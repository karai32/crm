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

        $sort  = $this->sortParam(['id', 'original_filename', 'entity_type', 'status'], 'id');
        $dir   = $this->dirParam();
        $total = $this->imports->countBatches();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('imports/index', [
            'title'      => Lang::get('imports.title'),
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
            'title'   => Lang::get('imports.upload_title'),
            'styles'  => ['imports.css'],
            'scripts' => ['imports-upload.js'],
            'error'   => null,
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
                'title'   => Lang::get('imports.upload_title'),
                'styles'  => ['imports.css'],
                'scripts' => ['imports-upload.js'],
                'error'   => $result['message'],
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
            'title'   => Lang::get('imports.preview_title'),
            'styles'  => ['imports.css'],
            'scripts' => ['imports-preview.js'],
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
            'title'  => Lang::get('imports.errors_title'),
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
            'title'  => Lang::get('imports.result_title'),
            'styles' => ['imports.css'],
            'result' => $result,
        ]);
    }
}
