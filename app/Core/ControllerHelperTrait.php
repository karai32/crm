<?php

trait ControllerHelperTrait
{
    protected function emptyToNull(string $value): ?string
    {
        return Strings::nullable($value);
    }

    protected function cleanIds(mixed $ids): array
    {
        return IdList::normalize($ids);
    }

    protected function idsFromPost(string $key): array
    {
        return $this->cleanIds($_POST[$key] ?? []);
    }

    protected function tagIdsFromGet(): array
    {
        $tagIds = $_GET['tag_ids'] ?? [];
        if (!empty($_GET['tag_id'])) {
            if (!is_array($tagIds)) {
                $tagIds = [];
            }
            $tagIds[] = $_GET['tag_id'];
        }
        return $this->cleanIds($tagIds);
    }

    protected function filterRowsByIds(array $rows, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return array_values(array_filter($rows, fn ($row) => in_array((int) $row['id'], $ids, true)));
    }

    protected function mergeSelectedRows(array $baseRows, array $selectedRows): array
    {
        $rows = [];
        foreach (array_merge($selectedRows, $baseRows) as $row) {
            $rows[(int) $row['id']] = $row;
        }
        return array_values($rows);
    }

    protected function customFieldFiltersFromRequest(): array
    {
        $values = $_GET['custom_fields'] ?? [];
        if (!is_array($values)) {
            return [];
        }
        $clean = [];
        foreach ($values as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value   = trim((string) $value);
            if ($fieldId > 0 && $value !== '') {
                $clean[$fieldId] = $value;
            }
        }
        return $clean;
    }
}
