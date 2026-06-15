<?php

class ContactApiService extends AbstractApiService
{
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private TagRepository $tags;

    public function __construct()
    {
        parent::__construct();
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
        $this->tags = new TagRepository();
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
            'first_name' => $query['first_name'] ?? '',
            'last_name' => $query['last_name'] ?? '',
            'email' => $query['email'] ?? '',
            'phone' => $query['phone'] ?? '',
            'is_company' => $query['is_company'] ?? '',
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
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
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
        $errors = [];

        if (array_key_exists('first_name', $body) && trim((string) ($body['first_name'] ?? '')) === '') {
            $errors[] = 'first_name cannot be empty';
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
            'first_name' => $contact['first_name'],
            'last_name' => $contact['last_name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'is_company' => $contact['is_company'],
        ];

        foreach (['first_name', 'last_name', 'email', 'phone', 'is_company'] as $field) {
            if (!array_key_exists($field, $body)) {
                continue;
            }
            $updated[$field] = $field === 'is_company'
                ? (empty($body[$field]) ? 0 : 1)
                : $this->nullableString($body[$field]);
        }
        $updated['first_name'] ??= $contact['first_name'];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $this->contacts->update($id, $updated);

            if (array_key_exists('tags', $body)) {
                if (!is_array($body['tags'])) {
                    throw new ApiException(422, 'validation_error', 'tags must be an array');
                }
                $this->contacts->syncTags($id, $this->resolveTagIds($body['tags']));
            }

            if (array_key_exists('clients', $body)) {
                if (!is_array($body['clients'])) {
                    throw new ApiException(422, 'validation_error', 'clients must be an array');
                }
                $this->contacts->syncClients($id, $this->resolveClientIds($body['clients']));
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
        // Support dot-notation keys: "custom_fields.notes" → custom_fields["notes"]
        foreach ($item as $key => $value) {
            if (str_starts_with($key, 'custom_fields.')) {
                $slug = substr($key, 14);
                if (!isset($item['custom_fields']) || !is_array($item['custom_fields'])) {
                    $item['custom_fields'] = [];
                }
                $item['custom_fields'][$slug] = $value;
                unset($item[$key]);
            }
        }

        $firstName = trim((string) ($item['first_name'] ?? ''));
        $email = trim((string) ($item['email'] ?? ''));
        $errors = [];

        if ($firstName === '') {
            $errors[] = 'first_name is required';
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

        $tagName = trim((string) ($item['tag'] ?? ''));
        $clientName = trim((string) ($item['client'] ?? ''));
        [$tagId, $tagCreated] = $this->resolveSingleTag($tagName);
        [$clientId, $clientCreated] = $this->resolveSingleClient($clientName);

        $contactId = $this->contacts->create([
            'first_name' => $firstName,
            'last_name' => $this->nullableString($item['last_name'] ?? null),
            'email' => $email === '' ? null : $email,
            'phone' => $this->nullableString($item['phone'] ?? null),
            'is_company' => empty($item['is_company']) ? 0 : 1,
        ]);

        if ($tagId !== null) {
            $this->contacts->syncTags($contactId, [$tagId]);
        }
        if ($clientId !== null) {
            $this->contacts->syncClients($contactId, [$clientId]);
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
            'first_name' => $contact['first_name'],
            'last_name' => $contact['last_name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
            'is_company' => (bool) $contact['is_company'],
            'created_at' => $contact['created_at'],
            'updated_at' => $contact['updated_at'] ?? null,
            'tags' => $this->formatTags($this->contacts->tagsForContact($id)),
            'clients' => $this->formatClients($this->contacts->clientsForContact($id)),
            'custom_fields' => $this->customFieldData('contact', $id),
        ];
    }

    private function resolveTagIds(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            [$id] = $this->resolveSingleTag(trim((string) $name));
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function resolveClientIds(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            [$id] = $this->resolveSingleClient(trim((string) $name));
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function resolveSingleTag(string $name): array
    {
        if ($name === '') {
            return [null, false];
        }
        $tag = $this->tags->findByName($name);
        return $tag === null
            ? [$this->tags->create($name, null), true]
            : [(int) $tag['id'], false];
    }

    private function resolveSingleClient(string $name): array
    {
        if ($name === '') {
            return [null, false];
        }
        $client = $this->clients->findByCommercialName($name);
        if ($client !== null) {
            return [(int) $client['id'], false];
        }

        $id = $this->clients->create([
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
        return [$id, true];
    }

    private function formatTags(array $tags): array
    {
        return array_map(fn (array $tag): array => [
            'id' => (int) $tag['id'],
            'name' => $tag['name'],
        ], $tags);
    }

    private function formatClients(array $clients): array
    {
        return array_map(fn (array $client): array => [
            'id' => (int) $client['id'],
            'name' => $client['commercial_name'],
        ], $clients);
    }
}
