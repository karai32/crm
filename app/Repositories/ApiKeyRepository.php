<?php

use Illuminate\Database\Query\Builder;

class ApiKeyRepository
{
    // array<{id, name, client_id, scopes, is_active, last_used_at, created_at}>
    public function all(string $sort = 'created_at', string $dir = 'desc'): array
    {
        return Database::rows($this->ordered($sort, $dir));
    }

    // int
    public function count(): int
    {
        return Database::table('api_keys')->count();
    }

    // array<{id, name, client_id, scopes, is_active, last_used_at, created_at}>
    public function paginate(int $page, int $perPage, string $sort = 'created_at', string $dir = 'desc'): array
    {
        return Database::rows(
            $this->ordered($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // ?{id, name, client_id, secret_hash, scopes, is_active, last_used_at, revoked_at, created_at, updated_at}
    public function findActiveByClientId(string $clientId): ?array
    {
        return Database::row(
            Database::table('api_keys')->where('client_id', $clientId)->where('is_active', 1)
        );
    }

    public function create(string $name, string $clientId, string $secretHash, array $scopes): int
    {
        return Database::table('api_keys')->insertGetId([
            'name'        => $name,
            'client_id'   => $clientId,
            'secret_hash' => $secretHash,
            'scopes'      => json_encode($scopes),
        ]);
    }

    public function updateScopes(int $id, array $scopes): void
    {
        Database::table('api_keys')->where('id', $id)->update(['scopes' => json_encode($scopes)]);
    }

    public function touchLastUsed(int $id): void
    {
        Database::table('api_keys')
            ->where('id', $id)
            ->where(fn (Builder $query) => $query
                ->whereNull('last_used_at')
                ->orWhereRaw('last_used_at < CURRENT_TIMESTAMP - INTERVAL 5 MINUTE'))
            ->update(['last_used_at' => Database::raw('CURRENT_TIMESTAMP')]);
    }

    public function revoke(int $id): void
    {
        Database::table('api_keys')->where('id', $id)->update([
            'is_active' => 0,
            'revoked_at' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    public function updateName(int $id, string $name): void
    {
        Database::table('api_keys')->where('id', $id)->update(['name' => $name]);
    }

    public function enable(int $id): void
    {
        Database::table('api_keys')->where('id', $id)->update([
            'is_active' => 1,
            'revoked_at' => null,
        ]);
    }

    public function delete(int $id): void
    {
        Database::table('api_keys')->where('id', $id)->delete();
    }

    // array<{id, api_key_id, request_id, method, path, response_status, error_code, items_count, ip_address, duration_ms, request_body, response_body, origin, created_at, key_name, key_client_id}>
    public function pageLogs(int $page, int $perPage, array $filters = []): array
    {
        return Database::rows(
            $this->logs($filters)
                ->orderByDesc('l.id')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // int
    public function countLogs(array $filters = []): int
    {
        return $this->logs($filters)->count();
    }

    private function logs(array $filters): Builder
    {
        $query = Database::table('api_logs as l')
            ->leftJoin('api_keys as k', 'k.id', '=', 'l.api_key_id')
            ->select('l.*')
            ->addSelect(['k.name AS key_name', 'k.client_id AS key_client_id']);

        if (!empty($filters['key_id'])) {
            $query->where('l.api_key_id', (int) $filters['key_id']);
        }

        if (!empty($filters['method'])) {
            $query->where('l.method', strtoupper($filters['method']));
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $query->whereBetween('l.response_status', [200, 299]);
            } elseif ($filters['status'] === 'error') {
                $query->where('l.response_status', '>=', 400);
            }
        }

        if (!empty($filters['path'])) {
            $query->where('l.path', 'like', '%' . $filters['path'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->where('l.created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $query->where('l.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query;
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
        Database::table('api_logs')->insert([
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

    private function ordered(string $sort, string $dir): Builder
    {
        $column = in_array($sort, ['name', 'is_active', 'created_at'], true) ? $sort : 'created_at';

        return Database::table('api_keys')
            ->select(['id', 'name', 'client_id', 'scopes', 'is_active', 'last_used_at', 'created_at'])
            ->orderBy($column, $dir === 'asc' ? 'asc' : 'desc');
    }
}
