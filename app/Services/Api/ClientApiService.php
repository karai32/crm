<?php

class ClientApiService extends AbstractApiService
{
    private ClientRepository $clients;
    private SectorRepository $sectors;
    private ClientWriteService $clientWriter;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository();
        $this->sectors = new SectorRepository();
        $this->clientWriter = new ClientWriteService(
            $this->clients,
            $this->entityTags,
            $this->customFields
        );
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
            'commercial_name' => $query['commercial_name'] ?? '',
            'legal_name' => $query['legal_name'] ?? '',
            'city' => $query['city'] ?? '',
            'country' => $query['country'] ?? '',
            'sector_id' => $query['sector_id'] ?? '',
            'tag_ids' => isset($query['tag_id']) ? [(int) $query['tag_id']] : [],
            'created_from' => $query['created_from'] ?? '',
            'created_to' => $query['created_to'] ?? '',
        ];

        $total = $this->clients->countAll($filters);
        $items = $this->clients->paginate($page, $perPage, $filters);
        $tags = $this->entityTags->tagsForEntities('client', array_column($items, 'id'));

        $data = array_map(function (array $client) use ($tags): array {
            $id = (int) $client['id'];
            return [
                'id' => $id,
                'commercial_name' => $client['commercial_name'],
                'legal_name' => $client['legal_name'],
                'city' => $client['city'],
                'province' => $client['province'],
                'country' => $client['country'],
                'sector' => $client['sector_name'],
                'sector_id' => $client['sector_id'] !== null ? (int) $client['sector_id'] : null,
                'created_at' => $client['created_at'] ?? null,
                'tags' => $this->formatTags($tags[$id] ?? []),
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
        return new ApiResult(200, ['success' => true, 'data' => $this->detail($id)], 1);
    }

    public function update(int $id, array $body): ApiResult
    {
        $this->requireRecord($this->clients->find($id), 'client');
        $body = $this->expandCustomFieldKeys($body);

        $changes = [];

        foreach (['commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes'] as $field) {
            if (array_key_exists($field, $body)) {
                $changes[$field] = $body[$field];
            }
        }

        if (array_key_exists('custom_fields', $body) && !is_array($body['custom_fields'])) {
            throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
        }

        Database::transaction(function () use ($id, $changes, $body): void {
            if (array_key_exists('sector', $body)) {
                [$changes['sector_id']] = $this->resolveSectorId((string) ($body['sector'] ?? ''));
            }

            $tagIds = null;
            if (array_key_exists('tags', $body)) {
                [$tagIds] = $this->resolveTagIds($this->splitNames($body['tags']));
            }

            $customFields = null;
            $customValues = [];
            if (array_key_exists('custom_fields', $body)) {
                [$customFields, $customValues] = $this->customFieldWriteData('client', $body['custom_fields']);
            }

            $this->clientWriter->update(
                id: $id,
                changes: $changes,
                tagIds: $tagIds,
                customFields: $customFields,
                customValues: $customValues
            );
        });

        return new ApiResult(200, ['success' => true, 'data' => $this->detail($id)], 1);
    }

    public function destroy(int $id): ApiResult
    {
        $this->requireRecord($this->clients->find($id), 'client');
        $this->clients->delete($id);
        return new ApiResult(200, ['success' => true, 'data' => ['id' => $id]], 1);
    }

    private function createOne(array $item): array
    {
        $item = $this->expandCustomFieldKeys($item);

        if (!empty($item['custom_fields']) && !is_array($item['custom_fields'])) {
            throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
        }

        [$sectorId, $sectorCreated] = $this->resolveSectorId((string) ($item['sector'] ?? ''));

        [$tagIds, $tagCreated] = $this->resolveTagIds($this->splitNames($item['tags'] ?? null));
        [$customFields, $customValues] = $this->customFieldWriteData(
            'client',
            is_array($item['custom_fields'] ?? null) ? $item['custom_fields'] : [],
            true
        );
        $clientId = $this->clientWriter->create(
            data: [
                'commercial_name' => $item['commercial_name'] ?? '',
                'legal_name' => $item['legal_name'] ?? null,
                'cif' => $item['cif'] ?? null,
                'address' => $item['address'] ?? null,
                'postal_code' => $item['postal_code'] ?? null,
                'city' => $item['city'] ?? null,
                'province' => $item['province'] ?? null,
                'country' => $item['country'] ?? null,
                'sector_id' => $sectorId,
                'website' => $item['website'] ?? null,
                'notes' => $item['notes'] ?? null,
            ],
            tagIds: $tagIds,
            customFields: $customFields,
            customValues: $customValues,
            applyCustomFieldDefaults: false
        );

        return ['client_id' => $clientId, 'tag_created' => $tagCreated, 'sector_created' => $sectorCreated];
    }

    private function detail(int $id): array
    {
        $client = $this->requireRecord($this->clients->find($id), 'client');
        $contacts = array_map(fn (array $contact): array => [
            'id' => (int) $contact['id'],
            'full_name' => $contact['full_name'],
            'email' => $contact['email'],
            'phone' => $contact['phone'],
        ], $this->clients->contactsForClient($id));

        return [
            'id' => (int) $client['id'],
            'commercial_name' => $client['commercial_name'],
            'legal_name' => $client['legal_name'],
            'cif' => $client['cif'],
            'address' => $client['address'],
            'postal_code' => $client['postal_code'],
            'city' => $client['city'],
            'province' => $client['province'],
            'country' => $client['country'],
            'sector' => $client['sector_name'] ?? null,
            'sector_id' => isset($client['sector_id']) ? (int) $client['sector_id'] : null,
            'website' => $client['website'],
            'notes' => $client['notes'],
            'created_at' => $client['created_at'] ?? null,
            'updated_at' => $client['updated_at'] ?? null,
            'tags' => $this->formatTags($this->entityTags->tagsForEntity('client', $id)),
            'contacts' => $contacts,
            'custom_fields' => $this->customFieldData('client', $id),
        ];
    }

    // Resolves a sector name to an id, auto-creating it when missing (mirrors tag behavior).
    // Returns [?int $id, bool $created]; empty name means "no sector".
    private function resolveSectorId(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return [null, false];
        }

        $sector = $this->sectors->findByName($name);
        if ($sector !== null) {
            return [(int) $sector['id'], false];
        }

        return [$this->sectors->create($name), true];
    }
}
