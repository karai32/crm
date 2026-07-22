<?php

// Blocks password brute-forcing per email address. File-backed (not session-backed,
// since an attacker never reuses a session/cookie) — no DB migration required.
class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 15 * 60;
    private const LOCKOUT_SECONDS = 15 * 60;

    public static function isLocked(string $email): bool
    {
        $entry = self::read()[self::key($email)] ?? null;

        return $entry !== null && (int) ($entry['locked_until'] ?? 0) > time();
    }

    public static function secondsRemaining(string $email): int
    {
        $entry = self::read()[self::key($email)] ?? null;

        return $entry === null ? 0 : max(0, (int) ($entry['locked_until'] ?? 0) - time());
    }

    public static function recordFailure(string $email): void
    {
        self::withLock(function (array $data) use ($email): array {
            $key = self::key($email);
            $now = time();
            $entry = $data[$key] ?? ['count' => 0, 'first_at' => $now, 'locked_until' => 0];

            if ($now - (int) $entry['first_at'] > self::WINDOW_SECONDS) {
                $entry = ['count' => 0, 'first_at' => $now, 'locked_until' => 0];
            }

            $entry['count'] = (int) $entry['count'] + 1;

            if ($entry['count'] >= self::MAX_ATTEMPTS) {
                $entry['locked_until'] = $now + self::LOCKOUT_SECONDS;
            }

            $data[$key] = $entry;

            return $data;
        });
    }

    public static function clear(string $email): void
    {
        self::withLock(function (array $data) use ($email): array {
            unset($data[self::key($email)]);

            return $data;
        });
    }

    private static function key(string $email): string
    {
        return strtolower(trim($email));
    }

    private static function filePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/login_throttle.json';
    }

    private static function read(): array
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    private static function withLock(callable $mutator): void
    {
        $path = self::filePath();
        $dir = dirname($path);

        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            return;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX);

        $size = filesize($path) ?: 0;
        $contents = $size > 0 ? fread($handle, $size) : '';
        $data = json_decode((string) $contents, true);
        $data = is_array($data) ? $data : [];

        // Prune stale entries so the file doesn't grow forever.
        $now = time();
        foreach ($data as $entryKey => $entry) {
            $lockedUntil = (int) ($entry['locked_until'] ?? 0);
            $firstAt = (int) ($entry['first_at'] ?? 0);
            if ($lockedUntil < $now && $now - $firstAt > self::WINDOW_SECONDS) {
                unset($data[$entryKey]);
            }
        }

        $data = $mutator($data);

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
