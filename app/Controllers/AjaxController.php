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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
            return;
        }

        if (!Auth::can('sectors.manage')) {
            $this->json(['error' => 'Forbidden'], 403);
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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
            return;
        }

        if (!Auth::can('sectors.manage')) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $catalog = new PhosphorIconCatalog();
        $query = trim($_GET['q'] ?? '');

        $this->json(['items' => $catalog->search($query)]);
    }

    public function clientFieldValues(): void
    {
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
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
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
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
