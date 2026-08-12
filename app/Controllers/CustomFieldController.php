<?php

use Illuminate\Support\Str;

class CustomFieldController
{
    use SortableTrait, ControllerHelperTrait;

    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->customFields = new CustomFieldRepository();
    }

    public function index(): void
    {
        $sort  = $this->sortParam(['entity_type', 'name', 'slug', 'field_type'], 'entity_type');
        $dir   = $this->dirParam();
        $total = $this->customFields->count();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('custom-fields/index', [
            'title'      => Lang::get('cf.title'),
            'styles'     => ['settings.css'],
            'fields'     => $this->customFields->paginate($page, $perPage, $sort, $dir),
            'sort'       => $sort,
            'dir'        => $dir,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function create(): void
    {
        $this->renderForm(false);
    }

    public function store(): void
    {
        $data = $this->fieldDataFromRequest();
        $options = $this->optionsFromRequest();

        if ($data['name'] === '' || $data['slug'] === '') {
            $this->renderForm(false, $data, (string) ($_POST['options'] ?? ''), Lang::get('cf.name_slug_required'));
            return;
        }

        $id = $this->customFields->create($data);
        $this->customFields->setOptions($id, $options);
        Auth::redirect('/custom-fields');
    }

    public function edit(): void
    {
        $field = $this->findOrNotFound($this->customFields->find((int) ($_GET['id'] ?? 0)), 'Custom field not found');

        if ($field === null) {
            return;
        }

        $this->renderForm(true, $field);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $field = $this->findOrNotFound($this->customFields->find($id), 'Custom field not found');

        if ($field === null) {
            return;
        }

        $data = $this->fieldDataFromRequest();
        $options = $this->optionsFromRequest();

        if ($data['name'] === '' || $data['slug'] === '') {
            $data['id'] = $id;

            $this->renderForm(true, $data, (string) ($_POST['options'] ?? ''), Lang::get('cf.name_slug_required'));
            return;
        }

        $this->customFields->update($id, $data);
        $this->customFields->setOptions($id, $options);
        Auth::redirect('/custom-fields');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->customFields->delete($id);
        }

        Auth::redirect('/custom-fields');
    }

    private function fieldDataFromRequest(): array
    {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $defaultValue = trim($_POST['default_value'] ?? '');

        return [
            'entity_type' => in_array($_POST['entity_type'] ?? '', ['contact', 'client'], true) ? $_POST['entity_type'] : 'contact',
            'name' => $name,
            'slug' => Str::slug($slug !== '' ? $slug : $name),
            'field_type' => in_array($_POST['field_type'] ?? '', $this->fieldTypes(), true) ? $_POST['field_type'] : 'text',
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_filterable' => isset($_POST['is_filterable']) ? 1 : 0,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'default_value' => $defaultValue !== '' ? $defaultValue : null,
        ];
    }

    private function optionsFromRequest(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $_POST['options'] ?? '');
        $options = array_map('trim', $lines ?: []);
        $options = array_filter($options, fn ($option) => $option !== '');

        return array_values(array_unique($options));
    }

    private function fieldTypes(): array
    {
        return ['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'];
    }

    private function renderForm(
        bool $isEdit,
        array $field = [],
        ?string $optionsText = null,
        ?string $error = null
    ): void {
        if (!$isEdit && $field === []) {
            $field = ['entity_type' => 'contact', 'field_type' => 'text', 'is_filterable' => 1, 'sort_order' => 0];
        }
        if ($optionsText === null && $isEdit) {
            $options = array_column($this->customFields->optionsForField((int) ($field['id'] ?? 0)), 'value');
            $optionsText = implode("\n", $options);
        }

        View::render('custom-fields/_form', [
            'isEdit' => $isEdit,
            'title' => Lang::get($isEdit ? 'cf.edit_title' : 'cf.create_title'),
            'styles' => ['settings.css'],
            'scripts' => ['custom-fields.js'],
            'field' => $field,
            'optionsText' => $optionsText ?? '',
            'error' => $error,
        ]);
    }
}
