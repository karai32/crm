<?php

class ContactController
{
    private ContactRepository $contacts;
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->customFields = new CustomFieldRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = $this->filtersFromRequest();
        $total = $this->contacts->countAll($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $contacts = $this->contacts->paginate($page, $perPage, $filters);
        $contactIds = array_map('intval', array_column($contacts, 'id'));
        $selectedFilterTags = $this->selectedTagsForIds($filters['tag_ids'] ?? []);

        View::render('contacts/index', [
            'title'              => 'Contacts',
            'styles'             => ['contacts.css'],
            'contacts'           => $contacts,
            'contactTags'        => $this->contacts->tagsForContacts($contactIds),
            'contactClients'     => $this->contacts->clientsForContacts($contactIds),
            'page'               => $page,
            'perPage'            => $perPage,
            'totalPages'         => $totalPages,
            'total'              => $total,
            'filters'            => $filters,
            'filterClients'      => $this->contacts->firstClients(),
            'filterSectors'      => $this->contacts->allSectors(),
            'filterTags'         => $this->mergeSelectedRows($this->contacts->firstTags(), $selectedFilterTags),
            'customFilterFields' => $this->customFields->filterableFieldsForEntity('contact'),
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

        if ($data['first_name'] === '') {
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
                'error'            => 'First name is required.',
            ]);
            return;
        }

        $id = $this->contacts->create($data);
        $this->contacts->syncTags($id, $tagIds);
        $this->contacts->syncClients($id, $clientIds);
        $this->customFields->saveValues('contact', $id, $customFields, $customValues);
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

        if ($data['first_name'] === '') {
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
                'error'            => 'First name is required.',
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

    public function bulkTags(): void
    {
        Auth::requirePermission('contacts.edit');

        $contactIds = $this->entityIdsFromPost('contact_ids');
        $tagIds = $this->tagIdsFromRequest();
        $action = $_POST['bulk_action'] ?? '';

        if (!empty($contactIds) && !empty($tagIds)) {
            if ($action === 'remove') {
                $this->contacts->removeTags($contactIds, $tagIds);
            } else {
                $this->contacts->addTags($contactIds, $tagIds);
            }
        }

        Auth::redirect('/contacts');
    }

    private function contactDataFromRequest(): array
    {
        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => $this->emptyToNull($_POST['last_name'] ?? ''),
            'email'      => $this->emptyToNull($_POST['email'] ?? ''),
            'phone'      => $this->emptyToNull($_POST['phone'] ?? ''),
            'is_company' => isset($_POST['is_company']) ? 1 : 0,
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
            'first_name' => trim($_GET['first_name'] ?? ''),
            'last_name' => trim($_GET['last_name'] ?? ''),
            'email' => trim($_GET['email'] ?? ''),
            'phone' => trim($_GET['phone'] ?? ''),
            'is_company' => trim($_GET['is_company'] ?? ''),
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
