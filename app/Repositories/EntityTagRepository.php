<?php

class EntityTagRepository
{
    private const ENTITIES = [
        'contact' => ['table' => 'contact_tags', 'column' => 'contact_id'],
        'client'  => ['table' => 'client_tags', 'column' => 'client_id'],
    ];

    // array<entity_id, array<{entity_id, id, name, color}>>
    public function tagsForEntities(string $entityType, array $entityIds): array
    {
        $entityIds = IdList::normalize($entityIds);
        if ($entityIds === []) {
            return [];
        }

        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $rows = Database::rows(
            Database::table("{$table} as links")
                ->join('tags', 'tags.id', '=', 'links.tag_id')
                ->selectRaw("links.{$column} AS entity_id")
                ->addSelect(['tags.id', 'tags.name', 'tags.color'])
                ->whereIn("links.{$column}", $entityIds)
                ->orderBy('tags.name')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['entity_id']][] = $row;
        }

        return $result;
    }

    // array<{entity_id, id, name, color}>
    public function tagsForEntity(string $entityType, int $entityId): array
    {
        return $this->tagsForEntities($entityType, [$entityId])[$entityId] ?? [];
    }

    public function sync(string $entityType, int $entityId, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $tagIds = IdList::normalize($tagIds);
        Database::table($table)->where($column, $entityId)->delete();

        if ($tagIds === []) {
            return;
        }

        Database::table($table)->insert(array_map(
            static fn (int $tagId): array => [$column => $entityId, 'tag_id' => $tagId],
            $tagIds
        ));
    }

    public function add(string $entityType, array $entityIds, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $entityIds = IdList::normalize($entityIds);
        $tagIds = IdList::normalize($tagIds);
        if ($entityIds === [] || $tagIds === []) {
            return;
        }

        $rows = [];
        foreach ($entityIds as $entityId) {
            foreach ($tagIds as $tagId) {
                $rows[] = [$column => $entityId, 'tag_id' => $tagId];
            }
        }

        Database::table($table)->insertOrIgnore($rows);
    }

    public function remove(string $entityType, array $entityIds, array $tagIds): void
    {
        ['table' => $table, 'column' => $column] = $this->definition($entityType);
        $entityIds = IdList::normalize($entityIds);
        $tagIds = IdList::normalize($tagIds);
        if ($entityIds === [] || $tagIds === []) {
            return;
        }

        Database::table($table)
            ->whereIn($column, $entityIds)
            ->whereIn('tag_id', $tagIds)
            ->delete();
    }

    private function definition(string $entityType): array
    {
        if (!isset(self::ENTITIES[$entityType])) {
            throw new InvalidArgumentException('Unsupported tag entity type: ' . $entityType);
        }

        return self::ENTITIES[$entityType];
    }
}
