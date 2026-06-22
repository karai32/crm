<?php

trait SortableTrait
{
    private function sortParam(array $allowed, string $default): string
    {
        $v = trim($_GET['sort'] ?? '');
        return in_array($v, $allowed, true) ? $v : $default;
    }

    private function dirParam(): string
    {
        return ($_GET['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
    }
}
