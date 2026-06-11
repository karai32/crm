<?php

class TagController
{
    private TagRepository $tags;

    public function __construct()
    {
        $this->tags = new TagRepository();
    }

    public function index(): void
    {
        Auth::requirePermission('tags.manage');

        View::render('tags/index', [
            'title'  => 'Tags',
            'styles' => ['settings.css'],
            'tags'   => $this->tags->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('tags.manage');

        View::render('tags/create', [
            'title'  => 'Create tag',
            'styles' => ['settings.css'],
            'error'  => null,
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('tags.manage');

        $name = trim($_POST['name'] ?? '');
        $color = $this->cleanColor($_POST['color'] ?? '');

        if ($name === '') {
            View::render('tags/create', [
                'title'  => 'Create tag',
                'styles' => ['settings.css'],
                'error'  => 'Tag name is required.',
                'name'   => $name,
                'color'  => $color,
            ]);
            return;
        }

        $this->tags->create($name, $color);
        Auth::redirect('/tags');
    }

    public function edit(): void
    {
        Auth::requirePermission('tags.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $tag = $this->tags->find($id);

        if ($tag === null) {
            http_response_code(404);
            echo 'Tag not found';
            return;
        }

        View::render('tags/edit', [
            'title'  => 'Edit tag',
            'styles' => ['settings.css'],
            'tag'    => $tag,
            'error'  => null,
        ]);
    }

    public function update(): void
    {
        Auth::requirePermission('tags.manage');

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $color = $this->cleanColor($_POST['color'] ?? '');
        $tag = $this->tags->find($id);

        if ($tag === null) {
            http_response_code(404);
            echo 'Tag not found';
            return;
        }

        if ($name === '') {
            View::render('tags/edit', [
                'title'  => 'Edit tag',
                'styles' => ['settings.css'],
                'error'  => 'Tag name is required.',
                'tag'    => [
                    'id'    => $id,
                    'name'  => $name,
                    'color' => $color,
                ],
            ]);
            return;
        }

        $this->tags->update($id, $name, $color);
        Auth::redirect('/tags');
    }

    public function delete(): void
    {
        Auth::requirePermission('tags.manage');

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->tags->delete($id);
        }

        Auth::redirect('/tags');
    }

    private function cleanColor(string $color): ?string
    {
        $color = trim($color);

        if ($color === '') {
            return null;
        }

        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) ? $color : null;
    }
}
