<?php

class EntityTagRepository
{
    private const ENTITIES = [
        'contact' => ['table' => 'contact_tags', 'column' => 'contact_id'],
        'client'  => ['table' => 'client_tags', 'column' => 'client_id'],
    ];

    public function tagsForEntities(string $entityType, array $entityIds): array
    {
        $entityIds = IdList::normalize($entityIds);
        if ($entityIds === []) {
            return [];
        }

        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $placeholders = implode(',', array_fill(0, count($entityIds), '?'));
        $statement = Database::connect()->prepare("
            SELECT links.{$column} AS entity_id, tags.id, tags.name, tags.color
            FROM {$table} links
            INNER JOIN tags ON tags.id = links.tag_id
            WHERE links.{$column} IN ({$placeholders})
            ORDER BY tags.name ASC
        ");
        $statement->execute($entityIds);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[(int) $row['entity_id']][] = $row;
        }

        return $result;
    }

    public function tagsForEntity(string $entityType, int $entityId): array
    {
        return $this->tagsForEntities($entityType, [$entityId])[$entityId] ?? [];
    }

    public function sync(string $entityType, int $entityId, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $tagIds = IdList::normalize($tagIds);
        $pdo = Database::connect();

        $delete = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = :entity_id");
        $delete->execute(['entity_id' => $entityId]);

        if ($tagIds === []) {
            return;
        }

        $insert = $pdo->prepare("
            INSERT INTO {$table} ({$column}, tag_id)
            VALUES (:entity_id, :tag_id)
        ");
        foreach ($tagIds as $tagId) {
            $insert->execute(['entity_id' => $entityId, 'tag_id' => $tagId]);
        }
    }

    public function add(string $entityType, array $entityIds, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $entityIds = IdList::normalize($entityIds);
        $tagIds = IdList::normalize($tagIds);
        if ($entityIds === [] || $tagIds === []) {
            return;
        }

        $insert = Database::connect()->prepare("
            INSERT IGNORE INTO {$table} ({$column}, tag_id)
            VALUES (:entity_id, :tag_id)
        ");
        foreach ($entityIds as $entityId) {
            foreach ($tagIds as $tagId) {
                $insert->execute(['entity_id' => $entityId, 'tag_id' => $tagId]);
            }
        }
    }

    public function remove(string $entityType, array $entityIds, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $entityIds = IdList::normalize($entityIds);
        $tagIds = IdList::normalize($tagIds);
        if ($entityIds === [] || $tagIds === []) {
            return;
        }

        $entityPlaceholders = implode(',', array_fill(0, count($entityIds), '?'));
        $tagPlaceholders = implode(',', array_fill(0, count($tagIds), '?'));
        $statement = Database::connect()->prepare("
            DELETE FROM {$table}
            WHERE {$column} IN ({$entityPlaceholders})
            AND tag_id IN ({$tagPlaceholders})
        ");
        $statement->execute(array_merge($entityIds, $tagIds));
    }

    private function definition(string $entityType): array
    {
        if (!isset(self::ENTITIES[$entityType])) {
            throw new InvalidArgumentException('Unsupported tag entity type: ' . $entityType);
        }

        return self::ENTITIES[$entityType];
    }
}
