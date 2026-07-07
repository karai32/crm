<?php

class UserController
{
    use SortableTrait;

    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $sort  = $this->sortParam(['id', 'name', 'role', 'is_active'], 'id');
        $dir   = $this->dirParam();
        $total = $this->users->count();
        [$page, $perPage, $totalPages] = $this->pageParams($total);

        $users              = $this->users->paginate($page, $perPage, $sort, $dir);
        $defaultPermissions = $this->defaultPermissions();

        foreach ($users as $index => $user) {
            $role = $user['role'] ?? 'user';
            $users[$index]['permissions'] = $role === 'admin'
                ? $defaultPermissions
                : array_replace($defaultPermissions, $this->users->permissionsForUser((int) $user['id']));
        }

        View::render('users/index', [
            'title'                 => Lang::get('users.title'),
            'styles'                => ['settings.css'],
            'users'                 => $users,
            'permissionDefinitions' => Auth::permissionDefinitions(),
            'sort'                  => $sort,
            'dir'                   => $dir,
            'page'                  => $page,
            'perPage'               => $perPage,
            'total'                 => $total,
            'totalPages'            => $totalPages,
        ]);
    }



    public function create(): void
    {
        Auth::requireAdmin();
        $defaultRole = $this->users->findRoleByName('user');

        View::render('users/create', [
            'title'   => Lang::get('users.create_title'),
            'styles'  => ['settings.css'],
            'scripts' => ['users.js'],
            'user'   => ['is_active' => 1, 'role_id' => (int) ($defaultRole['id'] ?? 0)],
            'roles'  => $this->users->allRoles(),
            'permissionDefinitions' => Auth::permissionDefinitions(),
            'userPermissions' => $this->defaultPermissions(),
            'error'  => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $data = $this->userDataFromRequest();
        $password = $_POST['password'] ?? '';

        if ($data['name'] === '' || $data['email'] === '' || $password === '') {
            View::render('users/create', [
                'title'  => Lang::get('users.create_title'),
                'styles' => ['settings.css'],
                'user'   => $data,
                'roles'  => $this->users->allRoles(),
                'permissionDefinitions' => Auth::permissionDefinitions(),
                'userPermissions' => $this->permissionsFromRequest(),
                'error'  => Lang::get('users.create_required'),
            ]);
            return;
        }

        if ($this->users->findByEmail($data['email']) !== null) {
            View::render('users/create', [
                'title'  => Lang::get('users.create_title'),
                'styles' => ['settings.css'],
                'user'   => $data,
                'roles'  => $this->users->allRoles(),
                'permissionDefinitions' => Auth::permissionDefinitions(),
                'userPermissions' => $this->permissionsFromRequest(),
                'error'  => Lang::get('users.email_already_used'),
            ]);
            return;
        }

        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->users->create($data);
        $this->users->savePermissions($id, $this->permissionsForRole((int) $data['role_id']));

        Auth::redirect('/users');
    }

    public function edit(): void
    {
        Auth::requireAdmin();

        $user = $this->findUserOrFail((int) ($_GET['id'] ?? 0));

        if ($user === null) {
            return;
        }

        View::render('users/edit', [
            'title'   => Lang::get('users.edit_title'),
            'styles'  => ['settings.css'],
            'scripts' => ['users.js'],
            'user'   => $user,
            'roles'  => $this->users->allRoles(),
            'permissionDefinitions' => Auth::permissionDefinitions(),
            'userPermissions' => $this->permissionsForEdit($user),
            'error'  => null,
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $existingUser = $this->findUserOrFail($id);

        if ($existingUser === null) {
            return;
        }

        $data = $this->userDataFromRequest();
        $password = $_POST['password'] ?? '';
        $currentUser = Auth::user();

        if (($currentUser['id'] ?? null) == $id && $data['is_active'] === 0) {
            View::render('users/edit', [
                'title'  => Lang::get('users.edit_title'),
                'styles' => ['settings.css'],
                'user'   => array_merge($existingUser, $data),
                'roles'  => $this->users->allRoles(),
                'permissionDefinitions' => Auth::permissionDefinitions(),
                'userPermissions' => $this->permissionsFromRequest(),
                'error'  => Lang::get('users.cannot_deactivate_own'),
            ]);
            return;
        }

        if ($data['name'] === '' || $data['email'] === '') {
            View::render('users/edit', [
                'title'  => Lang::get('users.edit_title'),
                'styles' => ['settings.css'],
                'user'   => array_merge($existingUser, $data),
                'roles'  => $this->users->allRoles(),
                'permissionDefinitions' => Auth::permissionDefinitions(),
                'userPermissions' => $this->permissionsFromRequest(),
                'error'  => Lang::get('users.edit_required'),
            ]);
            return;
        }

        $userWithEmail = $this->users->findByEmail($data['email']);

        if ($userWithEmail !== null && (int) $userWithEmail['id'] !== $id) {
            View::render('users/edit', [
                'title'  => Lang::get('users.edit_title'),
                'styles' => ['settings.css'],
                'user'   => array_merge($existingUser, $data),
                'roles'  => $this->users->allRoles(),
                'permissionDefinitions' => Auth::permissionDefinitions(),
                'userPermissions' => $this->permissionsFromRequest(),
                'error'  => Lang::get('users.email_already_used'),
            ]);
            return;
        }

        $this->users->update($id, $data);
        $this->users->savePermissions($id, $this->permissionsForRole((int) $data['role_id']));

        if ($password !== '') {
            $this->users->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::redirect('/users');
    }

    public function delete(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $currentUser = Auth::user();

        if ($id > 0 && ($currentUser['id'] ?? null) != $id) {
            $this->users->deactivate($id);
        }

        Auth::redirect('/users');
    }

    public function purge(): void
    {
        Auth::requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        $currentUser = Auth::user();

        if ($id <= 0 || ($currentUser['id'] ?? null) == $id) {
            Auth::redirect('/users');
        }

        $user = $this->users->find($id);

        if ($user !== null && (int) $user['is_active'] === 0) {
            $this->users->purge($id);
        }

        Auth::redirect('/users');
    }

    private function userDataFromRequest(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role_id' => (int) ($_POST['role_id'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function findUserOrFail(int $id): ?array
    {
        $user = $this->users->find($id);

        if ($user === null) {
            http_response_code(404);
            echo 'User not found';
            return null;
        }

        return $user;
    }

    private function defaultPermissions(): array
    {
        return array_fill_keys(array_keys(Auth::permissionDefinitions()), true);
    }

    private function permissionsFromRequest(): array
    {
        $selected = $_POST['permissions'] ?? [];
        $selected = is_array($selected) ? $selected : [];
        $permissions = [];

        foreach (Auth::permissionDefinitions() as $permissionKey => $label) {
            $permissions[$permissionKey] = in_array($permissionKey, $selected, true);
        }

        return $permissions;
    }

    private function permissionsForEdit(array $user): array
    {
        return array_replace($this->defaultPermissions(), $this->users->permissionsForUser((int) $user['id']));
    }

    private function permissionsForRole(int $roleId): array
    {
        foreach ($this->users->allRoles() as $role) {
            if ((int) $role['id'] === $roleId && ($role['name'] ?? '') === 'user') {
                return $this->permissionsFromRequest();
            }
        }

        return [];
    }
}
