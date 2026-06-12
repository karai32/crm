<?php

class ApiKeyRepository
{
    public function all(): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT id, name, key_prefix, scopes, is_active, last_used_at, created_at
            FROM api_keys
            ORDER BY created_at DESC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT id, name, key_prefix, scopes, is_active, last_used_at, created_at
            FROM api_keys
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByKeyHash(string $hash): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT *
            FROM api_keys
            WHERE key_hash = :hash AND is_active = 1
            LIMIT 1
        ');
        $statement->execute(['hash' => $hash]);

        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $keyPrefix, string $keyHash, array $scopes): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO api_keys (name, key_prefix, key_hash, scopes)
            VALUES (:name, :key_prefix, :key_hash, :scopes)
        ');
        $statement->execute([
            'name'       => $name,
            'key_prefix' => $keyPrefix,
            'key_hash'   => $keyHash,
            'scopes'     => json_encode($scopes),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function updateLastUsed(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function revoke(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE api_keys SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('DELETE FROM api_keys WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function log(
        ?int $apiKeyId,
        string $method,
        string $path,
        ?array $requestBody,
        int $responseStatus,
        ?array $responseBody,
        ?string $ip,
        int $durationMs
    ): void {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO api_logs (api_key_id, method, path, request_body, response_status, response_body, ip_address, duration_ms)
            VALUES (:api_key_id, :method, :path, :request_body, :response_status, :response_body, :ip_address, :duration_ms)
        ');
        $statement->execute([
            'api_key_id'      => $apiKeyId,
            'method'          => $method,
            'path'            => $path,
            'request_body'    => $requestBody !== null ? json_encode($requestBody, JSON_UNESCAPED_UNICODE) : null,
            'response_status' => $responseStatus,
            'response_body'   => $responseBody !== null ? json_encode($responseBody, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'      => $ip,
            'duration_ms'     => $durationMs,
        ]);
    }

    public function recentLogs(int $apiKeyId, int $limit = 20): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT id, method, path, response_status, ip_address, duration_ms, created_at
            FROM api_logs
            WHERE api_key_id = :key_id
            ORDER BY created_at DESC
            LIMIT :lim
        ');
        $statement->bindValue('key_id', $apiKeyId, PDO::PARAM_INT);
        $statement->bindValue('lim', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
