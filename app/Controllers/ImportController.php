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
        $previewId = (int) ($_GET['id'] ?? 0);
        $this->renderIndex($previewId > 0 ? $this->manager->preview($previewId) : null);
    }

    public function storeUpload(): void
    {
        $user = Auth::user();
        $result = $this->manager->upload(
            $_FILES['csv_file'] ?? [],
            $user['id'] ?? null,
            (string) ($_POST['entity_type'] ?? 'contacts')
        );

        if (!$result['success']) {
            $this->renderIndex(null, $result['message']);
            return;
        }

        Auth::redirect('/imports?id=' . $result['batch_id']);
    }

    private function renderIndex(?array $preview, ?string $error = null): void
    {
        $sort  = $this->sortParam(['id', 'original_filename', 'entity_type', 'status'], 'id');
        $dir   = $this->dirParam();
        $total = $this->imports->countBatches();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('imports/index', [
            'title'      => Lang::get('imports.title'),
            'styles'     => ['data.css', 'imports.css'],
            'scripts'    => ['imports-upload.js', 'imports-preview.js'],
            'entity'     => $this->entityParam($preview),
            'batches'    => $this->imports->paginateBatches($page, $perPage, $sort, $dir),
            'sort'       => $sort,
            'dir'        => $dir,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
            'preview'    => $preview,
            'error'      => $error,
        ]);
    }

    private function entityParam(?array $preview): string
    {
        $entity = $preview['batch']['entity_type']
            ?? $_POST['entity_type']
            ?? $_GET['entity']
            ?? 'contacts';

        return $entity === 'clients' ? 'clients' : 'contacts';
    }

    public function errors(): void
    {
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
