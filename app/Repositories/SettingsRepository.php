<?php

class SettingsRepository
{
    private static array $cache = [];

    // string (preference_value), or $default if not set
    public function get(int $userId, string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$userId][$key])) {
            return self::$cache[$userId][$key];
        }

        $row = Database::row(
            Database::table('user_preferences')
                ->select('preference_value')
                ->where('user_id', $userId)
                ->where('preference_key', $key)
        );
        $value = $row === null ? $default : $row['preference_value'];
        self::$cache[$userId][$key] = $value;

        return $value;
    }

    public function set(int $userId, string $key, mixed $value): void
    {
        Database::table('user_preferences')->upsert([[
            'user_id' => $userId,
            'preference_key' => $key,
            'preference_value' => (string) $value,
            'updated_at' => Database::raw('CURRENT_TIMESTAMP'),
        ]], ['user_id', 'preference_key'], ['preference_value', 'updated_at']);

        self::$cache[$userId][$key] = (string) $value;
    }

    // array<preference_key, preference_value>
    public function all(int $userId): array
    {
        $result = Database::table('user_preferences')
            ->where('user_id', $userId)
            ->orderBy('preference_key')
            ->pluck('preference_value', 'preference_key')
            ->all();

        foreach ($result as $key => $value) {
            self::$cache[$userId][$key] = $value;
        }

        return $result;
    }

    // int
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
