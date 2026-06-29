<?php

class SettingsController
{
    private SettingsRepository $settings;

    public function __construct()
    {
        $this->settings = new SettingsRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();

        View::render('settings/index', [
            'title'    => 'Settings',
            'styles'   => ['settings.css'],
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(): void
    {
        Auth::requireAdmin();

        $perPage = (int) ($_POST['per_page'] ?? 20);
        $perPage = max(5, min(500, $perPage));
        $this->settings->set('per_page', $perPage);

        Auth::redirect('/settings');
    }
}
