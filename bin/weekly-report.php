<?php

use Illuminate\Support\Carbon;

$root = dirname(__DIR__);

function logApplicationError(string $message): void
{
    $logPath = dirname(__DIR__) . '/storage/app.log';
    $entry   = '[' . date('c') . '] ' . $message . PHP_EOL;
    @file_put_contents($logPath, $entry, FILE_APPEND);
    error_log($message);
}

require_once $root . '/vendor/autoload.php';
require_once $root . '/app/Core/Database.php';
require_once $root . '/app/Services/MailerService.php';
require_once $root . '/app/Services/WeeklyReportService.php';

$recipients = Database::rows(
    Database::table('users as u')
        ->join('roles as r', 'r.id', '=', 'u.role_id')
        ->select(['u.name', 'u.email'])
        ->where('r.name', 'admin')
        ->where('u.is_active', 1)
);

if (empty($recipients)) {
    logApplicationError('Weekly report: no admin recipients found.');
    exit(0);
}

$service  = new WeeklyReportService();
$data     = $service->collect();

$from    = Carbon::parse($data['period_from'])->format('d/m/Y');
$to      = Carbon::parse($data['period_to'])->format('d/m/Y');
$subject = "Informe Semanal CRM — Del {$from} al {$to}";

$htmlBody = $service->buildHtml($data);
$textBody = $service->buildText($data);

$sent   = 0;
$errors = 0;

foreach ($recipients as $r) {
    if (MailerService::send($r['email'], $r['name'], $subject, $textBody, $htmlBody)) {
        $sent++;
    } else {
        $errors++;
        logApplicationError("Weekly report: failed to send to {$r['email']}");
    }
}

echo "Weekly report: {$sent} sent, {$errors} errors.\n";
