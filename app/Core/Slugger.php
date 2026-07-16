<?php

final class Slugger
{
    public static function make(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);

        return trim((string) $slug, '-');
    }
}
