<?php

class ContactController
{
    use SortableTrait, ControllerHelperTrait;

    private ContactRepository $contacts;
    private CustomFieldRepository $customFields;
    private ClientRepository $clients;
    private TagRepository $tags;
    private EntityTagRepository $entityTags;

    public function __construct()
    {
        $this->contacts = new ContactRepository();
        $this->customFields = new CustomFieldRepository();
        $this->clients = new ClientRepository();
        $this->tags = new TagRepository();
        $this->entityTags = new EntityTagRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $filters = $this->filtersFromRequest();
        $sort    = $this->sortParam(['id', 'full_name', 'email', 'created_at', 'clients'], 'id');
        $dir     = $this->dirParam();
        $total   = $this->contacts->countAll($filters);
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        $contacts = $this->contacts->paginate($page, $perPage, $filters, $sort, $dir);
        $contactIds = array_map('intval', array_column($contacts, 'id'));
        $selectedFilterTags = $this->filterRowsByIds($this->tags->first(500), $filters['tag_ids'] ?? []);

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
            'title'                      => Lang::get('contacts.title'),
            'styles'                     => ['contacts.css'],
            'scripts'                    => ['list-page.js'],
            'contacts'                   => $contacts,
            'contactTags'                => $this->entityTags->tagsForEntities('contact', $contactIds),
            'contactClients'             => $this->contacts->clientsForContacts($contactIds),
            'page'                       => $page,
            'perPage'                    => $perPage,
            'totalPages'                 => $totalPages,
            'total'                      => $total,
            'filters'                    => $filters,
            'sort'                       => $sort,
            'dir'                        => $dir,
            'filterClientName'           => $filterClientName,
            'preselectedFilterClientJson' => $preselectedFilterClientJson,
            'filterSectors'              => $this->contacts->allSectors(),
            'filterTags'                 => $this->mergeSelectedRows($this->tags->first(), $selectedFilterTags),
            'customFilterFields'         => $this->customFields->filterableFieldsForEntity('contact'),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('contacts.create');

        View::render('contacts/create', [
            'title'            => Lang::get('contacts.create_title'),
            'styles'           => ['contacts.css'],
            'contact'          => [],
            'tags'             => $this->tags->first(),
            'selectedTagIds'   => [],
            'clients'          => $this->contacts->firstClients(),
            'selectedClientIds' => [],
            'customFields'     => $this->customFields->fieldsForEntity('contact'),
            'customValues'     => [],
            'error'            => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('contacts.create');

        $data = $this->contactDataFromRequest();
        $tagIds = $this->idsFromPost('tag_ids');
        $clientIds = $this->idsFromPost('client_ids');
        $customFields = $this->customFields->fieldsForEntity('contact');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['full_name'] === '') {
            View::render('contacts/create', [
                'title'            => Lang::get('contacts.create_title'),
                'styles'           => ['contacts.css'],
                'contact'          => $data,
                'tags'             => $this->tags->first(),
                'selectedTagIds'   => $tagIds,
                'clients'          => $this->contacts->firstClients(),
                'selectedClientIds' => $clientIds,
                'customFields'     => $customFields,
                'customValues'     => $customValues,
                'error'            => Lang::get('contacts.name_required'),
            ]);
            return;
        }

        $data = array_merge($data, EmailInspector::inspect($data['email']));
        $id = $this->contacts->create($data);
        $this->entityTags->sync('contact', $id, $tagIds);
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
            'title'                 => Lang::get('contacts.details_title'),
            'styles'                => ['contacts.css'],
            'contact'               => $contact,
            'tags'                  => $this->entityTags->tagsForEntity('contact', (int) $contact['id']),
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

        $selectedTags = $this->entityTags->tagsForEntity('contact', (int) $contact['id']);
        $selectedClients = $this->contacts->clientsForContact((int) $contact['id']);

        View::render('contacts/edit', [
            'title'            => Lang::get('contacts.edit_title'),
            'styles'           => ['contacts.css'],
            'contact'          => $contact,
            'tags'             => $this->mergeSelectedRows($this->tags->first(), $selectedTags),
            'selectedTagIds'   => array_map('intval', array_column($selectedTags, 'id')),
            'clients'          => $this->mergeSelectedRows($this->contacts->firstClients(), $selectedClients),
            'selectedClientIds' => array_map('intval', array_column($selectedClients, 'id')),
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
        $manualEmailState = $this->manualEmailStateFromRequest();
        $tagIds = $this->idsFromPost('tag_ids');
        $clientIds = $this->idsFromPost('client_ids');
        $customFields = $this->customFields->fieldsForEntity('contact');
        $customValues = $_POST['custom_fields'] ?? [];

        if ($data['full_name'] === '') {
            $data['id'] = $id;

            if ($manualEmailState !== null) {
                $data = array_merge($data, $manualEmailState);
            }

            View::render('contacts/edit', [
                'title'            => Lang::get('contacts.edit_title'),
                'styles'           => ['contacts.css'],
                'contact'          => $data,
                'tags'             => $this->tags->first(),
                'selectedTagIds'   => $tagIds,
                'clients'          => $this->contacts->firstClients(),
                'selectedClientIds' => $clientIds,
                'customFields'     => $customFields,
                'customValues'     => $customValues,
                'error'            => Lang::get('contacts.name_required'),
            ]);
            return;
        }

        $data = array_merge($data, $data['email'] !== null && $manualEmailState !== null
            ? $manualEmailState
            : EmailInspector::inspect($data['email']));
        $this->contacts->update($id, $data);
        $this->entityTags->sync('contact', $id, $tagIds);
        $this->contacts->syncClients($id, $clientIds);
        $this->customFields->saveValues('contact', $id, $customFields, $customValues);
        Auth::redirect('/contacts/show?id=' . $id);
    }

    public function delete(): void
    {
        Auth::requirePermission('contacts.delete');

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->contacts->delete($id);
        }

        Auth::redirect('/contacts');
    }

    public function bulkAction(): void
    {
        Auth::requireLogin();

        $contactIds = $this->idsFromPost('contact_ids');
        $action = $_POST['bulk_action'] ?? '';

        if (!empty($contactIds)) {
            if ($action === 'delete') {
                Auth::requirePermission('contacts.delete');
                $this->contacts->deleteMultiple($contactIds);
            } elseif ($action === 'link_client') {
                Auth::requirePermission('contacts.edit');
                $clientIds = $this->idsFromPost('link_client_ids');
                if (!empty($clientIds)) {
                    $this->contacts->addClientsToContacts($contactIds, $clientIds);
                }
            } elseif ($action === 'remove_tags') {
                Auth::requirePermission('contacts.edit');
                $tagIds = $this->idsFromPost('tag_ids');
                if (!empty($tagIds)) {
                    $this->entityTags->remove('contact', $contactIds, $tagIds);
                }
            } else {
                Auth::requirePermission('contacts.edit');
                $tagIds = $this->idsFromPost('tag_ids');
                if (!empty($tagIds)) {
                    $this->entityTags->add('contact', $contactIds, $tagIds);
                }
            }
        }

        Auth::redirect('/contacts');
    }

    /**
     * Manual email-state override from the edit form switcher.
     * Returns null when nothing was selected (fall back to EmailInspector).
     */
    private function manualEmailStateFromRequest(): ?array
    {
        return match ($_POST['email_state'] ?? '') {
            'corporate' => ['is_corporate_email' => 1, 'email_status' => 'valid'],
            'consumer'  => ['is_corporate_email' => 0, 'email_status' => 'valid'],
            'invalid'   => ['is_corporate_email' => null, 'email_status' => 'invalid'],
            default     => null,
        };
    }

    private function contactDataFromRequest(): array
    {
        return [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => $this->emptyToNull($_POST['email'] ?? ''),
            'phone'     => $this->emptyToNull($_POST['phone'] ?? ''),
            'company'   => trim($_POST['company'] ?? ''),
        ];
    }

    private function filtersFromRequest(): array
    {
        return [
            'full_name'     => trim($_GET['full_name'] ?? ''),
            'email'         => trim($_GET['email'] ?? ''),
            'phone'         => trim($_GET['phone'] ?? ''),
            'client_id'     => (int) ($_GET['client_id'] ?? 0),
            'sector_id'     => (int) ($_GET['sector_id'] ?? 0),
            'tag_ids'       => $this->tagIdsFromGet(),
            'created_from'  => trim($_GET['created_from'] ?? ''),
            'created_to'    => trim($_GET['created_to'] ?? ''),
            'updated_from'       => trim($_GET['updated_from'] ?? ''),
            'updated_to'         => trim($_GET['updated_to'] ?? ''),
            'is_corporate_email' => $_GET['is_corporate_email'] ?? '',
            'email_status'       => $_GET['email_status'] ?? '',
            'custom_fields'      => $this->customFieldFiltersFromRequest(),
        ];
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
}
