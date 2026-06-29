<?php

class SettingsController
{
    private SettingsRepository $prefs;

    public function __construct()
    {
        $this->prefs = new SettingsRepository();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $user   = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        View::render('settings/index', [
            'title'    => 'Settings',
            'styles'   => ['settings.css'],
            'prefs'    => $this->prefs->all($userId),
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();

        $user   = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        $perPage = (int) ($_POST['per_page'] ?? 20);
        $perPage = max(5, min(500, $perPage));
        $this->prefs->set($userId, 'per_page', $perPage);

        Auth::redirect('/settings');
    }
}
