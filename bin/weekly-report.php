<?php

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

$pdo  = Database::connect();
$stmt = $pdo->query("
    SELECT u.name, u.email
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    WHERE r.name = 'admin' AND u.is_active = 1
");
$recipients = $stmt->fetchAll();

if (empty($recipients)) {
    logApplicationError('Weekly report: no admin recipients found.');
    exit(0);
}

$service  = new WeeklyReportService();
$data     = $service->collect();

$from    = date('d/m/Y', strtotime($data['period_from']));
$to      = date('d/m/Y', strtotime($data['period_to']));
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
