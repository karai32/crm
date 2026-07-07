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
            'title'        => Lang::get('settings.title'),
            'styles'       => ['settings.css'],
            'prefs'        => $this->prefs->all($userId),
            'reportStatus' => $_GET['report'] ?? null,
        ]);
    }

    public function sendReport(): void
    {
        Auth::requireLogin();

        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit;
        }

        $user    = Auth::user();
        $service = new WeeklyReportService();
        $dayOfWeek = (int) date('N'); // 1=Monday … 7=Sunday
        $mondayTs  = mktime(0, 0, 0) - ($dayOfWeek - 1) * 86400;
        $data    = $service->collect(date('Y-m-d 00:00:00', $mondayTs));

        $from    = date('d/m/Y', strtotime($data['period_from']));
        $to      = date('d/m/Y', strtotime($data['period_to']));
        $subject = "Informe CRM (manual) — Del {$from} al {$to}";

        $ok = MailerService::send(
            $user['email'],
            $user['name'],
            $subject,
            $service->buildText($data),
            $service->buildHtml($data)
        );

        Auth::redirect('/settings?report=' . ($ok ? 'sent' : 'error'));
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
