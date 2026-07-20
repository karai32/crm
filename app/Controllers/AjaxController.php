<?php

class AjaxController
{
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private TagRepository $tags;
    private SectorRepository $sectors;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
        $this->tags = new TagRepository();
        $this->sectors = new SectorRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function globalSearch(): void
    {
        if (!$this->guard()) {
            return;
        }

        $query = trim($_GET['q'] ?? '');

        if ($query === '') {
            $this->json(['items' => []]);
            return;
        }

        $contacts = array_map(function (array $contact): array {
            $name = $contact['full_name'] ?? '';
            $meta = $contact['email'] ?: ($contact['phone'] ?? '');

            return [
                'type' => 'contact',
                'id' => (int) $contact['id'],
                'name' => $name !== '' ? $name : 'Contact #' . (int) $contact['id'],
                'meta' => $meta,
                'url' => Auth::url('/contacts/show?id=' . (int) $contact['id']),
            ];
        }, $this->contacts->search($query, 8));

        $clients = array_map(function (array $client): array {
            $meta = '';

            if (!empty($client['legal_name']) && $client['legal_name'] !== $client['commercial_name']) {
                $meta = $client['legal_name'];
            }

            return [
                'type' => 'client',
                'id' => (int) $client['id'],
                'name' => $client['commercial_name'],
                'meta' => $meta,
                'url' => Auth::url('/clients/show?id=' . (int) $client['id']),
            ];
        }, $this->clients->search($query, 8));

        $items = array_slice(array_merge($contacts, $clients), 0, 12);

        $this->json(['items' => $items]);
    }

    public function clientsSearch(): void
    {
        if (!$this->guard()) {
            return;
        }

        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        [$clients, $hasMore] = $this->paginatedItems(
            $q,
            $page,
            fn ($q, $limit, $offset) => $this->clients->search($q, $limit, $offset)
        );

        $items = array_map(function (array $client): array {
            $label = $client['commercial_name'];

            if (!empty($client['legal_name']) && $client['legal_name'] !== $client['commercial_name']) {
                $label .= ' (' . $client['legal_name'] . ')';
            }

            return ['id' => (int) $client['id'], 'name' => $label];
        }, $clients);

        $this->json(['items' => $items, 'has_more' => $hasMore]);
    }

    public function tagsSearch(): void
    {
        if (!$this->guard()) {
            return;
        }

        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        [$tags, $hasMore] = $this->paginatedItems(
            $q,
            $page,
            fn ($q, $limit, $offset) => $this->tags->search($q, $limit, $offset)
        );

        $items = array_map(fn (array $tag): array => [
            'id'    => (int) $tag['id'],
            'name'  => $tag['name'],
            'slug'  => $tag['slug'] ?? null,
            'color' => $tag['color'] ?? null,
        ], $tags);

        $this->json(['items' => $items, 'has_more' => $hasMore]);
    }

    public function sectorsSearch(): void
    {
        if (!$this->guard('sectors.manage')) {
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $sectors = $this->sectors->search($query);

        $items = array_map(fn (array $sector): array => [
            'id' => (int) $sector['id'],
            'name' => $sector['name'],
            'slug' => $sector['slug'] ?? null,
            'icon' => $sector['icon'] ?? null,
            'is_active' => (int) $sector['is_active'],
        ], $sectors);

        $this->json(['items' => $items]);
    }

    public function iconsSearch(): void
    {
        if (!$this->guard('sectors.manage')) {
            return;
        }

        $catalog = new PhosphorIconCatalog();
        $query = trim($_GET['q'] ?? '');

        $this->json(['items' => $catalog->search($query)]);
    }

    public function clientFieldValues(): void
    {
        if (!$this->guard()) {
            return;
        }

        $field = trim($_GET['field'] ?? '');
        $q     = trim($_GET['q'] ?? '');
        $page  = max(1, (int) ($_GET['page'] ?? 1));

        [$items, $hasMore] = $this->paginatedItems(
            $q,
            $page,
            fn ($q, $limit, $offset) => $this->clients->distinctFieldValues($field, $q, $limit, $offset)
        );

        $this->json(['items' => $items, 'has_more' => $hasMore]);
    }

    public function customFieldValues(): void
    {
        if (!$this->guard()) {
            return;
        }

        $fieldId = (int) ($_GET['field_id'] ?? 0);
        if ($fieldId <= 0) {
            $this->json(['items' => [], 'has_more' => false]);
            return;
        }

        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        [$items, $hasMore] = $this->paginatedItems(
            $q,
            $page,
            fn ($q, $limit, $offset) => $this->customFields->distinctTextValues($fieldId, $q, $limit, $offset)
        );

        $this->json(['items' => $items, 'has_more' => $hasMore]);
    }

    public function inspectEmailBatch(): void
    {
        Auth::requireLogin();

        set_time_limit(120);

        try {
            $batchSize = 50;
            $total     = $this->contacts->countUninspected();
            $batch     = $this->contacts->uninspectedBatch($batchSize);

            foreach ($batch as $contact) {
                $result = EmailInspector::inspect($contact['email']);
                $this->contacts->updateEmailInspection(
                    (int) $contact['id'],
                    $result['is_corporate_email'],
                    $result['email_status']
                );
            }

            $processed = count($batch);

            $this->json([
                'processed' => $processed,
                'remaining' => max(0, $total - $processed),
                'done'      => $processed === 0,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'error'     => $e->getMessage(),
                'processed' => 0,
                'remaining' => 0,
                'done'      => true,
            ], 500);
        }
    }

    public function geminiCompany(): void
    {
        if (!$this->guard()) {
            return;
        }

        $contact = $this->contacts->find((int) ($_POST['contact_id'] ?? 0));
        $email = trim((string) ($contact['email'] ?? ''));

        if ($contact === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->json(['error' => 'Contact does not have a valid email address'], 422);
            return;
        }

        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        if ($domain === '') {
            $this->json(['error' => 'Could not determine the email domain'], 422);
            return;
        }

        $apiKey = $this->geminiApiKey();
        if ($apiKey === '') {
            $this->json(['error' => 'Gemini API key is not configured'], 500);
            return;
        }

        if (!function_exists('curl_init')) {
            $this->json(['error' => 'PHP cURL extension is not installed'], 500);
            return;
        }

        $website = 'https://' . $domain;
        $prompt = <<<PROMPT
            You are given an email domain: {$domain}
            The website to explore is: {$website}

            If the domain looks like it has a small typo (for example a wrong top-level domain
            such as ".con" instead of ".com", ".ogr" instead of ".org", a missing or duplicated
            letter, etc.), try the corrected domain(s) as well before giving up.

            Explore the site (original or corrected) and determine the official name of the
            company it belongs to.

            Respond with ONLY one of the following, and nothing else:
            - The official company name, if you can confidently determine it.
            - The exact word "none", if the site is unreachable, unrelated, parked, or you
              cannot confidently determine a company name.

            Do not make up information. Do not add explanations, punctuation, or extra text.
            PROMPT;

        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
            'tools' => [['url_context' => (object) []]],
        ];

        $curl = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);

        $responseBody = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($responseBody === false) {
            $this->json(['error' => 'Gemini connection failed: ' . $curlError], 502);
            return;
        }

        $response = json_decode($responseBody, true);
        if (!is_array($response)) {
            $this->json(['error' => 'Gemini returned invalid JSON'], 502);
            return;
        }

        if ($status < 200 || $status >= 300) {
            $message = $response['error']['message'] ?? 'Gemini API request failed';
            $this->json(['error' => $message, 'gemini_response' => $response], 502);
            return;
        }

        $this->json([
            'domain' => $domain,
            'website' => $website,
            'answer' => $response['candidates'][0]['content']['parts'][0]['text'] ?? null,
            'gemini_response' => $response,
        ]);
    }

    private function geminiApiKey(): string
    {
        $environmentKey = getenv('GEMINI_API_KEY');
        if (is_string($environmentKey) && trim($environmentKey) !== '') {
            return trim($environmentKey);
        }

        $configPath = dirname(__DIR__, 2) . '/config/gemini.php';
        if (!is_file($configPath)) {
            return '';
        }

        $config = require $configPath;

        return is_array($config) ? trim((string) ($config['api_key'] ?? '')) : '';
    }

    // JSON-flavoured auth guard: 401 when unauthenticated, 403 when the
    // optional permission is missing. Returns true when the request may proceed.
    private function guard(?string $permission = null): bool
    {
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
            return false;
        }

        if ($permission !== null && !Auth::can($permission)) {
            $this->json(['error' => 'Forbidden'], 403);
            return false;
        }

        return true;
    }

    private function paginatedItems(string $q, int $page, callable $fetch, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $rows   = $fetch($q, $perPage + 1, $offset);
        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            array_pop($rows);
        }
        return [$rows, $hasMore];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
