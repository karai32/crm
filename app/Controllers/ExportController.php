<?php

class ExportController
{
    private ContactRepository $contacts;
    private ExportRepository $exports;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->exports = new ExportRepository();
    }

    public function contactsForm(): void
    {
        Auth::requirePermission('exports.use');

        $filters = $this->filtersFromRequest();
        $defaultFields = ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at'];

        View::render('exports/contacts', [
            'title'          => 'Export contacts',
            'styles'         => ['contacts.css'],
            'fields'         => $this->contacts->exportableFields(),
            'selectedFields' => $defaultFields,
            'filters'        => $filters,
            'actionUrl'      => Auth::url('/contacts/export-csv'),
            'buttonText'     => 'Download CSV',
            'exportTitle'    => 'Export contacts to CSV',
            'error'          => null,
        ]);
    }

    public function contactsXlsxForm(): void
    {
        Auth::requirePermission('exports.use');

        $filters = $this->filtersFromRequest();
        $defaultFields = ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at'];
        $error = class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)
            ? null
            : 'PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet';

        View::render('exports/contacts', [
            'title'          => 'Export contacts XLSX',
            'styles'         => ['contacts.css'],
            'fields'         => $this->contacts->exportableFields(),
            'selectedFields' => $defaultFields,
            'filters'        => $filters,
            'actionUrl'      => Auth::url('/exports/contacts-xlsx'),
            'buttonText'     => 'Download XLSX',
            'exportTitle'    => 'Export contacts to XLSX',
            'error'          => $error,
        ]);
    }

    public function contactsCsv(): void
    {
        Auth::requirePermission('exports.use');

        $filters = $this->filtersFromPost();
        $fields = $this->selectedFieldsFromRequest();
        $totalRows = $this->contacts->countAll($filters);
        $fileName = 'contacts-' . date('Y-m-d-H-i-s') . '.csv';
        $user = Auth::user();

        $this->exports->createCompletedCsvExport(
            $user['id'] ?? null,
            $filters,
            $fields,
            $totalRows,
            $fileName
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $fields);
        $statement = $this->contacts->exportStatement($filters, $fields);

        while ($row = $statement->fetch()) {
            $csvRow = [];

            foreach ($fields as $field) {
                $csvRow[] = $row[$field] ?? '';
            }

            fputcsv($output, $csvRow);
        }

        fclose($output);
        exit;
    }

    public function contactsXlsx(): void
    {
        Auth::requirePermission('exports.use');

        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            View::render('exports/contacts', [
                'title'          => 'Export contacts XLSX',
                'styles'         => ['contacts.css'],
                'fields'         => $this->contacts->exportableFields(),
                'selectedFields' => $this->selectedFieldsFromRequest(),
                'filters'        => $this->filtersFromPost(),
                'actionUrl'      => Auth::url('/exports/contacts-xlsx'),
                'buttonText'     => 'Download XLSX',
                'exportTitle'    => 'Export contacts to XLSX',
                'error'          => 'PhpSpreadsheet is not installed. Run: composer require phpoffice/phpspreadsheet',
            ]);
            return;
        }

        $filters = $this->filtersFromPost();
        $fields = $this->selectedFieldsFromRequest();
        $totalRows = $this->contacts->countAll($filters);
        $fileName = 'contacts-' . date('Y-m-d-H-i-s') . '.xlsx';
        $user = Auth::user();

        $this->exports->createCompletedExport(
            $user['id'] ?? null,
            'xlsx',
            $filters,
            $fields,
            $totalRows,
            $fileName
        );

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Contacts');

        foreach ($fields as $columnIndex => $field) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . '1';
            $sheet->setCellValue($cell, $field);
        }

        $statement = $this->contacts->exportStatement($filters, $fields);
        $rowIndex = 2;

        while ($row = $statement->fetch()) {
            foreach ($fields as $columnIndex => $field) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowIndex;
                $sheet->setCellValue($cell, $row[$field] ?? '');
            }

            $rowIndex++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function filtersFromRequest(): array
    {
        return [
            'first_name' => trim($_GET['first_name'] ?? ''),
            'last_name' => trim($_GET['last_name'] ?? ''),
            'email' => trim($_GET['email'] ?? ''),
            'phone' => trim($_GET['phone'] ?? ''),
            'is_company' => trim($_GET['is_company'] ?? ''),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'sector_id' => (int) ($_GET['sector_id'] ?? 0),
            'tag_id' => (int) ($_GET['tag_id'] ?? 0),
            'tag_ids' => $this->tagIdsFromArray($_GET),
            'country' => trim($_GET['country'] ?? ''),
            'province' => trim($_GET['province'] ?? ''),
            'created_from' => trim($_GET['created_from'] ?? ''),
            'created_to' => trim($_GET['created_to'] ?? ''),
            'updated_from' => trim($_GET['updated_from'] ?? ''),
            'updated_to' => trim($_GET['updated_to'] ?? ''),
            'custom_fields' => $this->customFieldFilters($_GET['custom_fields'] ?? []),
        ];
    }

    private function filtersFromPost(): array
    {
        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'is_company' => trim($_POST['is_company'] ?? ''),
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'sector_id' => (int) ($_POST['sector_id'] ?? 0),
            'tag_id' => (int) ($_POST['tag_id'] ?? 0),
            'tag_ids' => $this->tagIdsFromArray($_POST),
            'country' => trim($_POST['country'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'created_from' => trim($_POST['created_from'] ?? ''),
            'created_to' => trim($_POST['created_to'] ?? ''),
            'updated_from' => trim($_POST['updated_from'] ?? ''),
            'updated_to' => trim($_POST['updated_to'] ?? ''),
            'custom_fields' => $this->customFieldFilters($_POST['custom_fields'] ?? []),
        ];
    }

    private function customFieldFilters(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $clean = [];

        foreach ($values as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);

            if ($fieldId > 0 && $value !== '') {
                $clean[$fieldId] = $value;
            }
        }

        return $clean;
    }

    private function tagIdsFromArray(array $source): array
    {
        $tagIds = $source['tag_ids'] ?? [];

        if (!empty($source['tag_id'])) {
            if (!is_array($tagIds)) {
                $tagIds = [];
            }

            $tagIds[] = $source['tag_id'];
        }

        if (!is_array($tagIds)) {
            return [];
        }

        $tagIds = array_map('intval', $tagIds);
        $tagIds = array_filter($tagIds, fn ($id) => $id > 0);

        return array_values(array_unique($tagIds));
    }

    private function selectedFieldsFromRequest(): array
    {
        $allowedFields = array_keys($this->contacts->exportableFields());
        $fields = $_POST['fields'] ?? [];

        if (!is_array($fields)) {
            return ['id'];
        }

        $fields = array_values(array_intersect($fields, $allowedFields));

        return empty($fields) ? ['id'] : $fields;
    }
}
