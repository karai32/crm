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

        if (!array_key_exists($permission, self::permissionDefinitions())) {
            return false;
        }

        if (self::isAdmin()) {
            return true;
        }

        $permissions = self::userPermissions();

        return $permissions[$permission] ?? false;
    }

    public static function ensureAuthenticated(): bool
    {
        if (!self::check()) {
            self::tryRememberLogin();
        }

        return self::check();
    }

    public static function requireLogin(): void
    {
        if (!self::ensureAuthenticated()) {
            self::redirect('/login');
        }
    }

    public static function issueRememberToken(int $userId): void
    {
        $dir = self::tokenDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $token    = bin2hex(random_bytes(32));
        $lifetime = 30 * 24 * 3600;
        @file_put_contents($dir . '/' . $token, json_encode([
            'user_id' => $userId,
            'expires' => time() + $lifetime,
        ]));
        setcookie('remember_token', $token, [
            'expires'  => time() + $lifetime,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function revokeRememberToken(): void
    {
        $token = $_COOKIE['remember_token'] ?? '';
        if ($token !== '' && preg_match('/^[0-9a-f]{64}$/', $token)) {
            @unlink(self::tokenDir() . '/' . $token);
        }
        setcookie('remember_token', '', time() - 3600, '/');
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

    private static function tokenDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/remember';
    }

    private static function tryRememberLogin(): void
    {
        $token = $_COOKIE['remember_token'] ?? '';
        if ($token === '' || !preg_match('/^[0-9a-f]{64}$/', $token)) {
            return;
        }

        $file = self::tokenDir() . '/' . $token;
        if (!is_file($file)) {
            return;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || ($data['expires'] ?? 0) < time()) {
            @unlink($file);
            setcookie('remember_token', '', time() - 3600, '/');
            return;
        }

        try {
            $pdo  = Database::connect();
            // $user: ?{id, role_id, name, email, password_hash, is_active, last_login_at, created_at, updated_at, role}
            $stmt = $pdo->prepare('
                SELECT users.*, roles.name AS role
                FROM users
                INNER JOIN roles ON roles.id = users.role_id
                WHERE users.id = :id AND users.is_active = 1
                LIMIT 1
            ');
            $stmt->execute(['id' => (int) $data['user_id']]);
            $user = $stmt->fetch();
        } catch (Throwable) {
            return;
        }

        if (!$user) {
            @unlink($file);
            setcookie('remember_token', '', time() - 3600, '/');
            return;
        }

        // Rotate: delete old token, issue a fresh one
        @unlink($file);
        self::issueRememberToken((int) $user['id']);
        self::login($user);
    }

    // array<permission_key, bool>
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
        } catch (Throwable $exception) {
            error_log('Unable to load user permissions: ' . $exception->getMessage());
            $cache[$userId] = [];
            return [];
        }
    }
}
