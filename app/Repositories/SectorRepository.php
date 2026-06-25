<?php

class SectorRepository
{
    public function all(string $sort = 'name', string $dir = 'asc'): array
    {
        $pdo = Database::connect();

        $allowed  = ['name' => 'name', 'slug' => 'slug', 'is_active' => 'is_active'];
        $orderCol = $allowed[$sort] ?? 'name';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        $orderSql = $sort === 'is_active'
            ? "is_active {$orderDir}, name ASC"
            : "{$orderCol} {$orderDir}";

        $statement = $pdo->prepare("SELECT * FROM sectors ORDER BY {$orderSql}");
        $statement->execute();

        return $statement->fetchAll();
    }

    public function active(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM sectors WHERE is_active = 1 ORDER BY name ASC');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function filter(string $name = '', mixed $isActive = ''): array
    {
        $pdo = Database::connect();
        $where = [];
        $params = [];

        if ($name !== '') {
            $where[] = 'name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }
        if ($isActive !== '' && $isActive !== null) {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = (int) $isActive;
        }

        $sql = 'SELECT * FROM sectors'
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY is_active DESC, name ASC';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM sectors WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $sector = $statement->fetch();

        return $sector ?: null;
    }

    public function findByName(string $name): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM sectors WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $sector = $statement->fetch();

        return $sector ?: null;
    }

    public function search(string $query, int $limit = 50): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT id, name, slug, icon, is_active
            FROM sectors
            WHERE name LIKE :query
               OR slug LIKE :query
            ORDER BY is_active DESC, name ASC
            LIMIT :limit
        ');

        $statement->bindValue('query', '%' . $query . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(string $name): int
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO sectors (name, slug, is_active)
            VALUES (:name, :slug, 1)
        ');

        $statement->execute([
            'name' => $name,
            'slug' => $this->makeSlug($name),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, string $name, int $isActive, ?string $icon = null): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE sectors
            SET name = :name, slug = :slug, icon = :icon, is_active = :is_active
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'name' => $name,
            'slug' => $this->makeSlug($name),
            'icon' => $icon,
            'is_active' => $isActive,
        ]);
    }

    public function deleteOrDeactivate(int $id): void
    {
        if ($this->clientsCount($id) > 0) {
            $this->deactivate($id);
            return;
        }

        $pdo = Database::connect();

        $statement = $pdo->prepare('DELETE FROM sectors WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function clientsCount(int $id): int
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM clients WHERE sector_id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return (int) ($row['total'] ?? 0);
    }

    private function deactivate(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('UPDATE sectors SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'sector-' . time();
    }
}
