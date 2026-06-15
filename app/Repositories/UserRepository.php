<?php

class UserRepository
{
    public function all(): array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT users.id, users.name, users.email, users.is_active, users.last_login_at,
                   users.created_at, roles.name AS role, roles.label AS role_label
            FROM users
            INNER JOIN roles ON roles.id = users.role_id
            ORDER BY users.id ASC
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function allRoles(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM roles ORDER BY id ASC');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT users.*, roles.name AS role
            FROM users
            INNER JOIN roles ON roles.id = users.role_id
            WHERE users.id = :id
            LIMIT 1
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $pdo = Database::connect();

        $sql = "
            SELECT users.*, roles.name AS role
            FROM users
            INNER JOIN roles ON roles.id = users.role_id
            WHERE users.email = :email
            LIMIT 1
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findRoleByName(string $name): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM roles WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $role = $statement->fetch();

        return $role ?: null;
    }

    public function permissionsForUser(int $userId): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT permission_key, is_allowed
            FROM user_permissions
            WHERE user_id = :user_id
        ');
        $statement->execute(['user_id' => $userId]);

        $permissions = [];

        foreach ($statement->fetchAll() as $row) {
            $permissions[$row['permission_key']] = (int) $row['is_allowed'] === 1;
        }

        return $permissions;
    }

    public function savePermissions(int $userId, array $permissions): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM user_permissions WHERE user_id = :user_id')->execute(['user_id' => $userId]);

        $statement = $pdo->prepare('
            INSERT INTO user_permissions (user_id, permission_key, is_allowed)
            VALUES (:user_id, :permission_key, :is_allowed)
        ');

        foreach ($permissions as $permissionKey => $isAllowed) {
            $statement->execute([
                'user_id' => $userId,
                'permission_key' => $permissionKey,
                'is_allowed' => $isAllowed ? 1 : 0,
            ]);
        }
    }

    public function create(array $data): int
    {
        $pdo = Database::connect();

        $sql = "
            INSERT INTO users (role_id, name, email, password_hash, is_active)
            VALUES (:role_id, :name, :email, :password_hash, :is_active)
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::connect();

        $sql = "
            UPDATE users
            SET role_id = :role_id,
                name = :name,
                email = :email,
                is_active = :is_active
            WHERE id = :id
        ";

        $statement = $pdo->prepare($sql);
        $statement->execute([
            'id' => $id,
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }

    public function deactivate(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function updateLastLogin(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
