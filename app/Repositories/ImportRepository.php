<?php

use Illuminate\Database\Query\Builder;

class ImportRepository
{
    // array<{id, user_id, original_filename, stored_filename, file_type, entity_type, status, total_rows, imported_rows, skipped_rows, error_rows, field_mapping, started_at, finished_at, created_at, user_name}>
    public function allBatches(string $sort = 'id', string $dir = 'desc'): array
    {
        return Database::rows($this->orderedBatches($sort, $dir)->limit(50));
    }

    // int
    public function countBatches(): int
    {
        return Database::table('import_batches')->count();
    }

    // array<{id, user_id, original_filename, stored_filename, file_type, entity_type, status, total_rows, imported_rows, skipped_rows, error_rows, field_mapping, started_at, finished_at, created_at, user_name}>
    public function paginateBatches(int $page, int $perPage, string $sort = 'id', string $dir = 'desc'): array
    {
        return Database::rows(
            $this->orderedBatches($sort, $dir)
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
        );
    }

    public function createBatch(
        ?int $userId,
        string $originalFilename,
        string $storedFilename,
        string $fileType,
        string $entityType
    ): int
    {
        return Database::table('import_batches')->insertGetId([
            'user_id' => $userId,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'file_type' => $fileType,
            'entity_type' => $entityType,
            'status' => 'uploaded',
        ]);
    }

    // ?{id, user_id, original_filename, stored_filename, file_type, entity_type, status, total_rows, imported_rows, skipped_rows, error_rows, field_mapping, started_at, finished_at, created_at}
    public function findBatch(int $id): ?array
    {
        return Database::row(Database::table('import_batches')->where('id', $id));
    }

    // array<{id, import_batch_id, import_row_id, row_number, error_message, created_at, raw_data}>
    public function errorsForBatch(int $batchId): array
    {
        return Database::rows(
            Database::table('import_errors')
                ->leftJoin('import_rows', 'import_rows.id', '=', 'import_errors.import_row_id')
                ->select('import_errors.*')
                ->addSelect('import_rows.raw_data')
                ->where('import_errors.import_batch_id', $batchId)
                ->orderBy('import_errors.row_number')
                ->orderBy('import_errors.id')
                ->limit(500)
        );
    }

    public function markPreviewed(int $id, int $totalRows, array $fieldMapping): void
    {
        Database::table('import_batches')->where('id', $id)->update([
            'status' => 'previewed',
            'total_rows' => $totalRows,
            'field_mapping' => json_encode($fieldMapping),
        ]);
    }

    public function updateMapping(int $id, int $totalRows, array $fieldMapping): void
    {
        Database::table('import_batches')->where('id', $id)->update([
            'total_rows' => $totalRows,
            'field_mapping' => json_encode($fieldMapping),
        ]);
    }

    public function claimForProcessing(int $id): bool
    {
        return Database::table('import_batches')
            ->where('id', $id)
            ->whereIn('status', ['uploaded', 'previewed'])
            ->update([
                'status' => 'processing',
                'started_at' => Database::raw('CURRENT_TIMESTAMP'),
            ]) === 1;
    }

    public function clearBatchDetails(int $id): void
    {
        Database::table('import_errors')->where('import_batch_id', $id)->delete();
        Database::table('import_rows')->where('import_batch_id', $id)->delete();
    }

    public function finishBatch(int $id, int $totalRows, int $importedRows, int $skippedRows, int $errorRows): void
    {
        $status = ($skippedRows || $errorRows) ? 'partial' : 'completed';
        $this->finish($id, $status, $totalRows, $importedRows, $skippedRows, $errorRows);
    }

    public function failBatch(int $id, int $totalRows, int $importedRows, int $skippedRows, int $errorRows): void
    {
        $this->finish($id, 'failed', $totalRows, $importedRows, $skippedRows, $errorRows);
    }

    private function finish(
        int $id,
        string $status,
        int $totalRows,
        int $importedRows,
        int $skippedRows,
        int $errorRows
    ): void {
        Database::table('import_batches')->where('id', $id)->update([
            'status' => $status,
            'total_rows' => $totalRows,
            'imported_rows' => $importedRows,
            'skipped_rows' => $skippedRows,
            'error_rows' => $errorRows,
            'finished_at' => Database::raw('CURRENT_TIMESTAMP'),
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
        $rowId = Database::table('import_rows')->insertGetId([
            'import_batch_id' => $batchId,
            'row_number' => $rowNumber,
            'raw_data' => json_encode($rawData, JSON_UNESCAPED_UNICODE),
            'status' => $status,
            'message' => $message,
        ]);
        $this->createError($batchId, $rowId, $rowNumber, $message);
    }

    public function createError(int $batchId, ?int $rowId, ?int $rowNumber, string $message): void
    {
        Database::table('import_errors')->insert([
            'import_batch_id' => $batchId,
            'import_row_id' => $rowId,
            'row_number' => $rowNumber,
            'error_message' => $message,
        ]);
    }

    private function orderedBatches(string $sort, string $dir): Builder
    {
        $allowed = [
            'id' => 'import_batches.id',
            'original_filename' => 'import_batches.original_filename',
            'entity_type' => 'import_batches.entity_type',
            'status' => 'import_batches.status',
        ];

        return Database::table('import_batches')
            ->leftJoin('users', 'users.id', '=', 'import_batches.user_id')
            ->select('import_batches.*')
            ->addSelect('users.name AS user_name')
            ->orderBy($allowed[$sort] ?? 'import_batches.id', $dir === 'asc' ? 'asc' : 'desc');
    }
}
