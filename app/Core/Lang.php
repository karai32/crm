<?php

class Lang
{
    private static array $strings = [];
    private static string $locale  = 'en';

    public static function load(string $locale): void
    {
        $allowed = ['en', 'es', 'ru'];
        self::$locale = in_array($locale, $allowed, true) ? $locale : 'en';

        $file = dirname(__DIR__, 2) . '/lang/' . self::$locale . '.php';
        self::$strings = file_exists($file) ? require $file : [];

        // Fallback to English for missing keys
        if (self::$locale !== 'en') {
            $fallback = dirname(__DIR__, 2) . '/lang/en.php';
            if (file_exists($fallback)) {
                self::$strings += require $fallback;
            }
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        $str = self::$strings[$key] ?? $key;
        if ($replace === []) {
            return $str;
        }

        $pairs = [];
        foreach ($replace as $k => $v) {
            $pairs[':' . $k] = (string) $v;
        }
        return strtr($str, $pairs);
    }

    public static function locale(): string
    {
        return self::$locale;
    }
}

function t(string $key, array $replace = []): string
{
    return htmlspecialchars(Lang::get($key, $replace), ENT_QUOTES, 'UTF-8');
}
