<?php

class PhosphorIconCatalog
{
    private const DEFAULT_ICON = 'crosshair';

    private const RECOMMENDED = [
        'buildings', 'briefcase', 'storefront', 'factory', 'warehouse',
        'fork-knife', 'car', 'paint-brush', 'heartbeat', 'code',
        'graduation-cap', 'house', 'airplane', 'truck', 'currency-dollar',
        'bank', 'book-open', 'camera', 'music-notes', 'calendar',
        'leaf', 'lightning', 'desktop', 'shopping-cart', 'users',
        'map-pin', 'shield-check', 'scales', 'soccer-ball', 't-shirt',
        'martini', 'bed', 'stethoscope', 'baby', 'game-controller',
        'person-simple-run', 'globe', 'megaphone', 'gear', 'sparkle',
    ];

    private static ?array $icons = null;
    private static ?array $names = null;

    public function defaultIcon(): string
    {
        return self::DEFAULT_ICON;
    }

    public function normalize(?string $icon): ?string
    {
        $icon = strtolower(trim((string) $icon));

        if ($icon === '') {
            return null;
        }

        if (str_starts_with($icon, 'ph-')) {
            $icon = substr($icon, 3);
        }

        return preg_match('/^[a-z0-9][a-z0-9-]{0,78}[a-z0-9]$/', $icon)
            ? $icon
            : null;
    }

    public function isValid(?string $icon): bool
    {
        $rawIcon = trim((string) $icon);
        $icon = $this->normalize($icon);

        if ($icon === null) {
            return $rawIcon === '';
        }

        return isset($this->names()[$icon]);
    }

    public function recommended(): array
    {
        $byName = $this->names();
        $items = [];

        foreach (self::RECOMMENDED as $name) {
            if (isset($byName[$name])) {
                $items[] = $byName[$name];
            }
        }

        return $items;
    }

    public function search(string $query, int $limit = 36): array
    {
        $query = strtolower(trim($query));

        if ($query === '') {
            return $this->recommended();
        }

        $terms = preg_split('/[\s_-]+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matches = [];

        foreach ($this->icons() as $icon) {
            $score = $this->score($icon, $query, $terms);

            if ($score > 0) {
                $matches[] = ['score' => $score, 'icon' => $icon];
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['icon']['name'], $b['icon']['name']));

        return array_map(
            fn ($match) => $match['icon'],
            array_slice($matches, 0, $limit)
        );
    }

    private function score(array $icon, string $query, array $terms): int
    {
        $name = strtolower($icon['name'] ?? '');
        $alias = strtolower($icon['alias'] ?? '');
        $haystacks = array_merge(
            [$name, $alias, strtolower($icon['label'] ?? '')],
            array_map('strtolower', $icon['categories'] ?? []),
            array_map('strtolower', $icon['tags'] ?? [])
        );

        $score = 0;

        if ($name === $query || $alias === $query) {
            $score += 120;
        } elseif (str_starts_with($name, $query) || ($alias !== '' && str_starts_with($alias, $query))) {
            $score += 90;
        } elseif (str_contains($name, $query) || ($alias !== '' && str_contains($alias, $query))) {
            $score += 60;
        }

        foreach ($haystacks as $value) {
            if ($value === $query) {
                $score += 45;
            } elseif (str_starts_with($value, $query)) {
                $score += 24;
            } elseif (str_contains($value, $query)) {
                $score += 12;
            }
        }

        foreach ($terms as $term) {
            foreach ($haystacks as $value) {
                if ($value === $term) {
                    $score += 14;
                } elseif (str_contains($value, $term)) {
                    $score += 5;
                }
            }
        }

        return $score;
    }

    private function icons(): array
    {
        if (self::$icons !== null) {
            return self::$icons;
        }

        $path = dirname(__DIR__, 2) . '/public_html/assets/data/phosphor-icons.json';
        $data = json_decode((string) file_get_contents($path), true);

        self::$icons = is_array($data) ? $data : [];

        return self::$icons;
    }

    private function names(): array
    {
        if (self::$names !== null) {
            return self::$names;
        }

        self::$names = [];

        foreach ($this->icons() as $icon) {
            if (!empty($icon['name'])) {
                self::$names[$icon['name']] = $icon;
            }
        }

        return self::$names;
    }
}
