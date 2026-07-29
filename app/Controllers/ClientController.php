<?php

class ClientController
{
    use SortableTrait, ControllerHelperTrait;

    private ClientRepository $clients;
    private SectorRepository $sectors;
    private CustomFieldRepository $customFields;
    private TagRepository $tags;
    private EntityTagRepository $entityTags;
    private ClientWriteService $clientWriter;

    public function __construct()
    {
        $this->clients = new ClientRepository();
        $this->sectors = new SectorRepository();
        $this->customFields = new CustomFieldRepository();
        $this->tags = new TagRepository();
        $this->entityTags = new EntityTagRepository();
        $this->clientWriter = new ClientWriteService(
            $this->clients,
            $this->entityTags,
            $this->customFields
        );
    }

    public function index(): void
    {
        $filters = $this->filtersFromRequest();
        $sort    = $this->sortParam(['id', 'commercial_name', 'legal_name', 'sector_name', 'city', 'country', 'created_at', 'is_active'], 'commercial_name');
        $dir     = $this->dirParam('asc');
        $total   = $this->clients->countAll($filters);
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        $clients = $this->clients->paginate($page, $perPage, $filters, $sort, $dir);
        $clientIds = array_map('intval', array_column($clients, 'id'));
        $selectedFilterTags = $this->filterRowsByIds($this->tags->first(500), $filters['tag_ids'] ?? []);

        View::render('clients/index', [
            'title'             => Lang::get('clients.title'),
            'styles'            => ['clients.css'],
            'scripts'           => ['list-page.js', 'clients.js'],
            'clients'           => $clients,
            'clientTags'        => $this->entityTags->tagsForEntities('client', $clientIds),
            'page'              => $page,
            'perPage'           => $perPage,
            'totalPages'        => $totalPages,
            'total'             => $total,
            'filters'           => $filters,
            'sort'              => $sort,
            'dir'               => $dir,
            'filterSectors'     => $this->sectors->all(),
            'filterTags'        => $this->mergeSelectedRows($this->tags->first(), $selectedFilterTags),
            'customFilterFields' => $this->customFields->filterableFieldsForEntity('client'),
        ]);
    }

    public function create(): void
    {
        View::render('clients/create', [
            'title'        => Lang::get('clients.create_title'),
            'styles'       => ['clients.css'],
            'client'       => [],
            'sectors'      => $this->sectors->active(),
            'tags'         => $this->tags->first(),
            'selectedTagIds' => [],
            'customFields' => $this->customFields->fieldsForEntity('client'),
            'customValues' => [],
            'error'        => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->clientDataFromRequest();
        $tagIds = $this->idsFromPost('tag_ids');
        $customFields = $this->customFields->fieldsForEntity('client');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['commercial_name'] === '') {
            View::render('clients/create', [
                'title'        => Lang::get('clients.create_title'),
                'styles'       => ['clients.css'],
                'client'       => $data,
                'sectors'      => $this->sectors->active(),
                'tags'         => $this->tags->first(),
                'selectedTagIds' => $tagIds,
                'customFields' => $customFields,
                'customValues' => $customValues,
                'error'        => Lang::get('clients.name_required'),
            ]);
            return;
        }

        $id = $this->clientWriter->create(
            data: $data,
            tagIds: $tagIds,
            customFields: $customFields,
            customValues: $customValues
        );
        Auth::redirect('/clients/show?id=' . $id);
    }

    public function show(): void
    {
        $client = $this->findClientOrFail((int) ($_GET['id'] ?? 0));

        if ($client === null) {
            return;
        }

        View::render('clients/show', [
            'title'               => Lang::get('clients.details_title'),
            'styles'              => ['clients.css'],
            'scripts'             => ['clients.js'],
            'client'              => $client,
            'contacts'            => $this->clients->contactsForClient((int) $client['id']),
            'tags'                => $this->entityTags->tagsForEntity('client', (int) $client['id']),
            'customFields'        => $this->customFields->fieldsForEntity('client'),
            'customValues'        => $this->customFields->valuesForEntity('client', (int) $client['id']),
            'customFieldRepository' => $this->customFields,
        ]);
    }

    public function edit(): void
    {
        $client = $this->findClientOrFail((int) ($_GET['id'] ?? 0));

        if ($client === null) {
            return;
        }

        $selectedTags = $this->entityTags->tagsForEntity('client', (int) $client['id']);

        View::render('clients/edit', [
            'title'        => Lang::get('clients.edit_title'),
            'styles'       => ['clients.css'],
            'client'       => $client,
            'sectors'      => $this->sectors->active(),
            'tags'         => $this->mergeSelectedRows($this->tags->first(), $selectedTags),
            'selectedTagIds' => array_map('intval', array_column($selectedTags, 'id')),
            'customFields' => $this->customFields->fieldsForEntity('client'),
            'customValues' => $this->customFields->valuesForEntity('client', (int) $client['id']),
            'error'        => null,
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $client = $this->findClientOrFail($id);

        if ($client === null) {
            return;
        }

        $data = $this->clientDataFromRequest();
        $tagIds = $this->idsFromPost('tag_ids');
        $customFields = $this->customFields->fieldsForEntity('client');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['commercial_name'] === '') {
            $data['id'] = $id;

            View::render('clients/edit', [
                'title'        => Lang::get('clients.edit_title'),
                'styles'       => ['clients.css'],
                'client'       => $data,
                'sectors'      => $this->sectors->active(),
                'tags'         => $this->tags->first(),
                'selectedTagIds' => $tagIds,
                'customFields' => $customFields,
                'customValues' => $customValues,
                'error'        => Lang::get('clients.name_required'),
            ]);
            return;
        }

        $this->clientWriter->update(
            id: $id,
            changes: $data,
            tagIds: $tagIds,
            customFields: $customFields,
            customValues: $customValues
        );
        Auth::redirect('/clients/show?id=' . $id);
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->clients->delete($id);
        }

        Auth::redirect('/clients');
    }

    public function bulkAction(): void
    {
        $clientIds = $this->idsFromPost('client_ids');
        $action = $_POST['bulk_action'] ?? '';

        if (!empty($clientIds)) {
            if ($action === 'delete') {
                $this->clients->deleteMultiple($clientIds);
            } elseif ($action === 'remove_tags') {
                $tagIds = $this->idsFromPost('tag_ids');
                if (!empty($tagIds)) {
                    $this->entityTags->remove('client', $clientIds, $tagIds);
                }
            } else {
                $tagIds = $this->idsFromPost('tag_ids');
                if (!empty($tagIds)) {
                    $this->entityTags->add('client', $clientIds, $tagIds);
                }
            }
        }

        Auth::redirect('/clients');
    }

    private function clientDataFromRequest(): array
    {
        return [
            'commercial_name'  => trim($_POST['commercial_name'] ?? ''),
            'legal_name'       => $this->emptyToNull($_POST['legal_name'] ?? ''),
            'cif'              => $this->emptyToNull($_POST['cif'] ?? ''),
            'address'          => $this->emptyToNull($_POST['address'] ?? ''),
            'postal_code'      => $this->emptyToNull($_POST['postal_code'] ?? ''),
            'city'             => $this->emptyToNull($_POST['city'] ?? ''),
            'province'         => $this->emptyToNull($_POST['province'] ?? ''),
            'country'          => $this->emptyToNull($_POST['country'] ?? ''),
            'sector_id'        => (int) ($_POST['sector_id'] ?? 0) ?: null,
            'website'          => $this->emptyToNull($_POST['website'] ?? ''),
            'notes'            => $this->emptyToNull($_POST['notes'] ?? ''),
            'is_web_connected' => isset($_POST['is_web_connected']) ? 1 : 0,
            'is_active'        => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function filtersFromRequest(): array
    {
        return [
            'commercial_name'  => trim($_GET['commercial_name'] ?? ''),
            'legal_name'       => trim($_GET['legal_name'] ?? ''),
            'sector_id'        => (int) ($_GET['sector_id'] ?? 0),
            'tag_ids'          => $this->tagIdsFromGet(),
            'is_web_connected' => $_GET['is_web_connected'] ?? '',
            'is_active'        => $_GET['is_active'] ?? '',
            'created_from'     => trim($_GET['created_from'] ?? ''),
            'created_to'       => trim($_GET['created_to'] ?? ''),
            'website'          => trim($_GET['website'] ?? ''),
            'country'          => trim($_GET['country'] ?? ''),
            'province'         => trim($_GET['province'] ?? ''),
            'city'             => trim($_GET['city'] ?? ''),
            'address'          => trim($_GET['address'] ?? ''),
            'custom_fields'    => $this->customFieldFiltersFromRequest(),
        ];
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
}
