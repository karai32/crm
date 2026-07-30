<?php

use Illuminate\Database\Query\Builder;

class UserRepository
{
    // array<{id, name, email, is_active, last_login_at, created_at, role, role_label}>
    public function all(string $sort = 'id', string $dir = 'asc'): array
    {
        return Database::rows($this->orderedUsers($sort, $dir));
    }

    // int
    public function count(): int
    {
        return Database::table('users')->count();
    }

    // array<{id, name, email, is_active, last_login_at, created_at, role, role_label}>
    public function paginate(int $page, int $perPage, string $sort = 'id', string $dir = 'asc'): array
    {
        return Database::rows(
            $this->orderedUsers($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // array<{id, name, label, created_at}>
    public function allRoles(): array
    {
        return Database::rows(Database::table('roles')->orderBy('id'));
    }

    // ?{id, role_id, name, email, password_hash, is_active, last_login_at, created_at, updated_at, role}
    public function find(int $id): ?array
    {
        return Database::row($this->userDetails()->where('users.id', $id));
    }

    // ?{id, role_id, name, email, password_hash, is_active, last_login_at, created_at, updated_at, role}
    public function findByEmail(string $email): ?array
    {
        return Database::row($this->userDetails()->where('users.email', $email));
    }

    // ?{id, name, label, created_at}
    public function findRoleByName(string $name): ?array
    {
        return Database::row(Database::table('roles')->where('name', $name));
    }

    // array<permission_key, bool>
    public function permissionsForUser(int $userId): array
    {
        $permissions = [];
        $values = Database::table('user_permissions')
            ->where('user_id', $userId)
            ->pluck('is_allowed', 'permission_key');

        foreach ($values as $key => $isAllowed) {
            $permissions[$key] = (int) $isAllowed === 1;
        }

        return $permissions;
    }

    public function savePermissions(int $userId, array $permissions): void
    {
        Database::table('user_permissions')->where('user_id', $userId)->delete();

        if ($permissions !== []) {
            Database::table('user_permissions')->insert(array_map(
                static fn (string $permissionKey, bool $isAllowed): array => [
                    'user_id' => $userId,
                    'permission_key' => $permissionKey,
                    'is_allowed' => $isAllowed ? 1 : 0,
                ],
                array_keys($permissions),
                array_values($permissions)
            ));
        }
    }

    public function create(array $data): int
    {
        return Database::table('users')->insertGetId([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        Database::table('users')->where('id', $id)->update([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        Database::table('users')->where('id', $id)->update(['password_hash' => $passwordHash]);
    }

    public function deactivate(int $id): void
    {
        Database::table('users')->where('id', $id)->update(['is_active' => 0]);
    }

    public function purge(int $id): void
    {
        Database::table('user_permissions')->where('user_id', $id)->delete();
        Database::table('users')->where('id', $id)->delete();
    }

    public function updateLastLogin(int $id): void
    {
        Database::table('users')->where('id', $id)->update([
            'last_login_at' => Database::raw('CURRENT_TIMESTAMP'),
        ]);
    }

    private function orderedUsers(string $sort, string $dir): Builder
    {
        $allowed = [
            'id' => 'users.id',
            'name' => 'users.name',
            'role' => 'roles.name',
            'is_active' => 'users.is_active',
        ];

        return $this->users()->orderBy(
            $allowed[$sort] ?? 'users.id',
            $dir === 'desc' ? 'desc' : 'asc'
        );
    }

    private function users(): Builder
    {
        return Database::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.is_active',
                'users.last_login_at',
                'users.created_at',
                'roles.name AS role',
                'roles.label AS role_label',
            ]);
    }

    private function userDetails(): Builder
    {
        return Database::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.*')
            ->addSelect('roles.name AS role');
    }
}
