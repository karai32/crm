<?php

class ApiV1ClientController
{
    private ApiKeyRepository      $apiKeys;
    private ClientRepository      $clients;
    private TagRepository         $tags;
    private SectorRepository      $sectors;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->apiKeys      = new ApiKeyRepository();
        $this->clients      = new ClientRepository();
        $this->tags         = new TagRepository();
        $this->sectors      = new SectorRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function clients(): void
    {
        $startTime = microtime(true);
        $method    = 'POST';
        $path      = '/api/v1/clients';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('clients:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks clients:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $body = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($body) || empty($body)) {
            $response = $this->errorResponse('validation_error', 'Request body must be a non-empty JSON array');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 422, $response, $ip, $startTime);
            return;
        }

        if (array_keys($body) !== range(0, count($body) - 1)) {
            $response = $this->errorResponse('validation_error', 'Request body must be a JSON array, not an object');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        if (count($body) > 100) {
            $response = $this->errorResponse('too_many_items', 'Maximum 100 clients per request');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $results = [];
        foreach ($body as $index => $item) {
            $results[] = $this->processClient($index, is_array($item) ? $item : []);
        }

        $created = count(array_filter($results, fn($r) => $r['success'] === true));
        $failed  = count($results) - $created;

        $response = [
            'success' => true,
            'data'    => [
                'processed' => count($results),
                'created'   => $created,
                'failed'    => $failed,
                'results'   => $results,
            ],
        ];

        $this->respond(207, $response);
        $this->log((int) $apiKey['id'], $method, $path, $body, 207, $response, $ip, $startTime);
    }

    public function clientsList(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/clients';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!$this->canRead($scopes)) {
            $response = $this->errorResponse('forbidden', 'API key lacks clients:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));

        $filters = [
            'commercial_name' => $_GET['commercial_name'] ?? '',
            'legal_name'      => $_GET['legal_name'] ?? '',
            'city'            => $_GET['city'] ?? '',
            'country'         => $_GET['country'] ?? '',
            'sector_id'       => $_GET['sector_id'] ?? '',
            'tag_ids'         => isset($_GET['tag_id']) ? [(int) $_GET['tag_id']] : [],
            'created_from'    => $_GET['created_from'] ?? '',
            'created_to'      => $_GET['created_to'] ?? '',
        ];

        $total     = $this->clients->countAll($filters);
        $items     = $this->clients->paginate($page, $perPage, $filters);
        $clientIds = array_column($items, 'id');

        $tagsMap = $this->clients->tagsForClients($clientIds);

        $data = [];
        foreach ($items as $client) {
            $id     = (int) $client['id'];
            $data[] = [
                'id'              => $id,
                'commercial_name' => $client['commercial_name'],
                'legal_name'      => $client['legal_name'],
                'city'            => $client['city'],
                'province'        => $client['province'],
                'country'         => $client['country'],
                'sector'          => $client['sector_name'],
                'created_at'      => $client['created_at'] ?? null,
                'tags'            => array_map(
                    fn($t) => ['id' => (int) $t['id'], 'name' => $t['name']],
                    $tagsMap[$id] ?? []
                ),
            ];
        }

        $response = [
            'success' => true,
            'data'    => [
                'items'       => $data,
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, null, $ip, $startTime);
    }

    public function clientsShow(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/clients/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!$this->canRead($scopes)) {
            $response = $this->errorResponse('forbidden', 'API key lacks clients:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $client = $id > 0 ? $this->clients->find($id) : null;

        if ($client === null) {
            $response = $this->errorResponse('not_found', 'Client not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $tags     = $this->clients->tagsForClient($id);
        $contacts = $this->clients->contactsForClient($id);
        $cfFields = $this->customFields->fieldsForEntity('client');
        $cfValues = $this->customFields->valuesForEntity('client', $id);

        $response = [
            'success' => true,
            'data'    => $this->formatClient($client, $tags, $contacts, $cfFields, $cfValues),
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    public function clientsUpdate(): void
    {
        $startTime = microtime(true);
        $method    = 'PATCH';
        $path      = '/api/v1/clients/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('clients:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks clients:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $client = $id > 0 ? $this->clients->find($id) : null;

        if ($client === null) {
            $response = $this->errorResponse('not_found', 'Client not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $body = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($body) || empty($body)) {
            $response = $this->errorResponse('validation_error', 'Request body must be a non-empty JSON object');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 422, $response, $ip, $startTime);
            return;
        }

        if (array_key_exists('commercial_name', $body) && trim((string) ($body['commercial_name'] ?? '')) === '') {
            $response = $this->errorResponse('validation_error', 'commercial_name cannot be empty');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $sectorId = $client['sector_id'];

        if (array_key_exists('sector', $body)) {
            $sectorName = trim((string) ($body['sector'] ?? ''));
            if ($sectorName === '') {
                $sectorId = null;
            } else {
                $sector = $this->sectors->findByName($sectorName);
                if ($sector === null) {
                    $response = $this->errorResponse('validation_error', "Sector '{$sectorName}' not found");
                    $this->respond(422, $response);
                    $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
                    return;
                }
                $sectorId = (int) $sector['id'];
            }
        }

        $stringFields = ['commercial_name', 'legal_name', 'cif', 'address', 'postal_code', 'city', 'province', 'country', 'website', 'notes'];
        $updated = [
            'commercial_name' => $client['commercial_name'],
            'legal_name'      => $client['legal_name'],
            'cif'             => $client['cif'],
            'address'         => $client['address'],
            'postal_code'     => $client['postal_code'],
            'city'            => $client['city'],
            'province'        => $client['province'],
            'country'         => $client['country'],
            'sector_id'       => $sectorId,
            'website'         => $client['website'],
            'notes'           => $client['notes'],
        ];

        foreach ($stringFields as $field) {
            if (array_key_exists($field, $body)) {
                $val = trim((string) ($body[$field] ?? ''));
                $updated[$field] = $val === '' ? null : $val;
            }
        }
        $updated['commercial_name'] = $updated['commercial_name'] ?? $client['commercial_name'];

        $this->clients->update($id, $updated);

        if (array_key_exists('tags', $body) && is_array($body['tags'])) {
            $tagIds = [];
            foreach ($body['tags'] as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName === '') continue;
                $tag      = $this->tags->findByName($tagName);
                $tagIds[] = $tag !== null ? (int) $tag['id'] : $this->tags->create($tagName, null);
            }
            $this->clients->syncTags($id, $tagIds);
        }

        if (array_key_exists('custom_fields', $body) && is_array($body['custom_fields'])) {
            $this->saveCustomFields($id, $body['custom_fields']);
        }

        $client   = $this->clients->find($id);
        $tags     = $this->clients->tagsForClient($id);
        $contacts = $this->clients->contactsForClient($id);
        $cfFields = $this->customFields->fieldsForEntity('client');
        $cfValues = $this->customFields->valuesForEntity('client', $id);

        $response = [
            'success' => true,
            'data'    => $this->formatClient($client, $tags, $contacts, $cfFields, $cfValues),
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, $body, 200, $response, $ip, $startTime);
    }

    public function clientsDestroy(): void
    {
        $startTime = microtime(true);
        $method    = 'DELETE';
        $path      = '/api/v1/clients/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('clients:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks clients:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $client = $id > 0 ? $this->clients->find($id) : null;

        if ($client === null) {
            $response = $this->errorResponse('not_found', 'Client not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $this->clients->delete($id);

        $response = ['success' => true, 'data' => ['id' => $id]];
        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    private function processClient(int $index, array $item): array
    {
        $commercialName = trim($item['commercial_name'] ?? '');

        if ($commercialName === '') {
            return ['index' => $index, 'success' => false, 'error' => ['code' => 'validation_error', 'details' => ['commercial_name is required']]];
        }

        $sectorId = null;
        $sectorName = trim($item['sector'] ?? '');
        if ($sectorName !== '') {
            $sector = $this->sectors->findByName($sectorName);
            if ($sector === null) {
                return ['index' => $index, 'success' => false, 'error' => ['code' => 'validation_error', 'details' => ["Sector '{$sectorName}' not found"]]];
            }
            $sectorId = (int) $sector['id'];
        }

        $strOrNull = fn($v) => trim((string) ($v ?? '')) ?: null;

        $clientId = $this->clients->create([
            'commercial_name' => $commercialName,
            'legal_name'      => $strOrNull($item['legal_name'] ?? null),
            'cif'             => $strOrNull($item['cif'] ?? null),
            'address'         => $strOrNull($item['address'] ?? null),
            'postal_code'     => $strOrNull($item['postal_code'] ?? null),
            'city'            => $strOrNull($item['city'] ?? null),
            'province'        => $strOrNull($item['province'] ?? null),
            'country'         => $strOrNull($item['country'] ?? null),
            'sector_id'       => $sectorId,
            'website'         => $strOrNull($item['website'] ?? null),
            'notes'           => $strOrNull($item['notes'] ?? null),
        ]);

        $tagCreated = false;
        if (!empty($item['tags']) && is_array($item['tags'])) {
            $tagIds = [];
            foreach ($item['tags'] as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName === '') continue;
                $tag = $this->tags->findByName($tagName);
                if ($tag === null) {
                    $tagIds[]   = $this->tags->create($tagName, null);
                    $tagCreated = true;
                } else {
                    $tagIds[] = (int) $tag['id'];
                }
            }
            $this->clients->syncTags($clientId, $tagIds);
        }

        $customIn = $item['custom_fields'] ?? [];
        if (is_array($customIn) && !empty($customIn)) {
            $this->saveCustomFields($clientId, $customIn);
        }

        return [
            'index'   => $index,
            'success' => true,
            'data'    => [
                'client_id'   => $clientId,
                'tag_created' => $tagCreated,
            ],
        ];
    }

    private function formatClient(array $client, array $tags, array $contacts, array $cfFields, array $cfValues): array
    {
        $customFields = [];
        foreach ($cfFields as $field) {
            $fieldId = (int) $field['id'];
            $value   = null;
            if (isset($cfValues[$fieldId])) {
                $row   = $cfValues[$fieldId];
                $value = match ($field['field_type']) {
                    'number'  => $row['value_number'] !== null ? (float) $row['value_number'] : null,
                    'date'    => $row['value_date'],
                    'boolean' => $row['value_bool'] !== null ? (bool) $row['value_bool'] : null,
                    default   => $row['value_text'],
                };
            }
            $customFields[$field['slug']] = $value;
        }

        return [
            'id'              => (int) $client['id'],
            'commercial_name' => $client['commercial_name'],
            'legal_name'      => $client['legal_name'],
            'cif'             => $client['cif'],
            'address'         => $client['address'],
            'postal_code'     => $client['postal_code'],
            'city'            => $client['city'],
            'province'        => $client['province'],
            'country'         => $client['country'],
            'sector'          => $client['sector_name'] ?? null,
            'website'         => $client['website'],
            'notes'           => $client['notes'],
            'created_at'      => $client['created_at'] ?? null,
            'updated_at'      => $client['updated_at'] ?? null,
            'tags'            => array_map(fn($t) => ['id' => (int) $t['id'], 'name' => $t['name']], $tags),
            'contacts'        => array_map(fn($c) => [
                'id'         => (int) $c['id'],
                'first_name' => $c['first_name'],
                'last_name'  => $c['last_name'],
                'email'      => $c['email'],
                'phone'      => $c['phone'],
            ], $contacts),
            'custom_fields'   => $customFields,
        ];
    }

    private function saveCustomFields(int $clientId, array $input): void
    {
        $fields      = [];
        $inputValues = [];

        foreach ($input as $slug => $value) {
            $field = $this->customFields->findByEntityAndSlug('client', (string) $slug);
            if ($field === null) {
                continue;
            }
            $fields[]                        = $field;
            $inputValues[(int) $field['id']] = $value;
        }

        if (!empty($fields)) {
            $this->customFields->saveValues('client', $clientId, $fields, $inputValues);
        }
    }

    private function canRead(array $scopes): bool
    {
        return in_array('clients:read', $scopes, true) || in_array('clients:write', $scopes, true);
    }

    private function authenticate(): ?array
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($key === '') {
            return null;
        }

        return $this->apiKeys->findByKeyHash(hash('sha256', $key));
    }

    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function errorResponse(string $code, string $message): array
    {
        return ['success' => false, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function log(?int $apiKeyId, string $method, string $path, ?array $requestBody, int $status, ?array $responseBody, ?string $ip, float $startTime): void
    {
        try {
            $this->apiKeys->log(
                $apiKeyId, $method, $path,
                $requestBody, $status, $responseBody,
                $ip, (int) round((microtime(true) - $startTime) * 1000)
            );
        } catch (Throwable) {
            // logging must never break the response
        }
    }
}
