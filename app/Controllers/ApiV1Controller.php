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
