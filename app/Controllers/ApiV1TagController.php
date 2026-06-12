<?php

class ApiV1TagController
{
    private ApiKeyRepository $apiKeys;
    private TagRepository    $tags;

    public function __construct()
    {
        $this->apiKeys = new ApiKeyRepository();
        $this->tags    = new TagRepository();
    }

    public function tags(): void
    {
        $startTime = microtime(true);
        $method    = 'POST';
        $path      = '/api/v1/tags';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('tags:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks tags:write scope');
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
            $response = $this->errorResponse('too_many_items', 'Maximum 100 tags per request');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $results = [];
        foreach ($body as $index => $item) {
            $results[] = $this->processTag($index, is_array($item) ? $item : []);
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

    public function tagsList(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/tags';
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
            $response = $this->errorResponse('forbidden', 'API key lacks tags:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $all        = $this->tags->all();
        $nameFilter = strtolower(trim($_GET['name'] ?? ''));

        $items = $nameFilter !== ''
            ? array_values(array_filter($all, fn($t) => str_contains(strtolower($t['name']), $nameFilter)))
            : $all;

        $response = [
            'success' => true,
            'data'    => [
                'items' => array_map(fn($t) => $this->formatTag($t), $items),
                'total' => count($items),
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, null, $ip, $startTime);
    }

    public function tagsShow(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/tags/{id}';
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
            $response = $this->errorResponse('forbidden', 'API key lacks tags:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id  = (int) ($_GET['id'] ?? 0);
        $tag = $id > 0 ? $this->tags->find($id) : null;

        if ($tag === null) {
            $response = $this->errorResponse('not_found', 'Tag not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $response = ['success' => true, 'data' => $this->formatTag($tag)];
        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    public function tagsUpdate(): void
    {
        $startTime = microtime(true);
        $method    = 'PATCH';
        $path      = '/api/v1/tags/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('tags:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks tags:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id  = (int) ($_GET['id'] ?? 0);
        $tag = $id > 0 ? $this->tags->find($id) : null;

        if ($tag === null) {
            $response = $this->errorResponse('not_found', 'Tag not found');
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

        $name  = array_key_exists('name', $body) ? trim((string) $body['name']) : $tag['name'];
        $color = array_key_exists('color', $body)
            ? (trim((string) ($body['color'] ?? '')) ?: null)
            : $tag['color'];

        $this->tags->update($id, $name, $color);

        $tag      = $this->tags->find($id);
        $response = ['success' => true, 'data' => $this->formatTag($tag)];
        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, $body, 200, $response, $ip, $startTime);
    }

    public function tagsDestroy(): void
    {
        $startTime = microtime(true);
        $method    = 'DELETE';
        $path      = '/api/v1/tags/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('tags:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks tags:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id  = (int) ($_GET['id'] ?? 0);
        $tag = $id > 0 ? $this->tags->find($id) : null;

        if ($tag === null) {
            $response = $this->errorResponse('not_found', 'Tag not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $this->tags->delete($id);

        $response = ['success' => true, 'data' => ['id' => $id]];
        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    private function processTag(int $index, array $item): array
    {
        $name = trim($item['name'] ?? '');

        if ($name === '') {
            return ['index' => $index, 'success' => false, 'error' => ['code' => 'validation_error', 'details' => ['name is required']]];
        }

        $color = trim($item['color'] ?? '') ?: null;
        $tagId = $this->tags->create($name, $color);
        $tag   = $this->tags->find($tagId);

        return ['index' => $index, 'success' => true, 'data' => $this->formatTag($tag)];
    }

    private function formatTag(array $tag): array
    {
        return [
            'id'    => (int) $tag['id'],
            'name'  => $tag['name'],
            'slug'  => $tag['slug'],
            'color' => $tag['color'],
        ];
    }

    private function canRead(array $scopes): bool
    {
        return in_array('tags:read', $scopes, true) || in_array('tags:write', $scopes, true);
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
