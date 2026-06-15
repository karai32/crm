<?php

class ApiKeyRepository
{
    public function all(): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT id, name, client_id, scopes, is_active, last_used_at, created_at
            FROM api_keys
            ORDER BY created_at DESC
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findActiveByClientId(string $clientId): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT *
            FROM api_keys
            WHERE client_id = :client_id AND is_active = 1
            LIMIT 1
        ');
        $statement->execute(['client_id' => $clientId]);

        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $clientId, string $secretHash, array $scopes): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO api_keys (name, client_id, secret_hash, scopes)
            VALUES (:name, :client_id, :secret_hash, :scopes)
        ');
        $statement->execute([
            'name'        => $name,
            'client_id'   => $clientId,
            'secret_hash' => $secretHash,
            'scopes'      => json_encode($scopes),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function updateScopes(int $id, array $scopes): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE api_keys SET scopes = :scopes WHERE id = :id');
        $statement->execute(['scopes' => json_encode($scopes), 'id' => $id]);
    }

    public function touchLastUsed(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            UPDATE api_keys
            SET last_used_at = NOW()
            WHERE id = :id
              AND (last_used_at IS NULL OR last_used_at < NOW() - INTERVAL 5 MINUTE)
        ');
        $statement->execute(['id' => $id]);
    }

    public function revoke(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            UPDATE api_keys
            SET is_active = 0, revoked_at = NOW()
            WHERE id = :id
        ');
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
        string $requestId,
        string $method,
        string $path,
        int $responseStatus,
        ?string $errorCode,
        ?int $itemsCount,
        ?string $ip,
        int $durationMs
    ): void {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO api_logs (
                api_key_id, request_id, method, path, response_status,
                error_code, items_count, ip_address, duration_ms
            )
            VALUES (
                :api_key_id, :request_id, :method, :path, :response_status,
                :error_code, :items_count, :ip_address, :duration_ms
            )
        ');
        $statement->execute([
            'api_key_id'      => $apiKeyId,
            'request_id'      => $requestId,
            'method'          => $method,
            'path'            => $path,
            'response_status' => $responseStatus,
            'error_code'      => $errorCode,
            'items_count'     => $itemsCount,
            'ip_address'      => $ip,
            'duration_ms'     => $durationMs,
        ]);
    }

}
