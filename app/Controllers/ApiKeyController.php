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

        $newCredentials = $_SESSION['new_api_credentials'] ?? null;
        unset($_SESSION['new_api_credentials']);

        View::render('api/index', [
            'title'          => 'API Credentials',
            'styles'         => ['api.css'],
            'apiKeys'        => $this->apiKeys->all(),
            'newCredentials' => $newCredentials,
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

    public function revoke(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->apiKeys->revoke($id);
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
}
