<?php

class ContactApiService extends AbstractApiService
{
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private ContactWriteService $contactWriter;
    private ClientWriteService $clientWriter;

    public function __construct()
    {
        parent::__construct();
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
        $this->contactWriter = new ContactWriteService(
            $this->contacts,
            $this->entityTags,
            $this->customFields
        );
        $this->clientWriter = new ClientWriteService($this->clients);
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
        $tags = $this->entityTags->tagsForEntities('contact', $ids);
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
        $this->requireRecord($this->contacts->find($id), 'contact');
        $body = $this->expandCustomFieldKeys($body);

        $changes = [];

        foreach (['full_name', 'email', 'phone', 'company'] as $field) {
            if (!array_key_exists($field, $body)) {
                continue;
            }
            $changes[$field] = $body[$field];
        }

        if (array_key_exists('custom_fields', $body) && !is_array($body['custom_fields'])) {
            throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
        }

        Database::transaction(function () use ($id, $changes, $body): void {
            $tagIds = null;
            if (array_key_exists('tags', $body)) {
                [$tagIds] = $this->resolveTagIds($this->splitNames($body['tags']));
            }

            $clientIds = null;
            if (array_key_exists('clients', $body)) {
                [$clientIds] = $this->resolveClientIds($this->splitNames($body['clients']));
            }

            $customFields = null;
            $customValues = [];
            if (array_key_exists('custom_fields', $body)) {
                [$customFields, $customValues] = $this->customFieldWriteData('contact', $body['custom_fields']);
            }

            $this->contactWriter->update(
                id: $id,
                changes: $changes,
                tagIds: $tagIds,
                clientIds: $clientIds,
                customFields: $customFields,
                customValues: $customValues
            );
        });

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

        if (!empty($item['custom_fields']) && !is_array($item['custom_fields'])) {
            throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
        }

        // "tags"/"clients" accept a single name, a comma-separated string, or a JSON array.
        [$tagIds, $tagCreated] = $this->resolveTagIds($this->splitNames($item['tags'] ?? null));
        [$clientIds, $clientCreated] = $this->resolveClientIds($this->splitNames($item['clients'] ?? null));

        [$customFields, $customValues] = $this->customFieldWriteData(
            'contact',
            is_array($item['custom_fields'] ?? null) ? $item['custom_fields'] : [],
            true
        );
        $contactId = $this->contactWriter->create(
            data: [
                'full_name' => $item['full_name'] ?? '',
                'email' => $item['email'] ?? null,
                'phone' => $item['phone'] ?? null,
                'company' => $item['company'] ?? '',
            ],
            tagIds: $tagIds,
            clientIds: $clientIds,
            customFields: $customFields,
            customValues: $customValues,
            applyCustomFieldDefaults: false
        );

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
            'tags' => $this->formatTags($this->entityTags->tagsForEntity('contact', $id)),
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
        return $this->clientWriter->create([
            'commercial_name' => $name,
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
