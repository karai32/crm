<?php

class ExportController
{
    private ExportService $service;
    private ExportRepository $exports;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->service      = new ExportService();
        $this->exports      = new ExportRepository();
        $this->customFields = new CustomFieldRepository();
    }

    // ── Hub page ───────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requirePermission('exports.use');

        $entity = in_array($_GET['entity'] ?? '', ['contacts', 'clients'], true)
            ? $_GET['entity']
            : 'contacts';

        $cfEntityType = $entity === 'contacts' ? 'contact' : 'client';
        $customFields = $this->customFields->fieldsForEntity($cfEntityType);

        $fieldDefs = $entity === 'contacts'
            ? $this->service->contactsFieldDefs($customFields)
            : $this->service->clientsFieldDefs($customFields);

        $defaultFields = $entity === 'contacts'
            ? ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at']
            : ['id', 'commercial_name', 'legal_name', 'city', 'country', 'created_at'];

        View::render('exports/index', [
            'title'         => 'Export data',
            'styles'        => ['data.css'],
            'entity'        => $entity,
            'fieldDefs'     => $fieldDefs,
            'defaultFields' => $defaultFields,
            'recentExports' => $this->exports->recentExports(12),
        ]);
    }

    // ── Unified download endpoint ─────────────────────────────────────────

    public function download(): void
    {
        Auth::requirePermission('exports.use');

        $entity = in_array($_POST['entity'] ?? '', ['contacts', 'clients'], true)
            ? $_POST['entity']
            : 'contacts';

        $format = in_array($_POST['format'] ?? '', ['csv', 'xlsx'], true)
            ? $_POST['format']
            : 'csv';

        $cfEntityType = $entity === 'contacts' ? 'contact' : 'client';
        $customFields = $this->customFields->fieldsForEntity($cfEntityType);

        $fieldDefs = $entity === 'contacts'
            ? $this->service->contactsFieldDefs($customFields)
            : $this->service->clientsFieldDefs($customFields);

        $selectedFields = $this->service->sanitizeFields(
            (array) ($_POST['fields'] ?? []),
            $fieldDefs
        );

        $filters   = $entity === 'contacts' ? $this->contactFiltersFromPost() : $this->clientFiltersFromPost();
        $totalRows = $entity === 'contacts'
            ? $this->service->countContacts($filters)
            : $this->service->countClients($filters);

        $fileName = $entity . '-' . date('Y-m-d-H-i-s') . '.' . $format;
        $user     = Auth::user();

        $this->exports->createCompletedExport($user['id'] ?? null, $format, $filters, $selectedFields, $totalRows, $fileName);

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $output = fopen('php://output', 'w');

            if ($entity === 'contacts') {
                $this->service->streamContactsCsv($filters, $selectedFields, $fieldDefs, $output);
            } else {
                $this->service->streamClientsCsv($filters, $selectedFields, $fieldDefs, $output);
            }

            fclose($output);
            exit;
        }

        // XLSX
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            Auth::redirect('/exports?entity=' . $entity . '&error=phpspreadsheet');
        }

        $spreadsheet = $entity === 'contacts'
            ? $this->service->buildContactsXlsx($filters, $selectedFields, $fieldDefs)
            : $this->service->buildClientsXlsx($filters, $selectedFields, $fieldDefs);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ── Template downloads ────────────────────────────────────────────────

    public function templateContacts(): void
    {
        Auth::requirePermission('exports.use');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contacts-import-template.csv"');
        echo $this->service->contactTemplateCsv();
        exit;
    }

    public function templateClients(): void
    {
        Auth::requirePermission('exports.use');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="clients-import-template.csv"');
        echo $this->service->clientTemplateCsv();
        exit;
    }

    // ── Legacy routes (redirect to hub) ──────────────────────────────────

    // ── Filter helpers ────────────────────────────────────────────────────

    private function contactFiltersFromPost(): array
    {
        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'is_company' => trim($_POST['is_company'] ?? ''),
            'client_id'  => (int) ($_POST['client_id'] ?? 0),
            'tag_ids'    => array_values(array_filter(array_map('intval', (array) ($_POST['tag_ids'] ?? [])), fn ($id) => $id > 0)),
        ];
    }

    private function clientFiltersFromPost(): array
    {
        return [
            'commercial_name' => trim($_POST['commercial_name'] ?? ''),
            'legal_name'      => trim($_POST['legal_name'] ?? ''),
            'city'            => trim($_POST['city'] ?? ''),
            'country'         => trim($_POST['country'] ?? ''),
            'province'        => trim($_POST['province'] ?? ''),
            'sector_id'       => (int) ($_POST['sector_id'] ?? 0),
            'tag_ids'         => array_values(array_filter(array_map('intval', (array) ($_POST['tag_ids'] ?? [])), fn ($id) => $id > 0)),
        ];
    }
}
