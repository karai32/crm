<?php

use Illuminate\Support\Carbon;

class SettingsController
{
    private SettingsRepository $prefs;

    public function __construct()
    {
        $this->prefs = new SettingsRepository();
    }

    public function index(): void
    {
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
        $user    = Auth::user();
        $service = new WeeklyReportService();
        $data = $service->collect(
            Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay()->toDateTimeString()
        );

        $from    = Carbon::parse($data['period_from'])->format('d/m/Y');
        $to      = Carbon::parse($data['period_to'])->format('d/m/Y');
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
        $user   = Auth::user();
        $userId = (int) ($user['id'] ?? 0);

        $perPage = (int) ($_POST['per_page'] ?? 20);
        $perPage = max(5, min(500, $perPage));
        $this->prefs->set($userId, 'per_page', $perPage);

        Auth::redirect('/settings');
    }
}
