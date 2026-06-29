<?php

class ImportController
{
    use SortableTrait;

    private ImportRepository $imports;
    private ImportManager $manager;

    public function __construct()
    {
        $this->imports = new ImportRepository();
        $this->manager = new ImportManager();
    }

    public function index(): void
    {
        Auth::requirePermission('imports.manage');

        $sort    = $this->sortParam(['id', 'original_filename', 'entity_type', 'status'], 'id');
        $dir     = $this->dirParam();
        $perPage = SettingsRepository::perPage();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $total   = $this->imports->countBatches();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        View::render('imports/index', [
            'title'      => 'Imports',
            'styles'     => ['imports.css'],
            'batches'    => $this->imports->paginateBatches($page, $perPage, $sort, $dir),
            'sort'       => $sort,
            'dir'        => $dir,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }



    public function upload(): void
    {
        Auth::requirePermission('imports.manage');

        View::render('imports/upload', [
            'title'  => 'Upload import file',
            'styles' => ['imports.css'],
            'error'  => null,
        ]);
    }

    public function storeUpload(): void
    {
        Auth::requirePermission('imports.manage');

        $user = Auth::user();
        $result = $this->manager->upload(
            $_FILES['csv_file'] ?? [],
            $user['id'] ?? null,
            (string) ($_POST['entity_type'] ?? 'contacts')
        );

        if (!$result['success']) {
            View::render('imports/upload', [
                'title'  => 'Upload import file',
                'styles' => ['imports.css'],
                'error'  => $result['message'],
            ]);
            return;
        }

        Auth::redirect('/imports/preview?id=' . $result['batch_id']);
    }

    public function preview(): void
    {
        Auth::requirePermission('imports.manage');

        $preview = $this->manager->preview((int) ($_GET['id'] ?? 0));

        if ($preview === null) {
            http_response_code(404);
            echo 'Import batch not found';
            return;
        }

        View::render('imports/preview', [
            'title'   => 'Import preview',
            'styles'  => ['imports.css'],
            'preview' => $preview,
        ]);
    }

    public function errors(): void
    {
        Auth::requirePermission('imports.manage');

        $batchId = (int) ($_GET['id'] ?? 0);
        $batch = $this->imports->findBatch($batchId);

        if ($batch === null) {
            http_response_code(404);
            echo 'Import batch not found';
            return;
        }

        View::render('imports/errors', [
            'title'  => 'Import errors',
            'styles' => ['imports.css'],
            'batch'  => $batch,
            'errors' => $this->imports->errorsForBatch($batchId),
        ]);
    }

    public function process(): void
    {
        Auth::requirePermission('imports.manage');

        $result = $this->manager->process(
            (int) ($_POST['id'] ?? 0),
            $_POST['mapping'] ?? [],
            $_POST['custom_fields'] ?? []
        );

        if ($result === null) {
            http_response_code(404);
            echo 'Import batch not found';
            return;
        }

        View::render('imports/result', [
            'title'  => 'Import result',
            'styles' => ['imports.css'],
            'result' => $result,
        ]);
    }
}
