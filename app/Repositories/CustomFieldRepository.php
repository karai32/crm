<?php

use Illuminate\Database\Query\Builder;

class CustomFieldRepository
{
    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}>
    public function all(string $sort = 'entity_type', string $dir = 'asc'): array
    {
        return Database::rows($this->ordered($sort, $dir));
    }

    // int
    public function count(): int
    {
        return Database::table('custom_fields')->count();
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}>
    public function paginate(int $page, int $perPage, string $sort = 'entity_type', string $dir = 'asc'): array
    {
        return Database::rows(
            $this->ordered($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // ?{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}
    public function find(int $id): ?array
    {
        return Database::row(Database::table('custom_fields')->where('id', $id));
    }

    // ?{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}
    public function findByEntityAndSlug(string $entityType, string $slug): ?array
    {
        return Database::row(
            Database::table('custom_fields')->where('entity_type', $entityType)->where('slug', $slug)
        );
    }

    public function create(array $data): int
    {
        return Database::table('custom_fields')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        Database::table('custom_fields')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        Database::table('custom_fields')->where('id', $id)->delete();
    }

    public function setOptions(int $fieldId, array $options): void
    {
        Database::table('custom_field_options')->where('field_id', $fieldId)->delete();

        if ($options !== []) {
            Database::table('custom_field_options')->insert(array_map(
                static fn (string $option, int $index): array => [
                    'field_id' => $fieldId,
                    'value' => $option,
                    'label' => $option,
                    'sort_order' => $index,
                ],
                array_values($options),
                array_keys(array_values($options))
            ));
        }
    }

    // array<{id, field_id, value, label, sort_order, created_at}>
    public function optionsForField(int $fieldId): array
    {
        return Database::rows(
            Database::table('custom_field_options')
                ->where('field_id', $fieldId)
                ->orderBy('sort_order')
                ->orderBy('label')
        );
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at, options}>
    public function fieldsForEntity(string $entityType): array
    {
        return $this->withOptions(Database::rows(
            Database::table('custom_fields')
                ->where('entity_type', $entityType)
                ->orderBy('sort_order')
                ->orderBy('name')
        ));
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at, options}>
    public function filterableFieldsForEntity(string $entityType): array
    {
        return $this->withOptions(Database::rows(
            Database::table('custom_fields')
                ->where('entity_type', $entityType)
                ->where('is_filterable', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
        ));
    }

    // array<{id, name}>
    public function distinctTextValues(int $fieldId, string $query = '', int $limit = 20, int $offset = 0): array
    {
        return array_map(
            static fn (array $row): array => ['id' => $row['val'], 'name' => $row['val']],
            Database::rows(
                Database::table('custom_field_values')
                    ->distinct()
                    ->select('value_text AS val')
                    ->where('field_id', $fieldId)
                    ->whereNotNull('value_text')
                    ->where('value_text', '!=', '')
                    ->when($query !== '', fn (Builder $builder) => $builder->where('value_text', 'like', '%' . $query . '%'))
                    ->orderBy('value_text')
                    ->limit($limit)
                    ->offset($offset)
            )
        );
    }

    // array<field_id, {id, field_id, entity_type, entity_id, value_text, value_number, value_date, value_bool, created_at, updated_at}>
    public function valuesForEntity(string $entityType, int $entityId): array
    {
        $values = [];
        $rows = Database::rows(
            Database::table('custom_field_values')
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
        );

        foreach ($rows as $row) {
            $values[(int) $row['field_id']] = $row;
        }

        return $values;
    }

    public function saveValues(string $entityType, int $entityId, array $fields, array $inputValues, bool $applyDefaults = false): void
    {
        $rows = [];

        foreach ($fields as $field) {
            $fieldId = (int) $field['id'];
            $rawValue = $inputValues[$fieldId] ?? null;

            // On creation, fill empty values with the field's default
            if ($applyDefaults && ($rawValue === null || trim((string) $rawValue) === '')) {
                $default = $field['default_value'] ?? null;
                if ($default !== null && trim((string) $default) !== '') {
                    $rawValue = $default;
                }
            }

            $valueText = null;
            $valueNumber = null;
            $valueDate = null;
            $valueBool = null;

            if ($field['field_type'] === 'checkbox') {
                $valueBool = $rawValue ? 1 : 0;
            } elseif ($field['field_type'] === 'number') {
                $valueNumber = trim((string) $rawValue) === '' ? null : (float) $rawValue;
            } elseif ($field['field_type'] === 'date') {
                $valueDate = trim((string) $rawValue) === '' ? null : trim((string) $rawValue);
            } else {
                $valueText = trim((string) $rawValue) === '' ? null : trim((string) $rawValue);
            }

            $rows[] = [
                'field_id' => $fieldId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'value_text' => $valueText,
                'value_number' => $valueNumber,
                'value_date' => $valueDate,
                'value_bool' => $valueBool,
            ];
        }

        if ($rows !== []) {
            Database::table('custom_field_values')->upsert(
                $rows,
                ['field_id', 'entity_type', 'entity_id'],
                ['value_text', 'value_number', 'value_date', 'value_bool']
            );
        }
    }

    private function ordered(string $sort, string $dir): Builder
    {
        $column = in_array($sort, ['entity_type', 'name', 'slug', 'field_type'], true)
            ? $sort
            : 'entity_type';

        return Database::table('custom_fields')->orderBy($column, $dir === 'desc' ? 'desc' : 'asc');
    }

    private function withOptions(array $fields): array
    {
        $selectIds = array_map(
            static fn (array $field): int => (int) $field['id'],
            array_filter($fields, static fn (array $field): bool => $field['field_type'] === 'select')
        );
        $options = $selectIds === [] ? [] : Database::rows(
            Database::table('custom_field_options')
                ->whereIn('field_id', $selectIds)
                ->orderBy('sort_order')
                ->orderBy('label')
        );
        $byField = [];

        foreach ($options as $option) {
            $byField[(int) $option['field_id']][] = $option;
        }
        foreach ($fields as &$field) {
            $field['options'] = $byField[(int) $field['id']] ?? [];
        }
        unset($field);

        return $fields;
    }

    public function displayValue(array $field, ?array $value): string
    {
        if ($value === null) {
            return '';
        }

        return match ($field['field_type']) {
            'checkbox' => ((int) ($value['value_bool'] ?? 0) === 1) ? 'Yes' : 'No',
            'number' => (string) ($value['value_number'] ?? ''),
            'date' => (string) ($value['value_date'] ?? ''),
            default => (string) ($value['value_text'] ?? ''),
        };
    }
}
