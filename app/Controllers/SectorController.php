<?php

class SectorController
{
    private SectorRepository $sectors;

    public function __construct()
    {
        $this->sectors = new SectorRepository();
    }

    public function index(): void
    {
        Auth::requirePermission('sectors.manage');

        View::render('sectors/index', [
            'title'   => 'Sectors',
            'styles'  => ['settings.css'],
            'sectors' => $this->sectors->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('sectors.manage');

        View::render('sectors/create', [
            'title'  => 'Create sector',
            'styles' => ['settings.css'],
            'error'  => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('sectors.manage');

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            View::render('sectors/create', [
                'title'  => 'Create sector',
                'styles' => ['settings.css'],
                'error'  => 'Sector name is required.',
                'name'   => $name,
            ]);
            return;
        }

        $this->sectors->create($name);
        Auth::redirect('/sectors');
    }

    public function edit(): void
    {
        Auth::requirePermission('sectors.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $sector = $this->sectors->find($id);

        if ($sector === null) {
            http_response_code(404);
            echo 'Sector not found';
            return;
        }

        View::render('sectors/edit', [
            'title'  => 'Edit sector',
            'styles' => ['settings.css'],
            'sector' => $sector,
            'error'  => null,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('sectors.manage');

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sector = $this->sectors->find($id);

        if ($sector === null) {
            http_response_code(404);
            echo 'Sector not found';
            return;
        }

        if ($name === '') {
            View::render('sectors/edit', [
                'title'  => 'Edit sector',
                'styles' => ['settings.css'],
                'error'  => 'Sector name is required.',
                'sector' => [
                    'id'        => $id,
                    'name'      => $name,
                    'is_active' => $isActive,
                ],
            ]);
            return;
        }

        $this->sectors->update($id, $name, $isActive);
        Auth::redirect('/sectors');
    }

    public function delete(): void
    {
        Auth::requirePermission('sectors.manage');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->sectors->deleteOrDeactivate($id);
        }

        Auth::redirect('/sectors');
    }
}
