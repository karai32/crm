<?php

abstract class AbstractApiController
{
    private ApiKeyRepository $apiKeys;
    private ApiAuthenticator $authenticator;
    private string $rawInput = '';

    public function __construct()
    {
        $this->apiKeys = new ApiKeyRepository();
        $this->authenticator = new ApiAuthenticator($this->apiKeys);
    }

    protected function handle(string $scope, string $path, callable $action): void
    {
        $startedAt = microtime(true);
        $this->rawInput = trim((string) file_get_contents('php://input'));
        $apiKey = $this->authenticator->authenticate();
        $requestId = bin2hex(random_bytes(12));

        header('X-Request-Id: ' . $requestId);

        if ($apiKey === null) {
            $this->finish(
                null,
                $path,
                $requestId,
                $startedAt,
                new ApiResult(401, $this->error('unauthorized', 'Invalid or missing API key'))
            );
            return;
        }

        if (!$this->hasScope($apiKey, $scope)) {
            $this->finish(
                (int) $apiKey['id'],
                $path,
                $requestId,
                $startedAt,
                new ApiResult(403, $this->error('forbidden', "API key lacks {$scope} scope"))
            );
            return;
        }

        $this->apiKeys->touchLastUsed((int) $apiKey['id']);

        try {
            $result = $action();
            if (!$result instanceof ApiResult) {
                throw new LogicException('API actions must return ApiResult');
            }
        } catch (ApiException $exception) {
            $error = $this->error(
                $exception->errorCode(),
                $exception->getMessage(),
                $exception->details()
            );
            $result = new ApiResult($exception->status(), $error);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $result = new ApiResult(409, $this->error('conflict', 'A record with these values already exists'));
            } else {
                error_log('API database error [' . $requestId . ']: ' . $exception->getMessage());
                $result = new ApiResult(500, $this->error('server_error', 'Internal server error'));
            }
        } catch (Throwable $exception) {
            error_log('API error [' . $requestId . ']: ' . $exception->getMessage());
            $result = new ApiResult(500, $this->error('server_error', 'Internal server error'));
        }

        $this->finish((int) $apiKey['id'], $path, $requestId, $startedAt, $result);
    }

    protected function jsonObject(): array
    {
        $body = $this->decodeJson('{');

        if ($body === []) {
            throw new ApiException(422, 'validation_error', 'Request body must be a non-empty JSON object');
        }

        return $body;
    }

    protected function jsonBatch(int $limit = 100): array
    {
        $rawBody = $this->rawInput;

        if ($rawBody === '' || ($rawBody[0] !== '[' && $rawBody[0] !== '{')) {
            throw new ApiException(422, 'validation_error', 'Request body must be a JSON array or object');
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiException(422, 'validation_error', 'Request body must contain valid JSON');
        }

        if (!is_array($decoded)) {
            throw new ApiException(422, 'validation_error', 'Request body must be a JSON array or object');
        }

        // Accept both a single object {"first_name":...} and an array [{...},...]
        $body = array_is_list($decoded) ? $decoded : [$decoded];

        if ($body === []) {
            throw new ApiException(422, 'validation_error', 'Request body must not be empty');
        }

        if (count($body) > $limit) {
            throw new ApiException(422, 'too_many_items', "Maximum {$limit} items per request");
        }

        return $body;
    }

    protected function routeId(): int
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            throw new ApiException(404, 'not_found', 'Record not found');
        }

        return $id;
    }

    private function decodeJson(string $openingCharacter): array
    {
        $rawBody = $this->rawInput;
        if ($rawBody === '' || $rawBody[0] !== $openingCharacter) {
            $expected = $openingCharacter === '[' ? 'array' : 'object';
            throw new ApiException(422, 'validation_error', "Request body must be a JSON {$expected}");
        }

        try {
            $body = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiException(422, 'validation_error', 'Request body must contain valid JSON');
        }

        if (!is_array($body)) {
            throw new ApiException(422, 'validation_error', 'Request body must be a JSON object or array');
        }

        return $body;
    }

    private function hasScope(array $apiKey, string $required): bool
    {
        $scopes = json_decode((string) ($apiKey['scopes'] ?? '[]'), true);
        if (!is_array($scopes)) {
            return false;
        }

        if (in_array($required, $scopes, true)) {
            return true;
        }

        if (str_ends_with($required, ':read')) {
            return in_array(substr($required, 0, -5) . ':write', $scopes, true);
        }

        return false;
    }

    private function finish(
        ?int $apiKeyId,
        string $path,
        string $requestId,
        float $startedAt,
        ApiResult $result
    ): void {
        $responseJson = $this->respond($result->status, $result->data);

        $errorCode = $result->data['error']['code'] ?? null;
        $origin    = substr($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '', 0, 255) ?: null;
        $reqBody   = $this->rawInput !== '' ? $this->truncate($this->rawInput) : null;
        $respBody  = $this->truncate($responseJson);

        try {
            $this->apiKeys->log(
                $apiKeyId,
                $requestId,
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $path,
                $result->status,
                is_string($errorCode) ? $errorCode : null,
                $result->itemsCount,
                $_SERVER['REMOTE_ADDR'] ?? null,
                (int) round((microtime(true) - $startedAt) * 1000),
                $reqBody,
                $respBody,
                $origin
            );
        } catch (Throwable $exception) {
            error_log('API logging failed [' . $requestId . ']: ' . $exception->getMessage());
        }
    }

    private function respond(int $status, array $data): string
    {
        http_response_code($status);
        if ($status === 401) {
            header('WWW-Authenticate: Basic realm="CRM API", charset="UTF-8"');
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo $json;
        return $json;
    }

    private function truncate(string $s, int $limit = 64000): string
    {
        return strlen($s) > $limit ? substr($s, 0, $limit) . '…' : $s;
    }

    private function error(string $code, string $message, array $details = []): array
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return ['success' => false, 'error' => $error];
    }
}
