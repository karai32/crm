<?php

class CustomFieldController
{
    use SortableTrait;

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
        View::render('custom-fields/_form', [
            'isEdit'  => false,
            'title'       => Lang::get('cf.create_title'),
            'styles'      => ['settings.css'],
            'scripts'     => ['custom-fields.js'],
            'field'       => ['entity_type' => 'contact', 'field_type' => 'text', 'is_filterable' => 1, 'sort_order' => 0],
            'optionsText' => '',
            'error'       => null,
        ]);
    }

    public function store(): void
    {
        $data = $this->fieldDataFromRequest();
        $options = $this->optionsFromRequest();

        if ($data['name'] === '' || $data['slug'] === '') {
            View::render('custom-fields/_form', [
                'isEdit'     => false,
                'title'       => Lang::get('cf.create_title'),
                'styles'      => ['settings.css'],
                'field'       => $data,
                'optionsText' => $_POST['options'] ?? '',
                'error'       => Lang::get('cf.name_slug_required'),
            ]);
            return;
        }

        $id = $this->customFields->create($data);
        $this->customFields->setOptions($id, $options);
        Auth::redirect('/custom-fields');
    }

    public function edit(): void
    {
        $field = $this->customFields->find((int) ($_GET['id'] ?? 0));

        if ($field === null) {
            http_response_code(404);
            echo 'Custom field not found';
            return;
        }

        $options = array_column($this->customFields->optionsForField((int) $field['id']), 'value');

        View::render('custom-fields/_form', [
            'isEdit'     => true,
            'title'       => Lang::get('cf.edit_title'),
            'styles'      => ['settings.css'],
            'scripts'     => ['custom-fields.js'],
            'field'       => $field,
            'optionsText' => implode("\n", $options),
            'error'       => null,
        ]);
    }

    public function update(): void
    {
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

            View::render('custom-fields/_form', [
                'isEdit'     => true,
                'title'       => Lang::get('cf.edit_title'),
                'styles'      => ['settings.css'],
                'field'       => $data,
                'optionsText' => $_POST['options'] ?? '',
                'error'       => Lang::get('cf.name_slug_required'),
            ]);
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
            'slug' => Slugger::make($slug !== '' ? $slug : $name),
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

}
