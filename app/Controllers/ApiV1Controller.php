<?php

class ApiV1Controller
{
    private ApiKeyRepository    $apiKeys;
    private ContactRepository   $contacts;
    private ClientRepository    $clients;
    private TagRepository       $tags;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->apiKeys      = new ApiKeyRepository();
        $this->contacts     = new ContactRepository();
        $this->clients      = new ClientRepository();
        $this->tags         = new TagRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function contacts(): void
    {
        $startTime = microtime(true);
        $method    = $_SERVER['REQUEST_METHOD'] ?? 'POST';
        $path      = '/api/v1/contacts';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!in_array('contacts:write', $scopes, true)) {
            $response = $this->errorResponse('forbidden', 'API key lacks contacts:write scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $body = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($body) || empty($body)) {
            $response = $this->errorResponse('validation_error', 'Request body must be a non-empty JSON array');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 422, $response, $ip, $startTime);
            return;
        }

        if (array_keys($body) !== range(0, count($body) - 1)) {
            $response = $this->errorResponse('validation_error', 'Request body must be a JSON array, not an object');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        if (count($body) > 100) {
            $response = $this->errorResponse('too_many_items', 'Maximum 100 contacts per request');
            $this->respond(422, $response);
            $this->log((int) $apiKey['id'], $method, $path, $body, 422, $response, $ip, $startTime);
            return;
        }

        $results = [];
        foreach ($body as $index => $item) {
            $results[] = $this->processContact($index, is_array($item) ? $item : []);
        }

        $created  = count(array_filter($results, fn($r) => $r['success'] === true));
        $failed   = count($results) - $created;

        $response = [
            'success' => true,
            'data'    => [
                'processed' => count($results),
                'created'   => $created,
                'failed'    => $failed,
                'results'   => $results,
            ],
        ];

        $this->respond(207, $response);
        $this->log((int) $apiKey['id'], $method, $path, $body, 207, $response, $ip, $startTime);
    }

    private function processContact(int $index, array $item): array
    {
        $firstName  = trim($item['first_name'] ?? '');
        $email      = trim($item['email'] ?? '');
        $tagName    = trim($item['tag'] ?? '');
        $clientName = trim($item['client'] ?? '');

        $errors = [];
        if ($firstName === '')  { $errors[] = 'first_name is required'; }
        if ($email === '')      { $errors[] = 'email is required'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'email is invalid'; }
        if ($tagName === '')    { $errors[] = 'tag is required'; }
        if ($clientName === '') { $errors[] = 'client is required'; }

        if (!empty($errors)) {
            return ['index' => $index, 'success' => false, 'error' => ['code' => 'validation_error', 'details' => $errors]];
        }

        if ($this->contacts->emailExists($email)) {
            return ['index' => $index, 'success' => false, 'error' => ['code' => 'duplicate_contact', 'message' => 'Contact with this email already exists']];
        }

        // Find or create tag
        $tag        = $this->tags->findByName($tagName);
        $tagCreated = $tag === null;
        $tagId      = $tagCreated ? $this->tags->create($tagName, null) : (int) $tag['id'];

        // Find or create client
        $client        = $this->clients->findByCommercialName($clientName);
        $clientCreated = $client === null;
        $clientId      = $clientCreated ? $this->clients->create([
            'commercial_name' => $clientName,
            'legal_name'  => null, 'cif'      => null, 'address'  => null,
            'postal_code' => null, 'city'     => null, 'province' => null,
            'country'     => null, 'sector_id'=> null, 'website'  => null, 'notes' => null,
        ]) : (int) $client['id'];

        // Create contact
        $contactId = $this->contacts->create([
            'first_name' => $firstName,
            'last_name'  => trim($item['last_name'] ?? '') ?: null,
            'email'      => $email,
            'phone'      => trim($item['phone'] ?? '') ?: null,
            'is_company' => empty($item['is_company']) ? 0 : 1,
        ]);

        $this->contacts->syncTags($contactId, [$tagId]);
        $this->contacts->syncClients($contactId, [$clientId]);

        // Custom fields — silently skip unknown slugs
        $customIn = $item['custom_fields'] ?? [];
        if (is_array($customIn) && !empty($customIn)) {
            $this->saveCustomFields($contactId, $customIn);
        }

        return [
            'index'   => $index,
            'success' => true,
            'data'    => [
                'contact_id'     => $contactId,
                'client_created' => $clientCreated,
                'tag_created'    => $tagCreated,
            ],
        ];
    }

    private function saveCustomFields(int $contactId, array $input): void
    {
        $fields      = [];
        $inputValues = [];

        foreach ($input as $slug => $value) {
            $field = $this->customFields->findByEntityAndSlug('contact', (string) $slug);
            if ($field === null) {
                continue;
            }
            $fields[]                       = $field;
            $inputValues[(int) $field['id']] = $value;
        }

        if (!empty($fields)) {
            $this->customFields->saveValues('contact', $contactId, $fields, $inputValues);
        }
    }

    public function contactsList(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/contacts';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!$this->canRead($scopes)) {
            $response = $this->errorResponse('forbidden', 'API key lacks contacts:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));

        $filters = [
            'first_name'   => $_GET['first_name'] ?? '',
            'last_name'    => $_GET['last_name'] ?? '',
            'email'        => $_GET['email'] ?? '',
            'phone'        => $_GET['phone'] ?? '',
            'is_company'   => $_GET['is_company'] ?? '',
            'client_id'    => $_GET['client_id'] ?? '',
            'tag_ids'      => isset($_GET['tag_id']) ? [(int) $_GET['tag_id']] : [],
            'created_from' => $_GET['created_from'] ?? '',
            'created_to'   => $_GET['created_to'] ?? '',
        ];

        $total      = $this->contacts->countAll($filters);
        $items      = $this->contacts->paginate($page, $perPage, $filters);
        $contactIds = array_column($items, 'id');

        $tagsMap    = $this->contacts->tagsForContacts($contactIds);
        $clientsMap = $this->contacts->clientsForContacts($contactIds);

        $data = [];
        foreach ($items as $contact) {
            $id     = (int) $contact['id'];
            $data[] = [
                'id'         => $id,
                'first_name' => $contact['first_name'],
                'last_name'  => $contact['last_name'],
                'email'      => $contact['email'],
                'phone'      => $contact['phone'],
                'created_at' => $contact['created_at'],
                'tags'       => array_map(
                    fn($t) => ['id' => (int) $t['id'], 'name' => $t['name']],
                    $tagsMap[$id] ?? []
                ),
                'clients'    => array_map(
                    fn($c) => ['id' => (int) $c['id'], 'name' => $c['commercial_name']],
                    $clientsMap[$id] ?? []
                ),
            ];
        }

        $response = [
            'success' => true,
            'data'    => [
                'items'       => $data,
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, null, $ip, $startTime);
    }

    public function contactsShow(): void
    {
        $startTime = microtime(true);
        $method    = 'GET';
        $path      = '/api/v1/contacts/{id}';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? null;

        $apiKey = $this->authenticate();

        if ($apiKey === null) {
            $response = $this->errorResponse('unauthorized', 'Invalid or missing API key');
            $this->respond(401, $response);
            $this->log(null, $method, $path, null, 401, $response, $ip, $startTime);
            return;
        }

        $scopes = json_decode($apiKey['scopes'] ?? '[]', true) ?? [];
        if (!$this->canRead($scopes)) {
            $response = $this->errorResponse('forbidden', 'API key lacks contacts:read scope');
            $this->respond(403, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 403, $response, $ip, $startTime);
            return;
        }

        $this->apiKeys->updateLastUsed((int) $apiKey['id']);

        $id      = (int) ($_GET['id'] ?? 0);
        $contact = $id > 0 ? $this->contacts->find($id) : null;

        if ($contact === null) {
            $response = $this->errorResponse('not_found', 'Contact not found');
            $this->respond(404, $response);
            $this->log((int) $apiKey['id'], $method, $path, null, 404, $response, $ip, $startTime);
            return;
        }

        $tags     = $this->contacts->tagsForContact($id);
        $clients  = $this->contacts->clientsForContact($id);
        $cfFields = $this->customFields->fieldsForEntity('contact');
        $cfValues = $this->customFields->valuesForEntity('contact', $id);

        $customFields = [];
        foreach ($cfFields as $field) {
            $fieldId = (int) $field['id'];
            $value   = null;
            if (isset($cfValues[$fieldId])) {
                $row   = $cfValues[$fieldId];
                $value = match ($field['field_type']) {
                    'number'  => $row['value_number'] !== null ? (float) $row['value_number'] : null,
                    'date'    => $row['value_date'],
                    'boolean' => $row['value_bool'] !== null ? (bool) $row['value_bool'] : null,
                    default   => $row['value_text'],
                };
            }
            $customFields[$field['slug']] = $value;
        }

        $response = [
            'success' => true,
            'data'    => [
                'id'            => (int) $contact['id'],
                'first_name'    => $contact['first_name'],
                'last_name'     => $contact['last_name'],
                'email'         => $contact['email'],
                'phone'         => $contact['phone'],
                'is_company'    => (bool) $contact['is_company'],
                'created_at'    => $contact['created_at'],
                'updated_at'    => $contact['updated_at'] ?? null,
                'tags'          => array_map(
                    fn($t) => ['id' => (int) $t['id'], 'name' => $t['name']],
                    $tags
                ),
                'clients'       => array_map(
                    fn($c) => ['id' => (int) $c['id'], 'name' => $c['commercial_name']],
                    $clients
                ),
                'custom_fields' => $customFields,
            ],
        ];

        $this->respond(200, $response);
        $this->log((int) $apiKey['id'], $method, $path, null, 200, $response, $ip, $startTime);
    }

    private function canRead(array $scopes): bool
    {
        return in_array('contacts:read', $scopes, true) || in_array('contacts:write', $scopes, true);
    }

    private function authenticate(): ?array
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($key === '') {
            return null;
        }

        return $this->apiKeys->findByKeyHash(hash('sha256', $key));
    }

    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function errorResponse(string $code, string $message): array
    {
        return ['success' => false, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function log(?int $apiKeyId, string $method, string $path, ?array $requestBody, int $status, ?array $responseBody, ?string $ip, float $startTime): void
    {
        try {
            $this->apiKeys->log(
                $apiKeyId, $method, $path,
                $requestBody, $status, $responseBody,
                $ip, (int) round((microtime(true) - $startTime) * 1000)
            );
        } catch (Throwable) {
            // logging must never break the response
        }
    }
}
