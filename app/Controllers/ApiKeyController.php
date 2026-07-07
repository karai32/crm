<?php

class ApiKeyController
{
    use SortableTrait;

    private ApiKeyRepository $apiKeys;

    public function __construct()
    {
        $this->apiKeys = new ApiKeyRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $newCredentials = $_SESSION['new_api_credentials'] ?? null;
        unset($_SESSION['new_api_credentials']);

        $sort  = $this->sortParam(['name', 'is_active', 'created_at'], 'created_at');
        $dir   = $this->dirParam();
        $total = $this->apiKeys->count();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('api/index', [
            'title'          => Lang::get('api.title'),
            'styles'         => ['api.css'],
            'scripts'        => ['api-keys.js'],
            'apiKeys'        => $this->apiKeys->paginate($page, $perPage, $sort, $dir),
            'newCredentials' => $newCredentials,
            'sort'           => $sort,
            'dir'            => $dir,
            'page'           => $page,
            'perPage'        => $perPage,
            'total'          => $total,
            'totalPages'     => $totalPages,
        ]);
    }



    public function store(): void
    {
        Auth::requireAdmin();

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            Auth::redirect('/api-keys');
        }

        $clientId   = 'crm_' . bin2hex(random_bytes(16));
        $secret     = bin2hex(random_bytes(32));
        $secretHash = hash('sha256', $secret);
        $scopes     = ['contacts:write', 'contacts:read', 'clients:write', 'clients:read', 'sectors:write', 'sectors:read', 'tags:write', 'tags:read'];

        $this->apiKeys->create($name, $clientId, $secretHash, $scopes);

        $_SESSION['new_api_credentials'] = [
            'client_id' => $clientId,
            'secret'    => $secret,
        ];
        Auth::redirect('/api-keys');
    }

    public function rename(): void
    {
        Auth::requireAdmin();

        $id   = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($id > 0 && $name !== '') {
            $this->apiKeys->updateName($id, $name);
        }

        Auth::redirect('/api-keys');
    }

    public function revoke(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->revoke($id);
        }

        Auth::redirect('/api-keys');
    }

    public function enable(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->enable($id);
        }

        Auth::redirect('/api-keys');
    }

    public function syncScopes(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->updateScopes($id, [
                'contacts:write', 'contacts:read',
                'clients:write',  'clients:read',
                'sectors:write',  'sectors:read',
                'tags:write',     'tags:read',
            ]);
        }

        Auth::redirect('/api-keys');
    }

    public function delete(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->delete($id);
        }

        Auth::redirect('/api-keys');
    }

    public function logs(): void
    {
        Auth::requireAdmin();

        $filters = [
            'key_id'    => (int) ($_GET['key_id'] ?? 0),
            'method'    => trim($_GET['method'] ?? ''),
            'status'    => trim($_GET['status'] ?? ''),
            'path'      => trim($_GET['path'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
        ];

        $total = $this->apiKeys->countLogs($filters);
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('api/logs', [
            'title'      => Lang::get('api.logs'),
            'styles'     => ['api.css'],
            'scripts'    => ['api-logs.js'],
            'logs'       => $this->apiKeys->pageLogs($page, $perPage, $filters),
            'apiKeys'    => $this->apiKeys->all(),
            'filters'    => $filters,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }
}
