<?php

class CustomFieldController
{
    private CustomFieldRepository $customFields;

    public function __construct()
    {
        $this->customFields = new CustomFieldRepository();
    }

    public function index(): void
    {
        Auth::requirePermission('custom_fields.manage');

        View::render('custom-fields/index', [
            'title'  => 'Custom fields',
            'styles' => ['settings.css'],
            'fields' => $this->customFields->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('custom_fields.manage');

        View::render('custom-fields/create', [
            'title'       => 'Create custom field',
            'styles'      => ['settings.css'],
            'field'       => ['entity_type' => 'contact', 'field_type' => 'text', 'is_filterable' => 1, 'sort_order' => 0],
            'optionsText' => '',
            'error'       => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('custom_fields.manage');

        $data = $this->fieldDataFromRequest();
        $options = $this->optionsFromRequest();

        if ($data['name'] === '' || $data['slug'] === '') {
            View::render('custom-fields/create', [
                'title'       => 'Create custom field',
                'styles'      => ['settings.css'],
                'field'       => $data,
                'optionsText' => $_POST['options'] ?? '',
                'error'       => 'Name and slug are required.',
            ]);
            return;
        }

        $id = $this->customFields->create($data);
        $this->customFields->setOptions($id, $options);
        Auth::redirect('/custom-fields');
    }

    public function edit(): void
    {
        Auth::requirePermission('custom_fields.manage');

        $field = $this->customFields->find((int) ($_GET['id'] ?? 0));

        if ($field === null) {
            http_response_code(404);
            echo 'Custom field not found';
            return;
        }

        $options = array_column($this->customFields->optionsForField((int) $field['id']), 'value');

        View::render('custom-fields/edit', [
            'title'       => 'Edit custom field',
            'styles'      => ['settings.css'],
            'field'       => $field,
            'optionsText' => implode("\n", $options),
            'error'       => null,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('custom_fields.manage');

        $id = (int) ($_POST['id'] ?? 0);
        $field = $this->customFields->find($id);

        if ($field === null) {
            http_response_code(404);
            echo 'Custom field not found';
            return;
        }

        $data = $this->fieldDataFromRequest();
        $options = $this->optionsFromRequest();

        if ($data['name'] === '' || $data['slug'] === '') {
            $data['id'] = $id;

            View::render('custom-fields/edit', [
                'title'       => 'Edit custom field',
                'styles'      => ['settings.css'],
                'field'       => $data,
                'optionsText' => $_POST['options'] ?? '',
                'error'       => 'Name and slug are required.',
            ]);
            return;
        }

        $this->customFields->update($id, $data);
        $this->customFields->setOptions($id, $options);
        Auth::redirect('/custom-fields');
    }

    public function delete(): void
    {
        Auth::requirePermission('custom_fields.manage');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->customFields->delete($id);
        }

        Auth::redirect('/custom-fields');
    }

    private function fieldDataFromRequest(): array
    {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

        return [
            'entity_type' => in_array($_POST['entity_type'] ?? '', ['contact', 'client'], true) ? $_POST['entity_type'] : 'contact',
            'name' => $name,
            'slug' => $slug !== '' ? $this->makeSlug($slug) : $this->makeSlug($name),
            'field_type' => in_array($_POST['field_type'] ?? '', $this->fieldTypes(), true) ? $_POST['field_type'] : 'text',
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_filterable' => isset($_POST['is_filterable']) ? 1 : 0,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
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

    private function makeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }
}
