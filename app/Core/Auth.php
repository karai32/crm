<?php

class Auth
{
    public static function permissionDefinitions(): array
    {
        return [
            'contacts.create' => 'Create contacts',
            'contacts.edit' => 'Edit contacts',
            'contacts.delete' => 'Delete contacts',
            'clients.create' => 'Create clients',
            'clients.edit' => 'Edit clients',
            'clients.delete' => 'Delete clients',
            'exports.use' => 'Export data',
            'imports.manage' => 'Import data',
            'sectors.manage' => 'Manage sectors',
            'tags.manage' => 'Manage tags',
            'custom_fields.manage' => 'Manage custom fields',
        ];
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        if (self::isAdmin()) {
            return true;
        }

        if ($permission === 'users.manage') {
            return false;
        }

        $permissions = self::userPermissions();

        return array_key_exists($permission, $permissions) ? $permissions[$permission] : true;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();

        if (!self::can($permission)) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::url($path));
        exit;
    }

    public static function url(string $path): string
    {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $path = '/' . ltrim($path, '/');

        return ($basePath === '/' ? '' : $basePath) . $path;
    }

    private static function userPermissions(): array
    {
        static $cache = [];

        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            return [];
        }

        if (array_key_exists($userId, $cache)) {
            return $cache[$userId];
        }

        try {
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

            $cache[$userId] = $permissions;

            return $permissions;
        } catch (Throwable) {
            return [];
        }
    }
}
