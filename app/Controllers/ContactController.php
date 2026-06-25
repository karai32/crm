<?php

class ContactController
{
    use SortableTrait;
    private ContactRepository $contacts;
    private CustomFieldRepository $customFields;
    private ClientRepository $clients;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->customFields = new CustomFieldRepository();
        $this->clients = new ClientRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = $this->filtersFromRequest();
        $sort    = $this->sortParam(['id', 'full_name', 'email', 'created_at', 'clients'], 'id');
        $dir     = $this->dirParam();
        $total = $this->contacts->countAll($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $contacts = $this->contacts->paginate($page, $perPage, $filters, $sort, $dir);
        $contactIds = array_map('intval', array_column($contacts, 'id'));
        $selectedFilterTags = $this->selectedTagsForIds($filters['tag_ids'] ?? []);

        $selectedClientId = (int) ($filters['client_id'] ?? 0);
        $filterClientName = '';
        $preselectedFilterClientJson = '[]';
        if ($selectedClientId > 0) {
            $sc = $this->clients->find($selectedClientId);
            if ($sc) {
                $filterClientName = $sc['commercial_name'];
                $preselectedFilterClientJson = json_encode([['id' => $selectedClientId, 'name' => $sc['commercial_name']]]);
            }
        }

        View::render('contacts/index', [
            'title'                      => 'Contacts',
            'styles'                     => ['contacts.css'],
            'contacts'                   => $contacts,
            'contactTags'                => $this->contacts->tagsForContacts($contactIds),
            'contactClients'             => $this->contacts->clientsForContacts($contactIds),
            'page'                       => $page,
            'perPage'                    => $perPage,
            'totalPages'                 => $totalPages,
            'total'                      => $total,
            'filters'                    => $filters,
            'sort'                       => $sort,
            'dir'                        => $dir,
            'filterClientName'           => $filterClientName,
            'preselectedFilterClientJson'=> $preselectedFilterClientJson,
            'filterSectors'              => $this->contacts->allSectors(),
            'filterTags'                 => $this->mergeSelectedRows($this->contacts->firstTags(), $selectedFilterTags),
            'customFilterFields'         => $this->customFields->filterableFieldsForEntity('contact'),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('contacts.create');

        View::render('contacts/create', [
            'title'            => 'Create contact',
            'styles'           => ['contacts.css'],
            'contact'          => [],
            'tags'             => $this->contacts->firstTags(),
            'selectedTagIds'   => [],
            'clients'          => $this->contacts->firstClients(),
            'selectedClientIds'=> [],
            'customFields'     => $this->customFields->fieldsForEntity('contact'),
            'customValues'     => [],
            'error'            => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('contacts.create');

        $data = $this->contactDataFromRequest();
        $tagIds = $this->tagIdsFromRequest();
        $clientIds = $this->clientIdsFromRequest();
        $customFields = $this->customFields->fieldsForEntity('contact');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['full_name'] === '') {
            View::render('contacts/create', [
                'title'            => 'Create contact',
                'styles'           => ['contacts.css'],
                'contact'          => $data,
                'tags'             => $this->contacts->firstTags(),
                'selectedTagIds'   => $tagIds,
                'clients'          => $this->contacts->firstClients(),
                'selectedClientIds'=> $clientIds,
                'customFields'     => $customFields,
                'customValues'     => $customValues,
                'error'            => 'Full name is required.',
            ]);
            return;
        }

        $id = $this->contacts->create($data);
        $this->contacts->syncTags($id, $tagIds);
        $this->contacts->syncClients($id, $clientIds);
        $this->customFields->saveValues('contact', $id, $customFields, $customValues, true);
        Auth::redirect('/contacts/show?id=' . $id);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $contact = $this->findContactOrFail((int) ($_GET['id'] ?? 0));

        if ($contact === null) {
            return;
        }

        View::render('contacts/show', [
            'title'                 => 'Contact details',
            'styles'                => ['contacts.css'],
            'contact'               => $contact,
            'tags'                  => $this->contacts->tagsForContact((int) $contact['id']),
            'clients'               => $this->contacts->clientsForContact((int) $contact['id']),
            'customFields'          => $this->customFields->fieldsForEntity('contact'),
            'customValues'          => $this->customFields->valuesForEntity('contact', (int) $contact['id']),
            'customFieldRepository' => $this->customFields,
        ]);
    }

    public function edit(): void
    {
        Auth::requirePermission('contacts.edit');

        $contact = $this->findContactOrFail((int) ($_GET['id'] ?? 0));

        if ($contact === null) {
            return;
        }

        $selectedTags = $this->contacts->tagsForContact((int) $contact['id']);
        $selectedClients = $this->contacts->clientsForContact((int) $contact['id']);

        View::render('contacts/edit', [
            'title'            => 'Edit contact',
            'styles'           => ['contacts.css'],
            'contact'          => $contact,
            'tags'             => $this->mergeSelectedRows($this->contacts->firstTags(), $selectedTags),
            'selectedTagIds'   => array_map('intval', array_column($selectedTags, 'id')),
            'clients'          => $this->mergeSelectedRows($this->contacts->firstClients(), $selectedClients),
            'selectedClientIds'=> array_map('intval', array_column($selectedClients, 'id')),
            'customFields'     => $this->customFields->fieldsForEntity('contact'),
            'customValues'     => $this->customFields->valuesForEntity('contact', (int) $contact['id']),
            'error'            => null,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('contacts.edit');

        $id = (int) ($_POST['id'] ?? 0);
        $contact = $this->findContactOrFail($id);

        if ($contact === null) {
            return;
        }

        $data = $this->contactDataFromRequest();
        $tagIds = $this->tagIdsFromRequest();
        $clientIds = $this->clientIdsFromRequest();
        $customFields = $this->customFields->fieldsForEntity('contact');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['full_name'] === '') {
            $data['id'] = $id;

            View::render('contacts/edit', [
                'title'            => 'Edit contact',
                'styles'           => ['contacts.css'],
                'contact'          => $data,
                'tags'             => $this->contacts->firstTags(),
                'selectedTagIds'   => $tagIds,
                'clients'          => $this->contacts->firstClients(),
                'selectedClientIds'=> $clientIds,
                'customFields'     => $customFields,
                'customValues'     => $customValues,
                'error'            => 'Full name is required.',
            ]);
            return;
        }

        $this->contacts->update($id, $data);
        $this->contacts->syncTags($id, $tagIds);
        $this->contacts->syncClients($id, $clientIds);
        $this->customFields->saveValues('contact', $id, $customFields, $customValues);
        Auth::redirect('/contacts/show?id=' . $id);
    }

    public function delete(): void
    {
        Auth::requirePermission('contacts.delete');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->contacts->delete($id);
        }

        Auth::redirect('/contacts');
    }

    public function bulkAction(): void
    {
        Auth::requireLogin();

        $contactIds = $this->entityIdsFromPost('contact_ids');
        $action = $_POST['bulk_action'] ?? '';

        if (!empty($contactIds)) {
            if ($action === 'delete') {
                Auth::requirePermission('contacts.delete');
                $this->contacts->deleteMultiple($contactIds);
            } elseif ($action === 'link_client') {
                Auth::requirePermission('contacts.edit');
                $clientIds = $this->linkClientIdsFromRequest();
                if (!empty($clientIds)) {
                    $this->contacts->addClientsToContacts($contactIds, $clientIds);
                }
            } elseif ($action === 'remove_tags') {
                Auth::requirePermission('contacts.edit');
                $tagIds = $this->tagIdsFromRequest();
                if (!empty($tagIds)) {
                    $this->contacts->removeTags($contactIds, $tagIds);
                }
            } else {
                Auth::requirePermission('contacts.edit');
                $tagIds = $this->tagIdsFromRequest();
                if (!empty($tagIds)) {
                    $this->contacts->addTags($contactIds, $tagIds);
                }
            }
        }

        Auth::redirect('/contacts');
    }

    private function contactDataFromRequest(): array
    {
        return [
            'full_name'  => trim($_POST['full_name'] ?? ''),
            'email'      => $this->emptyToNull($_POST['email'] ?? ''),
            'phone'      => $this->emptyToNull($_POST['phone'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
        ];
    }

    private function emptyToNull(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function tagIdsFromRequest(): array
    {
        $tagIds = $_POST['tag_ids'] ?? [];

        if (!is_array($tagIds)) {
            return [];
        }

        $tagIds = array_map('intval', $tagIds);
        $tagIds = array_filter($tagIds, fn ($id) => $id > 0);

        return array_values(array_unique($tagIds));
    }

    private function linkClientIdsFromRequest(): array
    {
        $clientIds = $_POST['link_client_ids'] ?? [];

        if (!is_array($clientIds)) {
            return [];
        }

        $clientIds = array_map('intval', $clientIds);
        $clientIds = array_filter($clientIds, fn ($id) => $id > 0);

        return array_values(array_unique($clientIds));
    }

    private function clientIdsFromRequest(): array
    {
        $clientIds = $_POST['client_ids'] ?? [];

        if (!is_array($clientIds)) {
            return [];
        }

        $clientIds = array_map('intval', $clientIds);
        $clientIds = array_filter($clientIds, fn ($id) => $id > 0);

        return array_values(array_unique($clientIds));
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

    private function filtersFromRequest(): array
    {
        return [
            'full_name' => trim($_GET['full_name'] ?? ''),
            'email' => trim($_GET['email'] ?? ''),
            'phone' => trim($_GET['phone'] ?? ''),
            'company' => trim($_GET['company'] ?? ''),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'sector_id' => (int) ($_GET['sector_id'] ?? 0),
            'tag_id' => (int) ($_GET['tag_id'] ?? 0),
            'tag_ids' => $this->tagIdsFromGet(),
            'country' => trim($_GET['country'] ?? ''),
            'province' => trim($_GET['province'] ?? ''),
            'created_from' => trim($_GET['created_from'] ?? ''),
            'created_to' => trim($_GET['created_to'] ?? ''),
            'updated_from' => trim($_GET['updated_from'] ?? ''),
            'updated_to' => trim($_GET['updated_to'] ?? ''),
            'custom_fields' => $this->customFieldFiltersFromRequest(),
        ];
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

        if (!is_array($tagIds)) {
            return [];
        }

        $tagIds = array_map('intval', $tagIds);
        $tagIds = array_filter($tagIds, fn ($id) => $id > 0);

        return array_values(array_unique($tagIds));
    }

    private function customFieldFiltersFromRequest(): array
    {
        $values = $_GET['custom_fields'] ?? [];

        if (!is_array($values)) {
            return [];
        }

        $clean = [];

        foreach ($values as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);

            if ($fieldId > 0 && $value !== '') {
                $clean[$fieldId] = $value;
            }
        }

        return $clean;
    }

    private function findContactOrFail(int $id): ?array
    {
        $contact = $this->contacts->find($id);

        if ($contact === null) {
            http_response_code(404);
            echo 'Contact not found';
            return null;
        }

        return $contact;
    }

    private function mergeSelectedRows(array $baseRows, array $selectedRows): array
    {
        $rows = [];

        foreach (array_merge($selectedRows, $baseRows) as $row) {
            $rows[(int) $row['id']] = $row;
        }

        return array_values($rows);
    }

    private function selectedTagsForIds(array $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        return array_values(array_filter($this->contacts->firstTags(500), function ($tag) use ($tagIds) {
            return in_array((int) $tag['id'], $tagIds, true);
        }));
    }
}
