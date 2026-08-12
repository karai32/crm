<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class SectorRepository
{
    // array<{id, name, slug, icon, is_active, created_at, updated_at}>
    public function all(string $sort = 'name', string $dir = 'asc'): array
    {
        return Database::rows($this->ordered($sort, $dir));
    }

    // int
    public function count(): int
    {
        return Database::table('sectors')->count();
    }

    // array<{id, name, slug, icon, is_active, created_at, updated_at}>
    public function paginate(int $page, int $perPage, string $sort = 'name', string $dir = 'asc'): array
    {
        return Database::rows(
            $this->ordered($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    // array<{id, name, slug, icon, is_active, created_at, updated_at}>
    public function active(): array
    {
        return Database::rows(
            Database::table('sectors')->where('is_active', 1)->orderBy('name')
        );
    }

    // array<{id, name, slug, icon, is_active, created_at, updated_at}>
    public function filter(string $name = '', mixed $isActive = ''): array
    {
        return Database::rows(
            Database::table('sectors')
                ->when($name !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $name . '%'))
                ->when(
                    $isActive !== '' && $isActive !== null,
                    fn (Builder $query) => $query->where('is_active', (int) $isActive)
                )
                ->orderByDesc('is_active')
                ->orderBy('name')
        );
    }

    // ?{id, name, slug, icon, is_active, created_at, updated_at}
    public function find(int $id): ?array
    {
        return Database::row(Database::table('sectors')->where('id', $id));
    }

    // ?{id, name, slug, icon, is_active, created_at, updated_at}
    public function findByName(string $name): ?array
    {
        return Database::row(Database::table('sectors')->where('name', $name));
    }

    // array<{id, name, slug, icon, is_active}>
    public function search(string $query, int $limit = 50): array
    {
        return Database::rows(
            Database::table('sectors')
                ->select(['id', 'name', 'slug', 'icon', 'is_active'])
                ->where(fn (Builder $builder) => $builder
                    ->where('name', 'like', '%' . $query . '%')
                    ->orWhere('slug', 'like', '%' . $query . '%'))
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->limit($limit)
        );
    }

    public function create(string $name): int
    {
        return Database::table('sectors')->insertGetId([
            'name' => $name,
            'slug' => $this->slug($name),
            'is_active' => 1,
        ]);
    }

    public function update(int $id, string $name, int $isActive, ?string $icon = null): void
    {
        Database::table('sectors')->where('id', $id)->update([
            'name' => $name,
            'slug' => $this->slug($name),
            'icon' => $icon,
            'is_active' => $isActive,
        ]);
    }

    public function deleteOrDeactivate(int $id): void
    {
        if ($this->clientsCount($id) > 0) {
            Database::table('sectors')->where('id', $id)->update(['is_active' => 0]);
            return;
        }

        Database::table('sectors')->where('id', $id)->delete();
    }

    // int
    public function clientsCount(int $id): int
    {
        return Database::table('clients')->where('sector_id', $id)->count();
    }

    private function ordered(string $sort, string $dir): Builder
    {
        $column = in_array($sort, ['name', 'slug', 'is_active'], true) ? $sort : 'name';
        $query = Database::table('sectors')->orderBy($column, $dir === 'asc' ? 'asc' : 'desc');

        return $column === 'is_active' ? $query->orderBy('name') : $query;
    }

    private function slug(string $name): string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : 'sector-' . time();
    }
}
