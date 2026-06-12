<?php

class ApiV1SectorController
{
    private ApiKeyRepository $apiKeys;
    private SectorRepository $sectors;

    public function __construct()
    {
        $this->apiKeys  = new ApiKeyRepository();
        $this->sectors  = new SectorRepository();
    }

    public function sectors(): void
    {
        $startTime = microtime(true);
        $method    = 'POST';
        $path      = '/api/v1/sectors';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('sectors:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks sectors:write scope');
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
            $response = $this->errorResponse('too_many_items', 'Maximum 100 sectors per request');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $results = [];
        foreach ($body as $index => $item) {
            $results[] = $this->processSector($index, is_array($item) ? $item : []);
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

    public function sectorsList(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/sectors';
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
            $response = $this->errorResponse('forbidden', 'API key lacks sectors:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $all = $this->sectors->all();

        // Optional filters applied in PHP (sectors are a small reference set)
        $nameFilter     = strtolower(trim($_GET['name'] ?? ''));
        $isActiveFilter = $_GET['is_active'] ?? '';

        $items = array_values(array_filter($all, function (array $s) use ($nameFilter, $isActiveFilter): bool {
            if ($nameFilter !== '' && !str_contains(strtolower($s['name']), $nameFilter)) {
                return false;
            }
            if ($isActiveFilter !== '' && (int) $s['is_active'] !== (int) $isActiveFilter) {
                return false;
            }
            return true;
        }));

        $response = [
            'success' => true,
            'data'    => [
                'items' => array_map(fn($s) => $this->formatSector($s), $items),
                'total' => count($items),
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, null, $ip, $startTime);
    }

    public function sectorsShow(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/sectors/{id}';
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
            $response = $this->errorResponse('forbidden', 'API key lacks sectors:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $sector = $id > 0 ? $this->sectors->find($id) : null;

        if ($sector === null) {
            $response = $this->errorResponse('not_found', 'Sector not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $response = [
            'success' => true,
            'data'    => $this->formatSector($sector, $this->sectors->clientsCount($id)),
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    public function sectorsUpdate(): void
    {
        $startTime = microtime(true);
        $method    = 'PATCH';
        $path      = '/api/v1/sectors/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('sectors:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks sectors:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $sector = $id > 0 ? $this->sectors->find($id) : null;

        if ($sector === null) {
            $response = $this->errorResponse('not_found', 'Sector not found');
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

        if (array_key_exists('name', $body) && trim((string) ($body['name'] ?? '')) === '') {
            $response = $this->errorResponse('validation_error', 'name cannot be empty');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $name     = array_key_exists('name', $body) ? trim((string) $body['name']) : $sector['name'];
        $isActive = array_key_exists('is_active', $body) ? ($body['is_active'] ? 1 : 0) : (int) $sector['is_active'];

        $this->sectors->update($id, $name, $isActive);

        $sector   = $this->sectors->find($id);
        $response = [
            'success' => true,
            'data'    => $this->formatSector($sector, $this->sectors->clientsCount($id)),
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, $body, 200, $response, $ip, $startTime);
    }

    public function sectorsDestroy(): void
    {
        $startTime = microtime(true);
        $method    = 'DELETE';
        $path      = '/api/v1/sectors/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('sectors:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks sectors:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id     = (int) ($_GET['id'] ?? 0);
        $sector = $id > 0 ? $this->sectors->find($id) : null;

        if ($sector === null) {
            $response = $this->errorResponse('not_found', 'Sector not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $hasClients = $this->sectors->clientsCount($id) > 0;
        $this->sectors->deleteOrDeactivate($id);

        $response = [
            'success' => true,
            'data'    => [
                'id'     => $id,
                'action' => $hasClients ? 'deactivated' : 'deleted',
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    private function processSector(int $index, array $item): array
    {
        $name = trim($item['name'] ?? '');

        if ($name === '') {
            return ['index' => $index, 'success' => false, 'error' => ['code' => 'validation_error', 'details' => ['name is required']]];
        }

        $sectorId = $this->sectors->create($name);
        $sector   = $this->sectors->find($sectorId);

        return [
            'index'   => $index,
            'success' => true,
            'data'    => $this->formatSector($sector),
        ];
    }

    private function formatSector(array $sector, int $clientsCount = -1): array
    {
        $data = [
            'id'        => (int) $sector['id'],
            'name'      => $sector['name'],
            'slug'      => $sector['slug'],
            'is_active' => (bool) $sector['is_active'],
        ];

        if ($clientsCount >= 0) {
            $data['clients_count'] = $clientsCount;
        }

        return $data;
    }

    private function canRead(array $scopes): bool
    {
        return in_array('sectors:read', $scopes, true) || in_array('sectors:write', $scopes, true);
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
