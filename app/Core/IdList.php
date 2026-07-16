<?php

final class IdList
{
    public static function normalize(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }
}
