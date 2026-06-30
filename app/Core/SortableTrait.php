<?php

trait SortableTrait
{
    private function sortParam(array $allowed, string $default): string
    {
        $v = trim($_GET['sort'] ?? '');
        return in_array($v, $allowed, true) ? $v : $default;
    }

    private function dirParam(string $default = 'desc'): string
    {
        $v = $_GET['dir'] ?? '';
        if ($v === 'asc' || $v === 'desc') {
            return $v;
        }
        return $default;
    }

    protected function pageParams(int $total): array
    {
        $perPage    = SettingsRepository::perPage();
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        return [$page, $perPage, $totalPages];
    }
}
