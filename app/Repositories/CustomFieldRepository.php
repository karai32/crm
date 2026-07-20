<?php

class CustomFieldRepository
{
    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}>
    public function all(string $sort = 'entity_type', string $dir = 'asc'): array
    {
        $pdo = Database::connect();
        $allowed = [
            'entity_type' => 'entity_type',
            'name'        => 'name',
            'slug'        => 'slug',
            'field_type'  => 'field_type',
        ];
        $orderCol = $allowed[$sort] ?? 'entity_type';
        $orderDir = $dir === 'desc' ? 'DESC' : 'ASC';
        $statement = $pdo->prepare("SELECT * FROM custom_fields ORDER BY {$orderCol} {$orderDir}");
        $statement->execute();

        return $statement->fetchAll();
    }

    // int
    public function count(): int
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM custom_fields');
        $stmt->execute();

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}>
    public function paginate(int $page, int $perPage, string $sort = 'entity_type', string $dir = 'asc'): array
    {
        $pdo     = Database::connect();
        $allowed = ['entity_type' => 'entity_type', 'name' => 'name', 'slug' => 'slug', 'field_type' => 'field_type'];
        $orderCol = $allowed[$sort] ?? 'entity_type';
        $orderDir = $dir === 'desc' ? 'DESC' : 'ASC';
        $offset   = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("SELECT * FROM custom_fields ORDER BY {$orderCol} {$orderDir} LIMIT :limit OFFSET :offset");
        $stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ?{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}
    public function find(int $id): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM custom_fields WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $field = $statement->fetch();

        return $field ?: null;
    }

    // ?{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at}
    public function findByEntityAndSlug(string $entityType, string $slug): ?array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT *
            FROM custom_fields
            WHERE entity_type = :entity_type AND slug = :slug
            LIMIT 1
        ');
        $statement->execute([
            'entity_type' => $entityType,
            'slug' => $slug,
        ]);
        $field = $statement->fetch();

        return $field ?: null;
    }

    public function create(array $data): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO custom_fields (entity_type, name, slug, field_type, is_required, is_filterable, sort_order, default_value)
            VALUES (:entity_type, :name, :slug, :field_type, :is_required, :is_filterable, :sort_order, :default_value)
        ');
        $statement->execute($data);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo = Database::connect();
        $data['id'] = $id;

        $statement = $pdo->prepare('
            UPDATE custom_fields
            SET entity_type = :entity_type,
                name = :name,
                slug = :slug,
                field_type = :field_type,
                is_required = :is_required,
                is_filterable = :is_filterable,
                sort_order = :sort_order,
                default_value = :default_value
            WHERE id = :id
        ');
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('DELETE FROM custom_fields WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function setOptions(int $fieldId, array $options): void
    {
        $pdo = Database::connect();
        $pdo->prepare('DELETE FROM custom_field_options WHERE field_id = :field_id')->execute(['field_id' => $fieldId]);

        $insert = $pdo->prepare('
            INSERT INTO custom_field_options (field_id, value, label, sort_order)
            VALUES (:field_id, :value, :label, :sort_order)
        ');

        foreach (array_values($options) as $index => $option) {
            $insert->execute([
                'field_id' => $fieldId,
                'value' => $option,
                'label' => $option,
                'sort_order' => $index,
            ]);
        }
    }

    // array<{id, field_id, value, label, sort_order, created_at}>
    public function optionsForField(int $fieldId): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM custom_field_options WHERE field_id = :field_id ORDER BY sort_order ASC, label ASC');
        $statement->execute(['field_id' => $fieldId]);

        return $statement->fetchAll();
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at, options}>
    public function fieldsForEntity(string $entityType): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM custom_fields WHERE entity_type = :entity_type ORDER BY sort_order ASC, name ASC');
        $statement->execute(['entity_type' => $entityType]);
        $fields = $statement->fetchAll();

        foreach ($fields as $index => $field) {
            $fields[$index]['options'] = $field['field_type'] === 'select'
                ? $this->optionsForField((int) $field['id'])
                : [];
        }

        return $fields;
    }

    // array<{id, entity_type, name, slug, field_type, is_required, is_filterable, default_value, sort_order, created_at, updated_at, options}>
    public function filterableFieldsForEntity(string $entityType): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT *
            FROM custom_fields
            WHERE entity_type = :entity_type AND is_filterable = 1
            ORDER BY sort_order ASC, name ASC
        ');
        $statement->execute(['entity_type' => $entityType]);
        $fields = $statement->fetchAll();

        foreach ($fields as $index => $field) {
            $fields[$index]['options'] = $field['field_type'] === 'select'
                ? $this->optionsForField((int) $field['id'])
                : [];
        }

        return $fields;
    }

    // array<{id, name}>
    public function distinctTextValues(int $fieldId, string $query = '', int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::connect();
        $where = 'field_id = :field_id AND value_text IS NOT NULL AND value_text != \'\'';
        $params = [':field_id' => $fieldId];

        if ($query !== '') {
            $where .= ' AND value_text LIKE :query';
            $params[':query'] = '%' . $query . '%';
        }

        $sql = "SELECT DISTINCT value_text AS val FROM custom_field_values WHERE {$where} ORDER BY value_text ASC LIMIT :limit OFFSET :offset";
        $statement = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $statement->bindValue($k, $v);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            fn ($row) => ['id' => $row['val'], 'name' => $row['val']],
            $statement->fetchAll()
        );
    }

    // array<field_id, {id, field_id, entity_type, entity_id, value_text, value_number, value_date, value_bool, created_at, updated_at}>
    public function valuesForEntity(string $entityType, int $entityId): array
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            SELECT *
            FROM custom_field_values
            WHERE entity_type = :entity_type AND entity_id = :entity_id
        ');
        $statement->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        $values = [];

        foreach ($statement->fetchAll() as $row) {
            $values[(int) $row['field_id']] = $row;
        }

        return $values;
    }

    public function saveValues(string $entityType, int $entityId, array $fields, array $inputValues, bool $applyDefaults = false): void
    {
        $pdo = Database::connect();

        $sql = '
            INSERT INTO custom_field_values (
                field_id, entity_type, entity_id, value_text, value_number, value_date, value_bool
            ) VALUES (
                :field_id, :entity_type, :entity_id, :value_text, :value_number, :value_date, :value_bool
            )
            ON DUPLICATE KEY UPDATE
                value_text = VALUES(value_text),
                value_number = VALUES(value_number),
                value_date = VALUES(value_date),
                value_bool = VALUES(value_bool)
        ';

        $statement = $pdo->prepare($sql);

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

            $statement->execute([
                'field_id' => $fieldId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'value_text' => $valueText,
                'value_number' => $valueNumber,
                'value_date' => $valueDate,
                'value_bool' => $valueBool,
            ]);
        }
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
