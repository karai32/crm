<?php

class ExportRepository
{
    public function createCompletedCsvExport(?int $userId, array $filters, array $fields, int $totalRows, string $fileName): void
    {
        $this->createCompletedExport($userId, 'csv', $filters, $fields, $totalRows, $fileName);
    }

    public function createCompletedExport(?int $userId, string $fileType, array $filters, array $fields, int $totalRows, string $fileName): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO export_batches (
                user_id, file_type, stored_filename, filters, selected_fields, total_rows, status, finished_at
            ) VALUES (
                :user_id, :file_type, :stored_filename, :filters, :selected_fields, :total_rows, :status, NOW()
            )
        ');

        $statement->execute([
            'user_id'         => $userId,
            'file_type'       => $fileType,
            'stored_filename' => $fileName,
            'filters'         => json_encode($filters),
            'selected_fields' => json_encode($fields),
            'total_rows'      => $totalRows,
            'status'          => 'completed',
        ]);
    }

    public function recentExports(int $limit = 15): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT eb.*, u.name AS user_name
            FROM export_batches eb
            LEFT JOIN users u ON u.id = eb.user_id
            ORDER BY eb.id DESC
            LIMIT :limit
        ');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
