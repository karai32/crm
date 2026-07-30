<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class TagRepository
{
    // array<{id, name, slug, color, created_at, updated_at}>
    public function first(int $limit = 50): array
    {
        return Database::rows(
            Database::table('tags')->orderBy('name')->limit($limit)
        );
    }

    // array<{id, name, slug, color, created_at, updated_at}>
    public function all(string $sort = 'name', string $dir = 'asc'): array
    {
        return Database::rows($this->ordered($sort, $dir));
    }

    // int
    public function count(): int
    {
        return Database::table('tags')->count();
    }

    // array<{id, name, slug, color, created_at, updated_at}>
    public function paginate(int $page, int $perPage, string $sort = 'name', string $dir = 'asc'): array
    {
        return Database::rows(
            $this->ordered($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // array<{id, name, slug, color, created_at, updated_at}>
    public function filter(string $name = ''): array
    {
        return Database::rows(
            Database::table('tags')
                ->when($name !== '', fn ($query) => $query->where('name', 'like', '%' . $name . '%'))
                ->orderBy('name')
        );
    }

    // ?{id, name, slug, color, created_at, updated_at}
    public function find(int $id): ?array
    {
        return Database::row(Database::table('tags')->where('id', $id));
    }

    // ?{id, name, slug, color, created_at, updated_at}
    public function findByName(string $name): ?array
    {
        return Database::row(Database::table('tags')->where('name', $name));
    }

    // array<{id, name, slug, color}>
    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        return Database::rows(
            Database::table('tags')
                ->select(['id', 'name', 'slug', 'color'])
                ->where('name', 'like', '%' . $query . '%')
                ->orderBy('name')
                ->limit($limit)
                ->offset($offset)
        );
    }

    public function create(string $name, ?string $color): int
    {
        return Database::table('tags')->insertGetId([
            'name' => $name,
            'slug' => $this->slug($name),
            'color' => $color,
        ]);
    }

    public function update(int $id, string $name, ?string $color): void
    {
        Database::table('tags')->where('id', $id)->update([
            'name' => $name,
            'slug' => $this->slug($name),
            'color' => $color,
        ]);
    }

    public function delete(int $id): void
    {
        // Contact and client tag links use ON DELETE CASCADE in the schema.
        Database::table('tags')->where('id', $id)->delete();
    }

    private function ordered(string $sort, string $dir): Builder
    {
        $column = in_array($sort, ['name', 'slug'], true) ? $sort : 'name';

        return Database::table('tags')->orderBy($column, $dir === 'asc' ? 'asc' : 'desc');
    }

    private function slug(string $name): string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : 'tag-' . time();
    }
}
