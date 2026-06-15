<?php

class ClientController
{
    private ClientRepository $clients;
    private SectorRepository $sectors;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->clients = new ClientRepository();
        $this->sectors = new SectorRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = $this->filtersFromRequest();
        $total = $this->clients->countAll($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $clients = $this->clients->paginate($page, $perPage, $filters);
        $clientIds = array_map('intval', array_column($clients, 'id'));
        $selectedFilterTags = $this->selectedTagsForIds($filters['tag_ids'] ?? []);

        View::render('clients/index', [
            'title'      => 'Clients',
            'styles'     => ['clients.css'],
            'clients'    => $clients,
            'clientTags' => $this->clients->tagsForClients($clientIds),
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'total'      => $total,
            'filters'    => $filters,
            'filterSectors' => $this->sectors->all(),
            'filterTags' => $this->mergeSelectedRows($this->clients->firstTags(), $selectedFilterTags),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('clients.create');

        View::render('clients/create', [
            'title'        => 'Create client',
            'styles'       => ['clients.css'],
            'client'       => [],
            'sectors'      => $this->sectors->active(),
            'tags'         => $this->clients->firstTags(),
            'selectedTagIds' => [],
            'customFields' => $this->customFields->fieldsForEntity('client'),
            'customValues' => [],
            'error'        => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('clients.create');

        $data = $this->clientDataFromRequest();
        $tagIds = $this->tagIdsFromRequest();
        $customFields = $this->customFields->fieldsForEntity('client');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['commercial_name'] === '') {
            View::render('clients/create', [
                'title'        => 'Create client',
                'styles'       => ['clients.css'],
                'client'       => $data,
                'sectors'      => $this->sectors->active(),
                'tags'         => $this->clients->firstTags(),
                'selectedTagIds' => $tagIds,
                'customFields' => $customFields,
                'customValues' => $customValues,
                'error'        => 'Commercial name is required.',
            ]);
            return;
        }

        $id = $this->clients->create($data);
        $this->clients->syncTags($id, $tagIds);
        $this->customFields->saveValues('client', $id, $customFields, $customValues);
        Auth::redirect('/clients/show?id=' . $id);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $client = $this->findClientOrFail((int) ($_GET['id'] ?? 0));

        if ($client === null) {
            return;
        }

        View::render('clients/show', [
            'title'               => 'Client details',
            'styles'              => ['clients.css'],
            'client'              => $client,
            'contacts'            => $this->clients->contactsForClient((int) $client['id']),
            'tags'                => $this->clients->tagsForClient((int) $client['id']),
            'customFields'        => $this->customFields->fieldsForEntity('client'),
            'customValues'        => $this->customFields->valuesForEntity('client', (int) $client['id']),
            'customFieldRepository' => $this->customFields,
        ]);
    }

    public function edit(): void
    {
        Auth::requirePermission('clients.edit');

        $client = $this->findClientOrFail((int) ($_GET['id'] ?? 0));

        if ($client === null) {
            return;
        }

        $selectedTags = $this->clients->tagsForClient((int) $client['id']);

        View::render('clients/edit', [
            'title'        => 'Edit client',
            'styles'       => ['clients.css'],
            'client'       => $client,
            'sectors'      => $this->sectors->active(),
            'tags'         => $this->mergeSelectedRows($this->clients->firstTags(), $selectedTags),
            'selectedTagIds' => array_map('intval', array_column($selectedTags, 'id')),
            'customFields' => $this->customFields->fieldsForEntity('client'),
            'customValues' => $this->customFields->valuesForEntity('client', (int) $client['id']),
            'error'        => null,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('clients.edit');

        $id = (int) ($_POST['id'] ?? 0);
        $client = $this->findClientOrFail($id);

        if ($client === null) {
            return;
        }

        $data = $this->clientDataFromRequest();
        $tagIds = $this->tagIdsFromRequest();
        $customFields = $this->customFields->fieldsForEntity('client');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['commercial_name'] === '') {
            $data['id'] = $id;

            View::render('clients/edit', [
                'title'        => 'Edit client',
                'styles'       => ['clients.css'],
                'client'       => $data,
                'sectors'      => $this->sectors->active(),
                'tags'         => $this->clients->firstTags(),
                'selectedTagIds' => $tagIds,
                'customFields' => $customFields,
                'customValues' => $customValues,
                'error'        => 'Commercial name is required.',
            ]);
            return;
        }

        $this->clients->update($id, $data);
        $this->clients->syncTags($id, $tagIds);
        $this->customFields->saveValues('client', $id, $customFields, $customValues);
        Auth::redirect('/clients/show?id=' . $id);
    }

    public function delete(): void
    {
        Auth::requirePermission('clients.delete');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->clients->delete($id);
        }

        Auth::redirect('/clients');
    }

    public function bulkAction(): void
    {
        Auth::requireLogin();

        $clientIds = $this->entityIdsFromPost('client_ids');
        $action = $_POST['bulk_action'] ?? '';

        if (!empty($clientIds)) {
            if ($action === 'delete') {
                Auth::requirePermission('clients.delete');
                $this->clients->deleteMultiple($clientIds);
            } elseif ($action === 'remove_tags') {
                Auth::requirePermission('clients.edit');
                $tagIds = $this->tagIdsFromRequest();
                if (!empty($tagIds)) {
                    $this->clients->removeTagsFromClients($clientIds, $tagIds);
                }
            } else {
                Auth::requirePermission('clients.edit');
                $tagIds = $this->tagIdsFromRequest();
                if (!empty($tagIds)) {
                    $this->clients->addTagsToClients($clientIds, $tagIds);
                }
            }
        }

        Auth::redirect('/clients');
    }

    private function clientDataFromRequest(): array
    {
        return [
            'commercial_name' => trim($_POST['commercial_name'] ?? ''),
            'legal_name' => $this->emptyToNull($_POST['legal_name'] ?? ''),
            'cif' => $this->emptyToNull($_POST['cif'] ?? ''),
            'address' => $this->emptyToNull($_POST['address'] ?? ''),
            'postal_code' => $this->emptyToNull($_POST['postal_code'] ?? ''),
            'city' => $this->emptyToNull($_POST['city'] ?? ''),
            'province' => $this->emptyToNull($_POST['province'] ?? ''),
            'country' => $this->emptyToNull($_POST['country'] ?? ''),
            'sector_id' => (int) ($_POST['sector_id'] ?? 0) ?: null,
            'website' => $this->emptyToNull($_POST['website'] ?? ''),
            'notes' => $this->emptyToNull($_POST['notes'] ?? ''),
        ];
    }

    private function emptyToNull(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function filtersFromRequest(): array
    {
        return [
            'commercial_name' => trim($_GET['commercial_name'] ?? ''),
            'legal_name' => trim($_GET['legal_name'] ?? ''),
            'cif' => trim($_GET['cif'] ?? ''),
            'sector_id' => (int) ($_GET['sector_id'] ?? 0),
            'tag_ids' => $this->tagIdsFromGet(),
            'city' => trim($_GET['city'] ?? ''),
            'province' => trim($_GET['province'] ?? ''),
            'country' => trim($_GET['country'] ?? ''),
            'address' => trim($_GET['address'] ?? ''),
            'postal_code' => trim($_GET['postal_code'] ?? ''),
            'website' => trim($_GET['website'] ?? ''),
            'notes' => trim($_GET['notes'] ?? ''),
            'created_from' => trim($_GET['created_from'] ?? ''),
            'created_to' => trim($_GET['created_to'] ?? ''),
            'updated_from' => trim($_GET['updated_from'] ?? ''),
            'updated_to' => trim($_GET['updated_to'] ?? ''),
        ];
    }

    private function tagIdsFromRequest(): array
    {
        $tagIds = $_POST['tag_ids'] ?? [];

        return $this->cleanTagIds($tagIds);
    }

    private function tagIdsFromGet(): array
    {
        $tagIds = $_GET['tag_ids'] ?? [];

        if (!empty($_GET['tag_id'])) {
            if (!is_array($tagIds)) {
                $tagIds = [];
            }

            $tagIds[] = $_GET['tag_id'];
        }

        return $this->cleanTagIds($tagIds);
    }

    private function cleanTagIds(mixed $tagIds): array
    {
        if (!is_array($tagIds)) {
            return [];
        }

        $tagIds = array_map('intval', $tagIds);
        $tagIds = array_filter($tagIds, fn ($id) => $id > 0);

        return array_values(array_unique($tagIds));
    }

    private function entityIdsFromPost(string $key): array
    {
        $ids = $_POST[$key] ?? [];

        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn ($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    private function findClientOrFail(int $id): ?array
    {
        $client = $this->clients->find($id);

        if ($client === null) {
            http_response_code(404);
            echo 'Client not found';
            return null;
        }

        return $client;
    }

    private function selectedTagsForIds(array $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        return array_values(array_filter($this->clients->firstTags(500), function ($tag) use ($tagIds) {
            return in_array((int) $tag['id'], $tagIds, true);
        }));
    }

    private function mergeSelectedRows(array $baseRows, array $selectedRows): array
    {
        $rows = [];

        foreach (array_merge($selectedRows, $baseRows) as $row) {
            $rows[(int) $row['id']] = $row;
        }

        return array_values($rows);
    }
}
