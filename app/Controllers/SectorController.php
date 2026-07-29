<?php

class SectorController
{
    use SortableTrait;

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
        View::render('sectors/create', [
            'title'  => Lang::get('sectors.create_title'),
            'styles' => ['settings.css'],
            'error'  => null,
        ]);
    }

    public function store(): void
    {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            View::render('sectors/create', [
                'title'  => Lang::get('sectors.create_title'),
                'styles' => ['settings.css'],
                'error'  => Lang::get('sectors.name_required'),
                'name'   => $name,
            ]);
            return;
        }

        $this->sectors->create($name);
        Auth::redirect('/sectors');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $sector = $this->sectors->find($id);

        if ($sector === null) {
            http_response_code(404);
            echo 'Sector not found';
            return;
        }

        View::render('sectors/edit', [
            'title'            => Lang::get('sectors.edit_title'),
            'styles'           => ['settings.css'],
            'sector'           => $sector,
            'error'            => null,
            'recommendedIcons' => $this->icons->recommended(),
            'defaultIcon'      => $this->icons->defaultIcon(),
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $iconInput = $_POST['icon'] ?? null;
        $icon = $this->icons->normalize($iconInput);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sector = $this->sectors->find($id);

        if ($sector === null) {
            http_response_code(404);
            echo 'Sector not found';
            return;
        }

        if ($name === '' || !$this->icons->isValid($iconInput)) {
            View::render('sectors/edit', [
                'title'            => Lang::get('sectors.edit_title'),
                'styles'           => ['settings.css'],
                'error'            => $name === '' ? Lang::get('sectors.name_required') : Lang::get('sectors.choose_icon'),
                'recommendedIcons' => $this->icons->recommended(),
                'defaultIcon'      => $this->icons->defaultIcon(),
                'sector'           => [
                    'id'        => $id,
                    'name'      => $name,
                    'icon'      => $icon,
                    'is_active' => $isActive,
                ],
            ]);
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
}
