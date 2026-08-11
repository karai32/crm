<?php

class TagController
{
    use SortableTrait, ControllerHelperTrait;

    private TagRepository $tags;

    public function __construct()
    {
        $this->tags = new TagRepository();
    }

    public function index(): void
    {
        $sort  = $this->sortParam(['name', 'slug'], 'name');
        $dir   = $this->dirParam('asc');
        $total = $this->tags->count();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        View::render('tags/index', [
            'title'      => Lang::get('tags.title'),
            'styles'     => ['settings.css'],
            'tags'       => $this->tags->paginate($page, $perPage, $sort, $dir),
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
        $color = $this->cleanColor($_POST['color'] ?? '');

        if ($name === '') {
            $this->renderForm(false, ['name' => $name, 'color' => $color], Lang::get('tags.name_required'));
            return;
        }

        $this->tags->create($name, $color);
        Auth::redirect('/tags');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $tag = $this->tags->find($id);

        if ($tag === null) {
            $this->notFound('Tag not found');
            return;
        }

        $this->renderForm(true, $tag);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $color = $this->cleanColor($_POST['color'] ?? '');
        $tag = $this->tags->find($id);

        if ($tag === null) {
            $this->notFound('Tag not found');
            return;
        }

        if ($name === '') {
            $this->renderForm(
                true,
                ['id' => $id, 'name' => $name, 'color' => $color],
                Lang::get('tags.name_required')
            );
            return;
        }

        $this->tags->update($id, $name, $color);
        Auth::redirect('/tags');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

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

    private function renderForm(bool $isEdit, array $tag = [], ?string $error = null): void
    {
        View::render('tags/_form', [
            'isEdit' => $isEdit,
            'title' => Lang::get($isEdit ? 'tags.edit_title' : 'tags.create_title'),
            'styles' => ['settings.css'],
            'scripts' => ['color-picker.js'],
            'tag' => $tag,
            'error' => $error,
        ]);
    }
}
