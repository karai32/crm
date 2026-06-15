<?php

class ClientApiService extends AbstractApiService
{
    private ClientRepository $clients;
    private TagRepository $tags;
    private SectorRepository $sectors;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new ClientRepository();
        $this->tags = new TagRepository();
        $this->sectors = new SectorRepository();
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
        $tags = $this->clients->tagsForClients(array_column($items, 'id'));

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
        $client = $this->requireRecord($this->clients->find($id), 'client');
        if (array_key_exists('commercial_name', $body) && trim((string) ($body['commercial_name'] ?? '')) === '') {
            throw new ApiException(422, 'validation_error', 'commercial_name cannot be empty');
        }

        $sectorId = $client['sector_id'];
        if (array_key_exists('sector', $body)) {
            $sectorId = $this->sectorId((string) ($body['sector'] ?? ''), true);
        }

        $updated = [
            'commercial_name' => $client['commercial_name'],
            'legal_name' => $client['legal_name'],
            'cif' => $client['cif'],
            'address' => $client['address'],
            'postal_code' => $client['postal_code'],
            'city' => $client['city'],
            'province' => $client['province'],
            'country' => $client['country'],
            'sector_id' => $sectorId,
            'website' => $client['website'],
            'notes' => $client['notes'],
        ];

        foreach (['commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes'] as $field) {
            if (array_key_exists($field, $body)) {
                $updated[$field] = $this->nullableString($body[$field]);
            }
        }
        $updated['commercial_name'] ??= $client['commercial_name'];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $this->clients->update($id, $updated);
            if (array_key_exists('tags', $body)) {
                if (!is_array($body['tags'])) {
                    throw new ApiException(422, 'validation_error', 'tags must be an array');
                }
                $this->clients->syncTags($id, $this->resolveTagIds($body['tags']));
            }
            if (array_key_exists('custom_fields', $body)) {
                if (!is_array($body['custom_fields'])) {
                    throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
                }
                $this->saveCustomFields('client', $id, $body['custom_fields']);
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
        $this->requireRecord($this->clients->find($id), 'client');
        $this->clients->delete($id);
        return new ApiResult(200, ['success' => true, 'data' => ['id' => $id]], 1);
    }

    private function createOne(array $item): array
    {
        $commercialName = trim((string) ($item['commercial_name'] ?? ''));
        if ($commercialName === '') {
            throw new ApiException(422, 'validation_error', 'Client validation failed', ['commercial_name is required']);
        }

        $clientId = $this->clients->create([
            'commercial_name' => $commercialName,
            'legal_name' => $this->nullableString($item['legal_name'] ?? null),
            'cif' => $this->nullableString($item['cif'] ?? null),
            'address' => $this->nullableString($item['address'] ?? null),
            'postal_code' => $this->nullableString($item['postal_code'] ?? null),
            'city' => $this->nullableString($item['city'] ?? null),
            'province' => $this->nullableString($item['province'] ?? null),
            'country' => $this->nullableString($item['country'] ?? null),
            'sector_id' => $this->sectorId((string) ($item['sector'] ?? ''), true),
            'website' => $this->nullableString($item['website'] ?? null),
            'notes' => $this->nullableString($item['notes'] ?? null),
        ]);

        $tagCreated = false;
        if (!empty($item['tags'])) {
            if (!is_array($item['tags'])) {
                throw new ApiException(422, 'validation_error', 'tags must be an array');
            }
            [$tagIds, $tagCreated] = $this->resolveTagIdsWithCreated($item['tags']);
            $this->clients->syncTags($clientId, $tagIds);
        }

        if (!empty($item['custom_fields'])) {
            if (!is_array($item['custom_fields'])) {
                throw new ApiException(422, 'validation_error', 'custom_fields must be an object');
            }
            $this->saveCustomFields('client', $clientId, $item['custom_fields']);
        }

        return ['client_id' => $clientId, 'tag_created' => $tagCreated];
    }

    private function detail(int $id): array
    {
        $client = $this->requireRecord($this->clients->find($id), 'client');
        $contacts = array_map(fn (array $contact): array => [
            'id' => (int) $contact['id'],
            'first_name' => $contact['first_name'],
            'last_name' => $contact['last_name'],
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
            'website' => $client['website'],
            'notes' => $client['notes'],
            'created_at' => $client['created_at'] ?? null,
            'updated_at' => $client['updated_at'] ?? null,
            'tags' => $this->formatTags($this->clients->tagsForClient($id)),
            'contacts' => $contacts,
            'custom_fields' => $this->customFieldData('client', $id),
        ];
    }

    private function sectorId(string $name, bool $allowEmpty): ?int
    {
        $name = trim($name);
        if ($name === '' && $allowEmpty) {
            return null;
        }

        $sector = $this->sectors->findByName($name);
        if ($sector === null) {
            throw new ApiException(422, 'validation_error', "Sector '{$name}' not found");
        }
        return (int) $sector['id'];
    }

    private function resolveTagIds(array $names): array
    {
        return $this->resolveTagIdsWithCreated($names)[0];
    }

    private function resolveTagIdsWithCreated(array $names): array
    {
        $ids = [];
        $created = false;
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $tag = $this->tags->findByName($name);
            if ($tag === null) {
                $ids[] = $this->tags->create($name, null);
                $created = true;
            } else {
                $ids[] = (int) $tag['id'];
            }
        }
        return [array_values(array_unique($ids)), $created];
    }

    private function formatTags(array $tags): array
    {
        return array_map(fn (array $tag): array => [
            'id' => (int) $tag['id'],
            'name' => $tag['name'],
        ], $tags);
    }
}
