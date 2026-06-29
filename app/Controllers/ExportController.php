<?php

class ExportController
{
    use SortableTrait;

    private ExportManager $manager;
    private ExportRepository $exports;

    public function __construct()
    {
        $this->manager = new ExportManager();
        $this->exports = new ExportRepository();
    }

    // ── Hub page ───────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requirePermission('exports.use');

        $entity = in_array($_GET['entity'] ?? '', ['contacts', 'clients'], true)
            ? $_GET['entity']
            : 'contacts';

        $sort     = $this->sortParam(['id', 'entity_type', 'stored_filename'], 'id');
        $dir      = $this->dirParam();
        $pageData = $this->manager->page($entity);
        $total    = $this->exports->count();
        [$pageNum, $perPage, $totalPages] = $this->pageParams($total);

        View::render('exports/index', [
            'title'         => 'Export data',
            'styles'        => ['data.css'],
            'scripts'       => ['exports.js'],
            'entity'        => $pageData['entity'],
            'fieldDefs'     => $pageData['fieldDefs'],
            'defaultFields' => $pageData['defaultFields'],
            'recentExports' => $this->exports->paginate($pageNum, $perPage, $sort, $dir),
            'sort'          => $sort,
            'dir'           => $dir,
            'page'          => $pageNum,
            'perPage'       => $perPage,
            'total'         => $total,
            'totalPages'    => $totalPages,
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
            'full_name'  => trim($_POST['full_name'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
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
