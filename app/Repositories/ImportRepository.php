<?php

class ImportRepository
{
    public function allBatches(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT import_batches.*, users.name AS user_name
            FROM import_batches
            LEFT JOIN users ON users.id = import_batches.user_id
            ORDER BY import_batches.id DESC
            LIMIT 50
        ');
        $statement->execute();

        return $statement->fetchAll();
    }

    public function createBatch(
        ?int $userId,
        string $originalFilename,
        string $storedFilename,
        string $fileType,
        string $entityType
    ): int
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO import_batches (user_id, original_filename, stored_filename, file_type, entity_type, status)
            VALUES (:user_id, :original_filename, :stored_filename, :file_type, :entity_type, :status)
        ');

        $statement->execute([
            'user_id' => $userId,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'file_type' => $fileType,
            'entity_type' => $entityType,
            'status' => 'uploaded',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function findBatch(int $id): ?array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('SELECT * FROM import_batches WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $batch = $statement->fetch();

        return $batch ?: null;
    }

    public function errorsForBatch(int $batchId): array
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            SELECT import_errors.*, import_rows.raw_data
            FROM import_errors
            LEFT JOIN import_rows ON import_rows.id = import_errors.import_row_id
            WHERE import_errors.import_batch_id = :batch_id
            ORDER BY import_errors.`row_number` ASC, import_errors.id ASC
            LIMIT 500
        ');
        $statement->execute(['batch_id' => $batchId]);

        return $statement->fetchAll();
    }

    public function markPreviewed(int $id, int $totalRows, array $fieldMapping): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE import_batches
            SET status = :status, total_rows = :total_rows, field_mapping = :field_mapping
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'status' => 'previewed',
            'total_rows' => $totalRows,
            'field_mapping' => json_encode($fieldMapping),
        ]);
    }

    public function updateMapping(int $id, int $totalRows, array $fieldMapping): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE import_batches
            SET total_rows = :total_rows, field_mapping = :field_mapping
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'total_rows' => $totalRows,
            'field_mapping' => json_encode($fieldMapping),
        ]);
    }

    public function markProcessing(int $id): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE import_batches
            SET status = :status, started_at = NOW()
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'status' => 'processing',
        ]);
    }

    public function clearBatchDetails(int $id): void
    {
        $pdo = Database::connect();

        $pdo->prepare('DELETE FROM import_errors WHERE import_batch_id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM import_rows WHERE import_batch_id = :id')->execute(['id' => $id]);
    }

    public function finishBatch(int $id, int $totalRows, int $importedRows, int $skippedRows, int $errorRows): void
    {
        $pdo = Database::connect();
        $status = ($skippedRows > 0 || $errorRows > 0) ? 'partial' : 'completed';

        $statement = $pdo->prepare('
            UPDATE import_batches
            SET status = :status,
                total_rows = :total_rows,
                imported_rows = :imported_rows,
                skipped_rows = :skipped_rows,
                error_rows = :error_rows,
                finished_at = NOW()
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'status' => $status,
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'skipped_rows' => $skippedRows,
            'error_rows' => $errorRows,
        ]);
    }

    public function failBatch(int $id, int $totalRows, int $importedRows, int $skippedRows, int $errorRows): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            UPDATE import_batches
            SET status = :status,
                total_rows = :total_rows,
                imported_rows = :imported_rows,
                skipped_rows = :skipped_rows,
                error_rows = :error_rows,
                finished_at = NOW()
            WHERE id = :id
        ');

        $statement->execute([
            'id' => $id,
            'status' => 'failed',
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'skipped_rows' => $skippedRows,
            'error_rows' => $errorRows,
        ]);
    }

    public function recordIssue(
        int $batchId,
        int $rowNumber,
        array $rawData,
        string $status,
        string $message
    ): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('
            INSERT INTO import_rows (import_batch_id, `row_number`, raw_data, status, message)
            VALUES (:import_batch_id, :row_number, :raw_data, :status, :message)
        ');
        $statement->execute([
            'import_batch_id' => $batchId,
            'row_number' => $rowNumber,
            'raw_data' => json_encode($rawData, JSON_UNESCAPED_UNICODE),
            'status' => $status,
            'message' => $message,
        ]);
        $this->createError($batchId, (int) $pdo->lastInsertId(), $rowNumber, $message);
    }

    public function createError(int $batchId, ?int $rowId, ?int $rowNumber, string $message): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare('
            INSERT INTO import_errors (import_batch_id, import_row_id, `row_number`, error_message)
            VALUES (:import_batch_id, :import_row_id, :row_number, :error_message)
        ');

        $statement->execute([
            'import_batch_id' => $batchId,
            'import_row_id' => $rowId,
            'row_number' => $rowNumber,
            'error_message' => $message,
        ]);
    }
}
