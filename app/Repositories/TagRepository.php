<?php

class TagRepository
{
    public function all(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM tags ORDER BY name ASC');
        $statement->execute();

        return $statement->fetchAll();
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

    public function search(string $query, int $limit = 20): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT id, name, slug, color
            FROM tags
            WHERE name LIKE :query
            ORDER BY name ASC
            LIMIT :limit
        ');

        $statement->bindValue('query', '%' . $query . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
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
            'slug' => $this->makeSlug($name),
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
            'slug' => $this->makeSlug($name),
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

    private function makeSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'tag-' . time();
    }
}
