<?php

class ExportController
{
    private ExportManager $manager;

    public function __construct()
    {
        $this->manager = new ExportManager();
    }

    // ── Hub page ───────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requirePermission('exports.use');

        $entity = in_array($_GET['entity'] ?? '', ['contacts', 'clients'], true)
            ? $_GET['entity']
            : 'contacts';

        $page = $this->manager->page($entity);

        View::render('exports/index', [
            'title'         => 'Export data',
            'styles'        => ['data.css'],
            'entity'        => $page['entity'],
            'fieldDefs'     => $page['fieldDefs'],
            'defaultFields' => $page['defaultFields'],
            'recentExports' => $page['recentExports'],
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

        $filters   = $entity === 'contacts' ? $this->contactFiltersFromPost() : $this->clientFiltersFromPost();
        $selectedFields = (array) ($_POST['fields'] ?? []);
        $fileName = $this->manager->filename($entity, $format);
        $user     = Auth::user();

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $output = fopen('php://output', 'w');
            $this->manager->writeCsv($entity, $filters, $selectedFields, $user['id'] ?? null, $fileName, $output);
            fclose($output);
            exit;
        }

        // XLSX
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            Auth::redirect('/exports?entity=' . $entity . '&error=phpspreadsheet');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $this->manager->writeXlsx(
            $entity,
            $filters,
            $selectedFields,
            $user['id'] ?? null,
            $fileName
        );
        exit;
    }

    // ── Template downloads ────────────────────────────────────────────────

    public function templateContacts(): void
    {
        Auth::requirePermission('exports.use');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contacts-import-template.csv"');
        echo $this->manager->template('contacts');
        exit;
    }

    public function templateClients(): void
    {
        Auth::requirePermission('exports.use');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="clients-import-template.csv"');
        echo $this->manager->template('clients');
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
