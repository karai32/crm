<?php

class ApiKeyController
{
    private ApiKeyRepository $apiKeys;

    public function __construct()
    {
        $this->apiKeys = new ApiKeyRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $newKey = $_SESSION['new_api_key'] ?? null;
        unset($_SESSION['new_api_key']);

        View::render('api/index', [
            'title'   => 'API Keys',
            'styles'  => ['api.css'],
            'apiKeys' => $this->apiKeys->all(),
            'newKey'  => $newKey,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            Auth::redirect('/api-keys');
        }

        $rawKey  = 'crm_' . bin2hex(random_bytes(32));
        $prefix  = substr($rawKey, 0, 12);
        $hash    = hash('sha256', $rawKey);
        $scopes  = ['contacts:write', 'contacts:read', 'clients:write', 'clients:read', 'sectors:write', 'sectors:read', 'tags:write', 'tags:read'];

        $this->apiKeys->create($name, $prefix, $hash, $scopes);

        $_SESSION['new_api_key'] = $rawKey;
        Auth::redirect('/api-keys');
    }

    public function revoke(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->revoke($id);
        }

        Auth::redirect('/api-keys');
    }

    public function delete(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->delete($id);
        }

        Auth::redirect('/api-keys');
    }
}
