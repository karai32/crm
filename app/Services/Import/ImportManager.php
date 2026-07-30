<?php

use Illuminate\Support\Carbon;

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
        if ($extension === 'xlsx' && !class_exists(\OpenSpout\Reader\XLSX\Reader::class)) {
            return ['success' => false, 'message' => 'OpenSpout is required for XLSX imports.'];
        }
        if (!$this->validMime((string) $file['tmp_name'], $extension)) {
            return ['success' => false, 'message' => 'The uploaded file type does not match its extension.'];
        }

        $directory = dirname(__DIR__, 3) . '/storage/imports';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            return ['success' => false, 'message' => 'Could not create the import directory.'];
        }

        $stored = $entityType . '-' . Carbon::now()->format('Y-m-d-H-i-s') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
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

        // Large batches can take minutes of DB round-trips; don't let php.ini's
        // default max_execution_time kill the request mid-import.
        set_time_limit(0);

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
        try {
            foreach ($this->reader->rows($this->path($batch['stored_filename']), $batch['file_type']) as $item) {
                $total++;
                $row = $item['row'];
                $rowNumber = (int) $item['row_number'];

                try {
                    Database::transaction(function () use (
                        $processor,
                        $row,
                        $mapping,
                        $customFields
                    ): void {
                        $mapped = $this->mapping->mapRow($row, $mapping);
                        $processor->process($mapped, $row, $mapping, $customFields);
                    });
                    $imported++;
                } catch (ImportRowException $exception) {
                    // A failed transaction may have rolled back ids held by a
                    // processor cache, so rebuild the processor for the next row.
                    $processor = $this->processor($entityType);
                    $status = $exception->rowStatus();
                    $status === 'skipped' ? $skipped++ : $errors++;
                    $this->imports->recordIssue($batchId, $rowNumber, $row, $status, $exception->getMessage());
                } catch (WriteException $exception) {
                    $processor = $this->processor($entityType);
                    $status = $exception->status() === 409 ? 'skipped' : 'error';
                    $status === 'skipped' ? $skipped++ : $errors++;
                    $this->imports->recordIssue(
                        $batchId,
                        $rowNumber,
                        $row,
                        $status,
                        $exception->getMessage()
                    );
                } catch (PDOException $exception) {
                    $processor = $this->processor($entityType);
                    error_log("Import row {$rowNumber} database error: " . $exception->getMessage());

                    if (Database::violatesConstraint($exception, 'uq_contacts_email')) {
                        $skipped++;
                        $this->imports->recordIssue(
                            $batchId,
                            $rowNumber,
                            $row,
                            'skipped',
                            'A contact with this email already exists.'
                        );
                    } elseif (Database::violatesConstraint($exception, 'uq_clients_commercial_name')) {
                        $skipped++;
                        $this->imports->recordIssue(
                            $batchId,
                            $rowNumber,
                            $row,
                            'skipped',
                            'A client with this commercial name already exists.'
                        );
                    } else {
                        $errors++;
                        $message = Database::isIntegrityViolation($exception)
                            ? 'The row conflicts with existing or related data.'
                            : 'Unable to import this row because of a database error.';
                        $this->imports->recordIssue($batchId, $rowNumber, $row, 'error', $message);
                    }
                } catch (Throwable $exception) {
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
