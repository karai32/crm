<?php

class SectorController
{
    use SortableTrait, ControllerHelperTrait;

    private SectorRepository $sectors;
    private PhosphorIconCatalog $icons;

    public function __construct()
    {
        $this->sectors = new SectorRepository();
        $this->icons = new PhosphorIconCatalog();
    }

    public function index(): void
    {
        $sort  = $this->sortParam(['name', 'slug', 'is_active'], 'name');
        $dir   = $this->dirParam('asc');
        $total = $this->sectors->count();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('sectors/index', [
            'title'      => Lang::get('sectors.title'),
            'styles'     => ['settings.css'],
            'sectors'    => $this->sectors->paginate($page, $perPage, $sort, $dir),
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
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $this->renderForm(false, ['name' => $name], Lang::get('sectors.name_required'));
            return;
        }

        $this->sectors->create($name);
        Auth::redirect('/sectors');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $sector = $this->findOrNotFound($this->sectors->find($id), 'Sector not found');

        if ($sector === null) {
            return;
        }

        $this->renderForm(true, $sector);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $iconInput = $_POST['icon'] ?? null;
        $icon = $this->icons->normalize($iconInput);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sector = $this->findOrNotFound($this->sectors->find($id), 'Sector not found');

        if ($sector === null) {
            return;
        }

        if ($name === '' || !$this->icons->isValid($iconInput)) {
            $this->renderForm(
                true,
                ['id' => $id, 'name' => $name, 'icon' => $icon, 'is_active' => $isActive],
                $name === '' ? Lang::get('sectors.name_required') : Lang::get('sectors.choose_icon')
            );
            return;
        }

        $this->sectors->update($id, $name, $isActive, $icon);
        Auth::redirect('/sectors');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->sectors->deleteOrDeactivate($id);
        }

        Auth::redirect('/sectors');
    }

    private function renderForm(bool $isEdit, array $sector = [], ?string $error = null): void
    {
        View::render('sectors/_form', [
            'isEdit' => $isEdit,
            'title' => Lang::get($isEdit ? 'sectors.edit_title' : 'sectors.create_title'),
            'styles' => ['settings.css'],
            'sector' => $sector,
            'error' => $error,
            'recommendedIcons' => $isEdit ? $this->icons->recommended() : [],
            'defaultIcon' => $this->icons->defaultIcon(),
        ]);
    }
}
