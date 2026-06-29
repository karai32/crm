<?php

class SettingsRepository
{
    private static array $cache = [];

    public function get(int $userId, string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$userId][$key])) {
            return self::$cache[$userId][$key];
        }

        $pdo  = Database::connect();
        $stmt = $pdo->prepare('
            SELECT preference_value
            FROM user_preferences
            WHERE user_id = :user_id AND preference_key = :key
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId, 'key' => $key]);
        $row = $stmt->fetch();

        $value = ($row !== false) ? $row['preference_value'] : $default;
        self::$cache[$userId][$key] = $value;

        return $value;
    }

    public function set(int $userId, string $key, mixed $value): void
    {
        $pdo = Database::connect();
        $pdo->prepare('
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (:user_id, :key, :value)
            ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = NOW()
        ')->execute(['user_id' => $userId, 'key' => $key, 'value' => (string) $value]);

        self::$cache[$userId][$key] = (string) $value;
    }

    public function all(int $userId): array
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('
            SELECT preference_key, preference_value
            FROM user_preferences
            WHERE user_id = :user_id
            ORDER BY preference_key ASC
        ');
        $stmt->execute(['user_id' => $userId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['preference_key']]               = $row['preference_value'];
            self::$cache[$userId][$row['preference_key']] = $row['preference_value'];
        }

        return $result;
    }

    public static function perPage(): int
    {
        $user   = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        if ($userId <= 0) {
            return 20;
        }

        static $instance = null;
        $instance ??= new self();
        $value = (int) $instance->get($userId, 'per_page', 20);

        return ($value >= 5 && $value <= 500) ? $value : 20;
    }
}
