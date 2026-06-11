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
            'user_id' => $userId,
            'file_type' => $fileType,
            'stored_filename' => $fileName,
            'filters' => json_encode($filters),
            'selected_fields' => json_encode($fields),
            'total_rows' => $totalRows,
            'status' => 'completed',
        ]);
    }
}
