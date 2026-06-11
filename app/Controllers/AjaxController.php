<?php

class AjaxController
{
    private ContactRepository $contacts;
    private ClientRepository $clients;
    private TagRepository $tags;
    private SectorRepository $sectors;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->clients = new ClientRepository();
        $this->tags = new TagRepository();
        $this->sectors = new SectorRepository();
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
            $name = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
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

        $query = trim($_GET['q'] ?? '');
        $clients = $this->clients->search($query);

        $items = array_map(function (array $client): array {
            $label = $client['commercial_name'];

            if (!empty($client['legal_name']) && $client['legal_name'] !== $client['commercial_name']) {
                $label .= ' (' . $client['legal_name'] . ')';
            }

            return [
                'id' => (int) $client['id'],
                'name' => $label,
            ];
        }, $clients);

        $this->json(['items' => $items]);
    }

    public function tagsSearch(): void
    {
        if (!Auth::check()) {
            $this->json(['error' => 'Unauthenticated'], 401);
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $tags = $this->tags->search($query);

        $items = array_map(fn (array $tag): array => [
            'id'    => (int) $tag['id'],
            'name'  => $tag['name'],
            'slug'  => $tag['slug'] ?? null,
            'color' => $tag['color'] ?? null,
        ], $tags);

        $this->json(['items' => $items]);
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
            'is_active' => (int) $sector['is_active'],
        ], $sectors);

        $this->json(['items' => $items]);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
