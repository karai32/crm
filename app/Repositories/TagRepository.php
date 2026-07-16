<?php

class TagRepository
{
    public function first(int $limit = 50): array
    {
        $statement = Database::connect()->prepare('SELECT * FROM tags ORDER BY name ASC LIMIT :limit');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function all(string $sort = 'name', string $dir = 'asc'): array
    {
        $pdo = Database::connect();

        $allowed  = ['name' => 'name', 'slug' => 'slug'];
        $orderCol = $allowed[$sort] ?? 'name';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';

        $statement = $pdo->prepare("SELECT * FROM tags ORDER BY {$orderCol} {$orderDir}");
        $statement->execute();

        return $statement->fetchAll();
    }

    public function count(): int
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tags');
        $stmt->execute();

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function paginate(int $page, int $perPage, string $sort = 'name', string $dir = 'asc'): array
    {
        $pdo      = Database::connect();
        $allowed  = ['name' => 'name', 'slug' => 'slug'];
        $orderCol = $allowed[$sort] ?? 'name';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        $offset   = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("SELECT * FROM tags ORDER BY {$orderCol} {$orderDir} LIMIT :limit OFFSET :offset");
        $stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function filter(string $name = ''): array
    {
        $pdo = Database::connect();
        $sql = 'SELECT * FROM tags';
        $params = [];

        if ($name !== '') {
            $sql .= ' WHERE name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        $statement = $pdo->prepare($sql . ' ORDER BY name ASC');
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM tags WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $tag = $statement->fetch();

        return $tag ?: null;
    }

    public function findByName(string $name): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM tags WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $tag = $statement->fetch();

        return $tag ?: null;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT id, name, slug, color
            FROM tags
            WHERE name LIKE :query
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ');

        $statement->bindValue('query', '%' . $query . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(string $name, ?string $color): int
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO tags (name, slug, color)
            VALUES (:name, :slug, :color)
        ');

        $statement->execute([
            'name' => $name,
            'slug' => $this->slug($name),
            'color' => $color,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, string $name, ?string $color): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE tags
            SET name = :name, slug = :slug, color = :color
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'name' => $name,
            'slug' => $this->slug($name),
            'color' => $color,
        ]);
    }

    public function delete(int $id): void
    {
        $pdo = Database::connect();

        // Contact and client tag links use ON DELETE CASCADE in the schema.
        $statement = $pdo->prepare('DELETE FROM tags WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function slug(string $name): string
    {
        $slug = Slugger::make($name);

        return $slug !== '' ? $slug : 'tag-' . time();
    }
}
