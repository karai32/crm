<?php

class TwoFactorService
{
    private const SESSION_KEY = 'two_factor_login';
    private const CODE_TTL_SECONDS = 600;
    private const RESEND_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function start(array $user): bool
    {
        $code = (string) random_int(100000, 999999);

        $_SESSION[self::SESSION_KEY] = [
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => time() + self::CODE_TTL_SECONDS,
            'last_sent_at' => time(),
            'attempts' => 0,
        ];

        return $this->sendCode($user['email'], $user['name'], $code);
    }

    public function resend(): bool
    {
        $pending = $this->pending();

        if ($pending === null || !$this->canResend()) {
            return false;
        }

        return $this->start($pending['user']);
    }

    public function verify(string $code): ?array
    {
        $pending = $this->pending();

        if ($pending === null) {
            return null;
        }

        if (time() > (int) $pending['expires_at']) {
            $this->clear();
            return null;
        }

        $pending['attempts'] = (int) $pending['attempts'] + 1;
        $_SESSION[self::SESSION_KEY] = $pending;

        if ($pending['attempts'] > self::MAX_ATTEMPTS) {
            $this->clear();
            return null;
        }

        if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, $pending['code_hash'])) {
            return null;
        }

        $user = $pending['user'];
        $this->clear();

        return $user;
    }

    public function pending(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public function hasPending(): bool
    {
        return $this->pending() !== null;
    }

    public function canResend(): bool
    {
        $pending = $this->pending();

        if ($pending === null) {
            return false;
        }

        return time() - (int) ($pending['last_sent_at'] ?? 0) >= self::RESEND_SECONDS;
    }

    public function maskedEmail(): string
    {
        $email = (string) ($this->pending()['user']['email'] ?? '');
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return $email;
        }

        $visible = substr($local, 0, 2);

        return $visible . str_repeat('*', max(2, strlen($local) - 2)) . '@' . $domain;
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function sendCode(string $email, string $name, string $code): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/mail.php';
        $fromEmail = $config['from_email'] ?? 'no-reply@localhost';
        $fromName = $config['from_name'] ?? 'CRM';
        $subject = 'Your CRM login code';
        $safeName = trim($name) !== '' ? $name : 'there';
        $message = "Hello {$safeName},\n\nYour CRM login code is: {$code}\n\nThis code expires in 10 minutes.";
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }
}
