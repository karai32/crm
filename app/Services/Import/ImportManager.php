<?php

class ImportManager
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    private ImportRepository $imports;
    private ImportFileReader $reader;
    private ImportMapping $mapping;

    public function __construct()
    {
        $this->imports = new ImportRepository();
        $this->reader = new ImportFileReader();
        $this->mapping = new ImportMapping();
    }

    public function upload(array $file, ?int $userId, string $entityType): array
    {
        $entityType = in_array($entityType, ['contacts', 'clients'], true) ? $entityType : 'contacts';
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please choose a CSV or XLSX file.'];
        }
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'The import file must be smaller than 20 MB.'];
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return ['success' => false, 'message' => 'Only CSV and XLSX files are supported.'];
        }
        if ($extension === 'xlsx' && !class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return ['success' => false, 'message' => 'PhpSpreadsheet is required for XLSX imports.'];
        }
        if (!$this->validMime((string) $file['tmp_name'], $extension)) {
            return ['success' => false, 'message' => 'The uploaded file type does not match its extension.'];
        }

        $directory = dirname(__DIR__, 3) . '/storage/imports';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            return ['success' => false, 'message' => 'Could not create the import directory.'];
        }

        $stored = $entityType . '-' . date('Y-m-d-H-i-s') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . '/' . $stored;
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            return ['success' => false, 'message' => 'Could not save uploaded file.'];
        }

        try {
            $id = $this->imports->createBatch(
                $userId,
                (string) $file['name'],
                $stored,
                $extension,
                $entityType
            );
        } catch (Throwable $exception) {
            @unlink($target);
            throw $exception;
        }

        return ['success' => true, 'batch_id' => $id];
    }

    public function preview(int $batchId): ?array
    {
        $batch = $this->imports->findBatch($batchId);
        if ($batch === null || !in_array($batch['status'], ['uploaded', 'previewed'], true)) {
            return null;
        }
        $path = $this->path((string) $batch['stored_filename']);
        if (!is_file($path)) {
            return null;
        }

        $parsed = $this->reader->preview(
            $path,
            (string) $batch['file_type']
        );
        $this->imports->markPreviewed($batchId, $parsed['total_rows'], $parsed['headers']);

        return [
            'batch' => array_merge($batch, ['status' => 'previewed']),
            'headers' => $parsed['headers'],
            'rows' => $parsed['rows'],
            'total_rows' => $parsed['total_rows'],
            'system_fields' => $this->mapping->fields($batch['entity_type'] ?? 'contacts'),
            'suggested_mapping' => $this->mapping->suggest(
                $parsed['headers'],
                $batch['entity_type'] ?? 'contacts'
            ),
        ];
    }

    public function process(int $batchId, array $mapping, array $customFields): ?array
    {
        $batch = $this->imports->findBatch($batchId);
        if ($batch === null) {
            return null;
        }
        if (!in_array($batch['status'], ['uploaded', 'previewed'], true)) {
            return $this->result($batch, true, 'This import has already been processed.');
        }

        $entityType = $batch['entity_type'] ?? 'contacts';
        $mapping = $this->mapping->clean($mapping, $entityType);
        $customFields = $this->mapping->cleanCustomFields($customFields);
        $required = $entityType === 'clients' ? 'commercial_name' : 'full_name';
        if (!in_array($required, $mapping, true)) {
            return $this->result($batch, true, "Map a column to {$required} before importing.");
        }

        $processor = $this->processor($entityType);

        if (!$this->imports->claimForProcessing($batchId)) {
            return $this->result($this->imports->findBatch($batchId) ?? $batch, true, 'This import is already processing.');
        }
        $this->imports->clearBatchDetails($batchId);
        $this->imports->updateMapping($batchId, 0, [
            'mapping' => $mapping,
            'custom_fields' => $customFields,
        ]);

        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $pdo = Database::connect();

        try {
            foreach ($this->reader->rows($this->path($batch['stored_filename']), $batch['file_type']) as $item) {
                $total++;
                $row = $item['row'];
                $rowNumber = (int) $item['row_number'];

                try {
                    $pdo->beginTransaction();
                    $mapped = $this->mapping->mapRow($row, $mapping);
                    $processor->process($mapped, $row, $mapping, $customFields);
                    $pdo->commit();
                    $imported++;
                } catch (ImportRowException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $status = $exception->rowStatus();
                    $status === 'skipped' ? $skipped++ : $errors++;
                    $this->imports->recordIssue($batchId, $rowNumber, $row, $status, $exception->getMessage());
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $processor = $this->processor($entityType);
                    $errors++;
                    error_log("Import row {$rowNumber} failed: " . $exception->getMessage());
                    $this->imports->recordIssue(
                        $batchId,
                        $rowNumber,
                        $row,
                        'error',
                        'Unable to import this row: [' . get_class($exception) . '] ' . $exception->getMessage()
                    );
                }
            }

            $this->imports->finishBatch($batchId, $total, $imported, $skipped, $errors);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->imports->failBatch($batchId, $total, $imported, $skipped, $errors + 1);
            $this->imports->createError($batchId, null, null, 'Import failed: ' . $exception->getMessage());
            return $this->result($this->imports->findBatch($batchId) ?? $batch, true, 'Import failed.');
        }

        return [
            'batch' => $this->imports->findBatch($batchId),
            'entity_type' => $entityType,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_rows' => $total,
        ];
    }

    private function validMime(string $path, string $extension): bool
    {
        if (!function_exists('finfo_open')) {
            return true;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = $extension === 'xlsx'
            ? ['application/zip', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            : ['text/plain', 'text/csv', 'application/csv', 'application/octet-stream', 'application/vnd.ms-excel'];
        return $mime === false || in_array($mime, $allowed, true);
    }

    private function path(string $filename): string
    {
        return dirname(__DIR__, 3) . '/storage/imports/' . basename($filename);
    }

    private function processor(string $entityType): AbstractImportProcessor
    {
        return $entityType === 'clients' ? new ClientImportProcessor() : new ContactImportProcessor();
    }

    private function result(array $batch, bool $failed, string $message): array
    {
        return [
            'batch' => $batch,
            'entity_type' => $batch['entity_type'] ?? 'contacts',
            'imported' => (int) ($batch['imported_rows'] ?? 0),
            'skipped' => (int) ($batch['skipped_rows'] ?? 0),
            'errors' => (int) ($batch['error_rows'] ?? 0),
            'total_rows' => (int) ($batch['total_rows'] ?? 0),
            'failed' => $failed,
            'message' => $message,
        ];
    }
}
