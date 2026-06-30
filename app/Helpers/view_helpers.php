<?php

function paginationRange(int $current, int $last): array
{
    $pages = [];
    for ($i = 1; $i <= $last; $i++) {
        if ($i === 1 || $i === $last || abs($i - $current) <= 2) {
            $pages[] = $i;
        }
    }
    $result = [];
    $prev   = null;
    foreach ($pages as $p) {
        if ($prev !== null && $p - $prev > 1) {
            $result[] = '...';
        }
        $result[] = $p;
        $prev = $p;
    }
    return $result;
}

function thSort(string $col, string $label, string $sort, string $dir, string $basePath, array $baseParams = [], string $xClass = ''): string
{
    $nd     = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $url    = Auth::url($basePath . '?' . http_build_query(array_merge($baseParams, ['sort' => $col, 'dir' => $nd])));
    $active = $sort === $col;
    $iconName = $active ? ($dir === 'asc' ? 'ph-arrow-up' : 'ph-arrow-down') : 'ph-arrows-down-up';
    $cls    = trim('th-sort' . ($active ? ' th-sort--' . $dir : '') . ($xClass !== '' ? ' ' . $xClass : ''));
    $href   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    return '<th class="' . $cls . '"><a href="' . $href . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' <i class="ph ' . $iconName . ' sort-icon" aria-hidden="true"></i></a></th>';
}

function renderPagination(int $page, int $totalPages, int $total, int $perPage, string $basePath, array $baseParams = [], string $cssClass = 'list-pagination'): void
{
    if ($totalPages <= 1) {
        return;
    }
    $from = ($page - 1) * $perPage + 1;
    $to   = min($page * $perPage, $total);
    $url  = static function (array $extra) use ($basePath, $baseParams): string {
        return htmlspecialchars(Auth::url($basePath . '?' . http_build_query(array_merge($baseParams, $extra))), ENT_QUOTES, 'UTF-8');
    };
    echo '<div class="' . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8') . '">';
    echo '<span>Showing ' . $from . '–' . $to . ' of ' . (int) $total . '</span>';
    echo '<div class="pagination-pages">';
    if ($page > 1) {
        echo '<a class="page-btn" href="' . $url(['page' => $page - 1]) . '">&#8249;</a>';
    } else {
        echo '<span class="page-btn disabled">&#8249;</span>';
    }
    foreach (paginationRange($page, $totalPages) as $pg) {
        if ($pg === '...') {
            echo '<span class="page-ellipsis">...</span>';
        } else {
            $cls = 'page-btn' . ($pg === $page ? ' active' : '');
            echo '<a class="' . $cls . '" href="' . $url(['page' => $pg]) . '">' . $pg . '</a>';
        }
    }
    if ($page < $totalPages) {
        echo '<a class="page-btn" href="' . $url(['page' => $page + 1]) . '">&#8250;</a>';
    } else {
        echo '<span class="page-btn disabled">&#8250;</span>';
    }
    echo '</div></div>';
}
