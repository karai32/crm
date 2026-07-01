<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class MailerService
{
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $textBody,
        string $htmlBody = ''
    ): bool {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = static function (string $str): void {
                logApplicationError('SMTP: ' . trim($str));
            };
            $mail->Host       = $config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'] === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $config['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $toName);

            $mail->Subject = $subject;

            if ($htmlBody !== '') {
                $mail->isHTML(true);
                $mail->Body    = $htmlBody;
                $mail->AltBody = $textBody;
            } else {
                $mail->isHTML(false);
                $mail->Body = $textBody;
            }

            $mail->send();

            return true;
        } catch (MailerException) {
            logApplicationError('Mailer error: ' . $mail->ErrorInfo);

            return false;
        }
    }
}
