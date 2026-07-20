<?php

class ApiKeyRepository
{
    // array<{id, name, client_id, scopes, is_active, last_used_at, created_at}>
    public function all(string $sort = 'created_at', string $dir = 'desc'): array
    {
        $pdo = Database::connect();
        $allowed = [
            'name'       => 'name',
            'is_active'  => 'is_active',
            'created_at' => 'created_at',
        ];
        $orderCol = $allowed[$sort] ?? 'created_at';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        $statement = $pdo->prepare("
            SELECT id, name, client_id, scopes, is_active, last_used_at, created_at
            FROM api_keys
            ORDER BY {$orderCol} {$orderDir}
        ");
        $statement->execute();

        return $statement->fetchAll();
    }

    // int
    public function count(): int
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM api_keys');
        $stmt->execute();

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    // array<{id, name, client_id, scopes, is_active, last_used_at, created_at}>
    public function paginate(int $page, int $perPage, string $sort = 'created_at', string $dir = 'desc'): array
    {
        $pdo     = Database::connect();
        $allowed = ['name' => 'name', 'is_active' => 'is_active', 'created_at' => 'created_at'];
        $orderCol = $allowed[$sort] ?? 'created_at';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        $offset   = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT id, name, client_id, scopes, is_active, last_used_at, created_at
            FROM api_keys
            ORDER BY {$orderCol} {$orderDir}
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ?{id, name, client_id, secret_hash, scopes, is_active, last_used_at, revoked_at, created_at, updated_at}
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

    public function updateName(int $id, string $name): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('UPDATE api_keys SET name = :name WHERE id = :id');
        $statement->execute(['name' => $name, 'id' => $id]);
    }

    public function enable(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            UPDATE api_keys
            SET is_active = 1, revoked_at = NULL
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

    // array<{id, api_key_id, request_id, method, path, response_status, error_code, items_count, ip_address, duration_ms, request_body, response_body, origin, created_at, key_name, key_client_id}>
    public function pageLogs(int $page, int $perPage, array $filters = []): array
    {
        $pdo = Database::connect();
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildLogsFilter($filters);

        $sql = "
            SELECT l.*, k.name AS key_name, k.client_id AS key_client_id
            FROM api_logs l
            LEFT JOIN api_keys k ON k.id = l.api_key_id
            {$where}
            ORDER BY l.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // int
    public function countLogs(array $filters = []): int
    {
        $pdo = Database::connect();
        [$where, $params] = $this->buildLogsFilter($filters);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM api_logs l
            LEFT JOIN api_keys k ON k.id = l.api_key_id
            {$where}
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    }

    private function buildLogsFilter(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['key_id'])) {
            $where[] = 'l.api_key_id = :key_id';
            $params['key_id'] = (int) $filters['key_id'];
        }

        if (!empty($filters['method'])) {
            $where[] = 'l.method = :method';
            $params['method'] = strtoupper($filters['method']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $where[] = '(l.response_status >= 200 AND l.response_status < 300)';
            } elseif ($filters['status'] === 'error') {
                $where[] = 'l.response_status >= 400';
            }
        }

        if (!empty($filters['path'])) {
            $where[] = 'l.path LIKE :path';
            $params['path'] = '%' . $filters['path'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'l.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'l.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return [
            empty($where) ? '' : 'WHERE ' . implode(' AND ', $where),
            $params,
        ];
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
        int $durationMs,
        ?string $requestBody = null,
        ?string $responseBody = null,
        ?string $origin = null
    ): void {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO api_logs (
                api_key_id, request_id, method, path, response_status,
                error_code, items_count, ip_address, duration_ms,
                request_body, response_body, origin
            )
            VALUES (
                :api_key_id, :request_id, :method, :path, :response_status,
                :error_code, :items_count, :ip_address, :duration_ms,
                :request_body, :response_body, :origin
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
            'request_body'    => $requestBody,
            'response_body'   => $responseBody,
            'origin'          => $origin,
        ]);
    }

}
