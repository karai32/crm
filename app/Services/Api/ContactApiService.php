<?php

class ContactApiService extends AbstractApiService
{
    private ContactRepository $contacts;
    private ClientRepository $clients;

    public function __construct()
    {
        parent::__construct();
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
    }

    public function createBatch(array $items): ApiResult
    {
        return $this->batch($items, fn (array $item): array => $this->createOne($item));
    }

    public function index(array $query): ApiResult
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 25)));
        $filters = [
            'full_name' => $query['full_name'] ?? '',
            'email' => $query['email'] ?? '',
            'phone' => $query['phone'] ?? '',
            'company' => $query['company'] ?? '',
            'client_id' => $query['client_id'] ?? '',
            'tag_ids' => isset($query['tag_id']) ? [(int) $query['tag_id']] : [],
            'created_from' => $query['created_from'] ?? '',
            'created_to' => $query['created_to'] ?? '',
        ];

        $total = $this->contacts->countAll($filters);
        $items = $this->contacts->paginate($page, $perPage, $filters);
        $ids = array_column($items, 'id');
        $tags = $this->contacts->tagsForContacts($ids);
        $clients = $this->contacts->clientsForContacts($ids);

        $data = array_map(function (array $contact) use ($tags, $clients): array {
            $id = (int) $contact['id'];
            return [
                'id' => $id,
                'full_name' => $contact['full_name'],
                'email' => $contact['email'],
                'phone' => $contact['phone'],
                'created_at' => $contact['created_at'],
                'tags' => $this->formatTags($tags[$id] ?? []),
                'clients' => $this->formatClients($clients[$id] ?? []),
            ];
        }, $items);

        return new ApiResult(200, [
            'success' => true,
            'data' => [
                'items' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ], count($data));
    }

    public function show(int $id): ApiResult
    {
        return new ApiResult(200, [
            'success' => true,
            'data' => $this->detail($id),
        ], 1);
    }

    public function update(int $id, array $body): ApiResult
    {
        $contact = $this->requireRecord($this->contacts->find($id), 'contact');
        $body = $this->expandCustomFieldKeys($body);
        $errors = [];

        if (array_key_exists('full_name', $body) && trim((string) ($body['full_name'] ?? '')) === '') {
            $errors[] = 'full_name cannot be empty';
        }

        if (array_key_exists('email', $body)) {
            $email = trim((string) ($body['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'email is invalid';
            } elseif ($email !== '' && $this->contacts->emailTakenByOther($email, $id)) {
                $errors[] = 'email is already used by another contact';
            }
        }

        if ($errors !== []) {
            throw new ApiException(422, 'validation_error', 'Contact validation failed', $errors);
        }

        $updated = [
            'full_name' => $contact['full_name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'company' => $contact['company'],
        ];

        foreach (['full_name', 'email', 'phone', 'company'] as $field) {
            if (!array_key_exists($field, $body)) {
                continue;
            }
            $updated[$field] = $field === 'full_name'
                ? trim((string) ($body[$field] ?? ''))
                : ($field === 'company'
                    ? trim((string) ($body[$field] ?? ''))
                    : $this->nullableString($body[$field]));
        }
        $updated['full_name'] = $updated['full_name'] ?: $contact['full_name'];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $this->contacts->update($id, $updated);

            if (array_key_exists('tags', $body)) {
                [$tagIds] = $this->resolveTagIds($this->splitNames($body['tags']));
                $this->contacts->syncTags($id, $tagIds);
            }

            if (array_key_exists('clients', $body)) {
                [$clientIds] = $this->resolveClientIds($this->splitNames($body['clients']));
                $this->contacts->syncClients($id, $clientIds);
            }

            if (array_key_exists('custom_fields', $body)) {
                if (!is_array($body['custom_fields'])) {
                    throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
                }
                $this->saveCustomFields('contact', $id, $body['custom_fields']);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        return new ApiResult(200, ['success' => true, 'data' => $this->detail($id)], 1);
    }

    public function destroy(int $id): ApiResult
    {
        $this->requireRecord($this->contacts->find($id), 'contact');
        $this->contacts->delete($id);

        return new ApiResult(200, ['success' => true, 'data' => ['id' => $id]], 1);
    }

    private function createOne(array $item): array
    {
        $item = $this->expandCustomFieldKeys($item);

        $fullName = trim((string) ($item['full_name'] ?? ''));
        $email = trim((string) ($item['email'] ?? ''));
        $errors = [];

        if ($fullName === '') {
            $errors[] = 'full_name is required';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email is invalid';
        }
        if ($errors !== []) {
            throw new ApiException(422, 'validation_error', 'Contact validation failed', $errors);
        }
        if ($email !== '' && $this->contacts->emailExists($email)) {
            throw new ApiException(409, 'duplicate_contact', 'Contact with this email already exists');
        }

        // "tags"/"clients" accept a single name, a comma-separated string, or a JSON array.
        [$tagIds, $tagCreated] = $this->resolveTagIds($this->splitNames($item['tags'] ?? null));
        [$clientIds, $clientCreated] = $this->resolveClientIds($this->splitNames($item['clients'] ?? null));

        $contactId = $this->contacts->create([
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'phone' => $this->nullableString($item['phone'] ?? null),
            'company' => trim((string) ($item['company'] ?? '')),
        ]);

        if ($tagIds !== []) {
            $this->contacts->syncTags($contactId, $tagIds);
        }
        if ($clientIds !== []) {
            $this->contacts->syncClients($contactId, $clientIds);
        }
        if (!empty($item['custom_fields']) && !is_array($item['custom_fields'])) {
            throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
        }
        $this->saveCustomFields('contact', $contactId, is_array($item['custom_fields'] ?? null) ? $item['custom_fields'] : [], true);

        return [
            'contact_id' => $contactId,
            'client_created' => $clientCreated,
            'tag_created' => $tagCreated,
        ];
    }

    private function detail(int $id): array
    {
        $contact = $this->requireRecord($this->contacts->find($id), 'contact');

        return [
            'id' => (int) $contact['id'],
            'full_name' => $contact['full_name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'company' => $contact['company'],
            'created_at' => $contact['created_at'],
            'updated_at' => $contact['updated_at'] ?? null,
            'tags' => $this->formatTags($this->contacts->tagsForContact($id)),
            'clients' => $this->formatClients($this->contacts->clientsForContact($id)),
            'custom_fields' => $this->customFieldData('contact', $id),
        ];
    }

    // Resolves client commercial names to ids, auto-creating any that don't exist yet.
    // Returns [int[] $ids, bool $anyCreated].
    private function resolveClientIds(array $names): array
    {
        $ids = [];
        $created = false;

        foreach ($names as $name) {
            $client = $this->clients->findByCommercialName($name);
            if ($client === null) {
                $ids[] = $this->createBlankClient($name);
                $created = true;
            } else {
                $ids[] = (int) $client['id'];
            }
        }

        return [array_values(array_unique($ids)), $created];
    }

    private function createBlankClient(string $name): int
    {
        return $this->clients->create([
            'commercial_name' => $name,
            'legal_name' => null,
            'cif' => null,
            'address' => null,
            'postal_code' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'sector_id' => null,
            'website' => null,
            'notes' => null,
        ]);
    }

    private function formatClients(array $clients): array
    {
        return array_map(fn (array $client): array => [
            'id' => (int) $client['id'],
            'name' => $client['commercial_name'],
        ], $clients);
    }
}
