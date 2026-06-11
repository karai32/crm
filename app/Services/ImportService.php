<?php

class ImportService
{
    private ImportRepository $imports;
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private SectorRepository $sectors;
    private TagRepository $tags;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->imports = new ImportRepository();
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
        $this->sectors = new SectorRepository();
        $this->tags = new TagRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function uploadFile(array $file, ?int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Please choose a CSV or XLSX file.'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return ['success' => false, 'message' => 'Only CSV and XLSX files are supported.'];
        }

        if ($extension === 'xlsx' && !class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return [
                'success' => false,
                'message' => 'PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet',
            ];
        }

        $importDir = dirname(__DIR__, 2) . '/storage/imports';

        if (!is_dir($importDir)) {
            mkdir($importDir, 0777, true);
        }

        $storedFilename = 'contacts-' . date('Y-m-d-H-i-s') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $importDir . '/' . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Could not save uploaded file.'];
        }

        $batchId = $this->imports->createBatch($userId, $file['name'], $storedFilename, $extension);

        return ['success' => true, 'batch_id' => $batchId];
    }

    public function preview(int $batchId): ?array
    {
        $batch = $this->imports->findBatch($batchId);

        if ($batch === null) {
            return null;
        }

        $path = $this->filePath($batch['stored_filename']);
        $parsed = $this->readImportFile($batch, 10);

        $this->imports->markPreviewed($batchId, $parsed['total_rows'], $parsed['headers']);

        return [
            'batch' => $batch,
            'headers' => $parsed['headers'],
            'rows' => $parsed['rows'],
            'total_rows' => $parsed['total_rows'],
            'system_fields' => $this->systemFields(),
            'suggested_mapping' => $this->suggestMapping($parsed['headers']),
        ];
    }

    public function process(int $batchId, array $mapping, array $customFieldSettings = []): ?array
    {
        $batch = $this->imports->findBatch($batchId);

        if ($batch === null) {
            return null;
        }

        $totalRows = $this->countImportRows($batch);
        $mapping = $this->cleanMapping($mapping);
        $customFieldSettings = $this->cleanCustomFieldSettings($customFieldSettings);

        $this->imports->markProcessing($batchId);
        $this->imports->clearBatchDetails($batchId);
        $this->imports->updateMapping($batchId, $totalRows, [
            'mapping' => $mapping,
            'custom_fields' => $customFieldSettings,
        ]);

        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $currentRowNumber = null;
        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            foreach ($this->importRows($batch) as $rowData) {
                $rowNumber = $rowData['row_number'];
                $currentRowNumber = $rowNumber;
                $row = $rowData['row'];
                $mappedRow = $this->mapRow($row, $mapping);
                $email = trim($mappedRow['email'] ?? '');
                $firstName = trim($mappedRow['first_name'] ?? '');

                if ($firstName === '') {
                    $message = 'First name is required.';
                    $rowId = $this->imports->createRow($batchId, $rowNumber, $row, 'error', null, $message);
                    $this->imports->createError($batchId, $rowId, $rowNumber, $message);
                    $errors++;
                    continue;
                }

                if ($email !== '' && $this->contacts->emailExists($email)) {
                    $message = 'Duplicate email: ' . $email;
                    $rowId = $this->imports->createRow($batchId, $rowNumber, $row, 'skipped', null, $message);
                    $this->imports->createError($batchId, $rowId, $rowNumber, $message);
                    $skipped++;
                    continue;
                }

                $contactId = $this->contacts->create([
                    'first_name' => $firstName,
                    'last_name'  => $this->emptyToNull($mappedRow['last_name'] ?? ''),
                    'email'      => $this->emptyToNull($email),
                    'phone'      => $this->emptyToNull($mappedRow['phone'] ?? ''),
                    'is_company' => $this->boolValue($mappedRow['is_company'] ?? ''),
                ]);

                $clientId = $this->clientIdFromMappedRow($mappedRow);
                $tagIds = $this->tagIdsFromMappedRow($mappedRow);

                if ($clientId !== null) {
                    $this->contacts->syncClients($contactId, [$clientId]);
                }

                if (!empty($tagIds)) {
                    $this->contacts->syncTags($contactId, $tagIds);

                    if ($clientId !== null) {
                        $this->clients->addTags($clientId, $tagIds);
                    }
                }

                $this->saveCustomFieldValues($row, $mapping, $customFieldSettings, $contactId, $clientId);

                $this->imports->createRow($batchId, $rowNumber, $row, 'imported', $contactId, 'Imported', $clientId);
                $imported++;
            }

            $this->imports->finishBatch($batchId, $totalRows, $imported, $skipped, $errors);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors = 1;
            $message = 'Import failed' . ($currentRowNumber !== null ? ' at row ' . $currentRowNumber : '') . ': ' . $exception->getMessage();
            $this->imports->failBatch($batchId, $totalRows, 0, 0, $errors);
            $this->imports->createError($batchId, null, $currentRowNumber, $message);

            return [
                'batch' => $this->imports->findBatch($batchId),
                'imported' => 0,
                'skipped' => 0,
                'errors' => $errors,
                'total_rows' => $totalRows,
                'failed' => true,
                'message' => $message,
            ];
        }

        return [
            'batch' => $this->imports->findBatch($batchId),
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_rows' => $totalRows,
        ];
    }

    private function readCsv(string $path, ?int $limit = null): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $rows = [];
        $totalRows = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $totalRows++;
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }

            if ($limit === null || count($rows) < $limit) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows, 'total_rows' => $totalRows];
    }

    private function readImportFile(array $batch, ?int $limit = null): array
    {
        $path = $this->filePath($batch['stored_filename']);
        $fileType = $batch['file_type'] ?? strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($fileType === 'xlsx') {
            return $this->readXlsx($path, $limit);
        }

        return $this->readCsv($path, $limit);
    }

    private function countImportRows(array $batch): int
    {
        $count = 0;

        foreach ($this->importRows($batch) as $rowData) {
            $count++;
        }

        return $count;
    }

    private function importRows(array $batch): Generator
    {
        $path = $this->filePath($batch['stored_filename']);
        $fileType = $batch['file_type'] ?? strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($fileType === 'xlsx') {
            yield from $this->xlsxRows($path);
            return;
        }

        yield from $this->csvRows($path);
    }

    private function csvRows(string $path): Generator
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return;
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            return;
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $rowNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($data === [null] || $data === false) {
                continue;
            }

            $row = [];
            $hasValue = false;

            foreach ($headers as $index => $header) {
                $value = $data[$index] ?? '';
                $row[$header] = $value;

                if (trim((string) $value) !== '') {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                continue;
            }

            yield [
                'row_number' => $rowNumber,
                'row' => $row,
            ];
        }

        fclose($handle);
    }

    private function xlsxRows(string $path): Generator
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = [];

        foreach ($sheet->getRowIterator() as $sheetRow) {
            $rowNumber = $sheetRow->getRowIndex();
            $cells = [];

            foreach ($sheetRow->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            if ($rowNumber === 1) {
                $headers = array_map(fn ($header) => trim((string) $header), $cells);

                if (!empty($headers)) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                }

                continue;
            }

            if (empty($headers)) {
                continue;
            }

            $row = [];
            $hasValue = false;

            foreach ($headers as $index => $header) {
                $value = $cells[$index] ?? '';
                $row[$header] = $value;

                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                continue;
            }

            yield [
                'row_number' => $rowNumber,
                'row' => $row,
            ];
        }

        $spreadsheet->disconnectWorksheets();
    }

    private function readXlsx(string $path, ?int $limit = null): array
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $headers = [];
        $totalRows = 0;

        foreach ($sheet->getRowIterator() as $sheetRow) {
            $rowNumber = $sheetRow->getRowIndex();
            $cells = [];

            foreach ($sheetRow->getCellIterator() as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            if ($rowNumber === 1) {
                $headers = array_map(fn ($header) => trim((string) $header), $cells);

                if (!empty($headers)) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                }

                continue;
            }

            if (empty($headers)) {
                continue;
            }

            $hasValue = false;
            $row = [];

            foreach ($headers as $index => $header) {
                $value = trim((string) ($cells[$index] ?? ''));
                $row[$header] = $value;

                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if (!$hasValue) {
                continue;
            }

            $totalRows++;

            if ($limit === null || count($rows) < $limit) {
                $rows[] = $row;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return ['headers' => $headers, 'rows' => $rows, 'total_rows' => $totalRows];
    }

    private function filePath(string $storedFilename): string
    {
        return dirname(__DIR__, 2) . '/storage/imports/' . $storedFilename;
    }

    private function emptyToNull(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function systemFields(): array
    {
        return [
            'first_name'  => 'first_name',
            'last_name'   => 'last_name',
            'email'       => 'email',
            'phone'       => 'phone',
            'is_company'  => 'is_company',
            'client_name' => 'client_name',
            'sector_name' => 'sector_name',
            'tags'        => 'tags',
        ];
    }

    private function suggestMapping(array $headers): array
    {
        $suggestions = [
            'name'          => 'first_name',
            'first name'    => 'first_name',
            'first_name'    => 'first_name',
            'last name'     => 'last_name',
            'last_name'     => 'last_name',
            'email'         => 'email',
            'e-mail'        => 'email',
            'phone'         => 'phone',
            'is_company'    => 'is_company',
            'company indicator' => 'is_company',
            'company'       => 'client_name',
            'client'        => 'client_name',
            'client_name'   => 'client_name',
            'client name'   => 'client_name',
            'sector'        => 'sector_name',
            'sector_name'   => 'sector_name',
            'sector name'   => 'sector_name',
            'tags'          => 'tags',
            'tag'           => 'tags',
        ];

        $mapping = [];

        foreach ($headers as $header) {
            $key = $this->lower(trim($header));
            $mapping[$header] = $suggestions[$key] ?? '';
        }

        return $mapping;
    }

    private function cleanMapping(array $mapping): array
    {
        $allowedFields = array_keys($this->systemFields());
        $clean = [];

        foreach ($mapping as $csvColumn => $systemField) {
            $csvColumn = trim((string) $csvColumn);
            $systemField = trim((string) $systemField);

            if ($csvColumn !== '' && (in_array($systemField, $allowedFields, true) || $systemField === '__custom')) {
                $clean[$csvColumn] = $systemField;
            }
        }

        return $clean;
    }

    private function mapRow(array $row, array $mapping): array
    {
        $mappedRow = [];

        foreach ($mapping as $csvColumn => $systemField) {
            if ($systemField === '__custom') {
                continue;
            }

            $mappedRow[$systemField] = trim((string) ($row[$csvColumn] ?? ''));
        }

        return $mappedRow;
    }

    private function clientIdFromMappedRow(array $mappedRow): ?int
    {
        $clientName = trim($mappedRow['client_name'] ?? '');

        if ($clientName === '') {
            return null;
        }

        $sectorId = null;
        $sectorName = trim($mappedRow['sector_name'] ?? '');

        if ($sectorName !== '') {
            $sector = $this->sectors->findByName($sectorName);
            $sectorId = $sector ? (int) $sector['id'] : $this->sectors->create($sectorName);
        }

        $client = $this->clients->findByCommercialName($clientName);

        if ($client) {
            return (int) $client['id'];
        }

        return $this->clients->create([
            'commercial_name' => $clientName,
            'legal_name' => null,
            'cif' => null,
            'address' => null,
            'postal_code' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'sector_id' => $sectorId,
            'website' => null,
            'notes' => null,
        ]);
    }

    private function tagIdsFromMappedRow(array $mappedRow): array
    {
        $tagsValue = trim($mappedRow['tags'] ?? '');

        if ($tagsValue === '') {
            return [];
        }

        $tagNames = preg_split('/[,;|]/', $tagsValue);
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tag = $this->tags->findByName($tagName);
            $tagIds[] = $tag ? (int) $tag['id'] : $this->tags->create($tagName, null);
        }

        return array_values(array_unique($tagIds));
    }

    private function boolValue(string $value): int
    {
        $value = $this->lower(trim($value));

        return in_array($value, ['1', 'yes', 'true'], true) ? 1 : 0;
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }

    private function cleanCustomFieldSettings(array $settings): array
    {
        $clean = [];
        $allowedEntities = ['contact', 'client'];
        $allowedTypes = ['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'];

        foreach ($settings as $csvColumn => $fieldSettings) {
            if (!is_array($fieldSettings)) {
                continue;
            }

            $entityType = $fieldSettings['entity_type'] ?? 'contact';
            $fieldType = $fieldSettings['field_type'] ?? 'text';

            $clean[$csvColumn] = [
                'entity_type' => in_array($entityType, $allowedEntities, true) ? $entityType : 'contact',
                'field_type' => in_array($fieldType, $allowedTypes, true) ? $fieldType : 'text',
            ];
        }

        return $clean;
    }

    private function saveCustomFieldValues(array $row, array $mapping, array $customFieldSettings, int $contactId, ?int $clientId): void
    {
        foreach ($mapping as $csvColumn => $systemField) {
            if ($systemField !== '__custom') {
                continue;
            }

            $settings = $customFieldSettings[$csvColumn] ?? ['entity_type' => 'contact', 'field_type' => 'text'];
            $entityType = $settings['entity_type'];
            $entityId = $entityType === 'client' ? $clientId : $contactId;

            if ($entityId === null) {
                continue;
            }

            $field = $this->findOrCreateCustomField($csvColumn, $entityType, $settings['field_type']);
            $rawValue = $row[$csvColumn] ?? '';

            if ($field['field_type'] === 'checkbox') {
                $rawValue = $this->boolValue((string) $rawValue);
            }

            $this->customFields->saveValues(
                $entityType,
                $entityId,
                [$field],
                [(int) $field['id'] => $rawValue]
            );
        }
    }

    private function findOrCreateCustomField(string $csvColumn, string $entityType, string $fieldType): array
    {
        $slug = $this->makeSlug($csvColumn);
        $field = $this->customFields->findByEntityAndSlug($entityType, $slug);

        if ($field !== null) {
            return $field;
        }

        $id = $this->customFields->create([
            'entity_type' => $entityType,
            'name' => $csvColumn,
            'slug' => $slug,
            'field_type' => $fieldType,
            'is_required' => 0,
            'is_filterable' => 1,
            'sort_order' => 0,
        ]);

        return $this->customFields->find($id) ?? [
            'id' => $id,
            'entity_type' => $entityType,
            'name' => $csvColumn,
            'slug' => $slug,
            'field_type' => $fieldType,
        ];
    }

    private function makeSlug(string $value): string
    {
        $slug = $this->lower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'custom-field-' . time();
    }
}
