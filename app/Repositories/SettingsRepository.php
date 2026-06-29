<?php

class SettingsRepository
{
    private static array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $row  = $stmt->fetch();

        $value = ($row !== false) ? $row['setting_value'] : $default;
        self::$cache[$key] = $value;

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $pdo = Database::connect();
        $pdo->prepare('
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ')->execute(['key' => $key, 'value' => (string) $value]);

        self::$cache[$key] = (string) $value;
    }

    public function all(): array
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC');
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['setting_key']]      = $row['setting_value'];
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }

    public static function perPage(): int
    {
        static $instance = null;
        $instance ??= new self();
        $value = (int) $instance->get('per_page', 20);

        return ($value >= 5 && $value <= 500) ? $value : 20;
    }
}
