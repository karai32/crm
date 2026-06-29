<?php

class ExportRepository
{
    public function start(
        ?int $userId,
        string $entityType,
        string $fileType,
        array $filters,
        array $fields,
        string $fileName
    ): int
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO export_batches (
                user_id, entity_type, file_type, stored_filename, filters, selected_fields, status
            ) VALUES (
                :user_id, :entity_type, :file_type, :stored_filename, :filters, :selected_fields, :status
            )
        ');

        $statement->execute([
            'user_id'         => $userId,
            'entity_type'     => $entityType,
            'file_type'       => $fileType,
            'stored_filename' => $fileName,
            'filters'         => json_encode($filters),
            'selected_fields' => json_encode($fields),
            'status'          => 'processing',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function complete(int $id, int $totalRows): void
    {
        $statement = Database::connect()->prepare('
            UPDATE export_batches
            SET status = :status, total_rows = :total_rows, finished_at = NOW()
            WHERE id = :id
        ');
        $statement->execute(['id' => $id, 'status' => 'completed', 'total_rows' => $totalRows]);
    }

    public function fail(int $id): void
    {
        $statement = Database::connect()->prepare('
            UPDATE export_batches
            SET status = :status, finished_at = NOW()
            WHERE id = :id
        ');
        $statement->execute(['id' => $id, 'status' => 'failed']);
    }

    public function count(): int
    {
        $pdo  = Database::connect();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM export_batches');
        $stmt->execute();

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function paginate(int $page, int $perPage, string $sort = 'id', string $dir = 'desc'): array
    {
        $pdo     = Database::connect();
        $allowed = [
            'id'              => 'eb.id',
            'entity_type'     => 'eb.entity_type',
            'stored_filename' => 'eb.stored_filename',
        ];
        $orderCol = $allowed[$sort] ?? 'eb.id';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';
        $offset   = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT eb.*, u.name AS user_name
            FROM export_batches eb
            LEFT JOIN users u ON u.id = eb.user_id
            ORDER BY {$orderCol} {$orderDir}
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function recentExports(int $limit = 15, string $sort = 'id', string $dir = 'desc'): array
    {
        $pdo = Database::connect();
        $allowed = [
            'id'              => 'eb.id',
            'entity_type'     => 'eb.entity_type',
            'stored_filename' => 'eb.stored_filename',
        ];
        $orderCol = $allowed[$sort] ?? 'eb.id';
        $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';

        $statement = $pdo->prepare("
            SELECT eb.*, u.name AS user_name
            FROM export_batches eb
            LEFT JOIN users u ON u.id = eb.user_id
            ORDER BY {$orderCol} {$orderDir}
            LIMIT :limit
        ");
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
