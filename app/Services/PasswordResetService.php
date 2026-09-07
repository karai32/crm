<?php

// Stateless reset tokens: the signature is keyed by the user's current password_hash,
// so no database storage is needed and a token stops working the moment the password changes.
class PasswordResetService
{
    private const TTL_SECONDS = 3600;

    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function sendResetLink(string $email): void
    {
        $user = $this->users->findByEmail(trim($email));

        if ($user === null || (int) $user['is_active'] !== 1) {
            return;
        }

        $token = $this->makeToken((int) $user['id'], $user['password_hash']);
        $link = $this->baseUrl() . '/password/reset?' . http_build_query(['token' => $token]);

        $this->sendEmail($user['email'], $user['name'], $link);
    }

    // ?{id, role_id, name, email, password_hash, is_active, last_login_at, created_at, updated_at, role}
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        $payload = self::base64UrlDecode($encodedPayload);
        if ($payload === null) {
            return null;
        }

        $segments = explode('|', $payload, 2);
        if (count($segments) !== 2) {
            return null;
        }

        [$userId, $expiresAt] = $segments;
        if (!ctype_digit($userId) || !ctype_digit($expiresAt) || (int) $expiresAt < time()) {
            return null;
        }

        $user = $this->users->find((int) $userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $user['password_hash']);

        return hash_equals($expectedSignature, $signature) ? $user : null;
    }

    public function resetPassword(array $user, string $password): void
    {
        $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
    }

    private function makeToken(int $userId, string $passwordHash): string
    {
        $payload = $userId . '|' . (time() + self::TTL_SECONDS);
        $signature = hash_hmac('sha256', $payload, $passwordHash);

        return self::base64UrlEncode($payload) . '.' . $signature;
    }

    private function sendEmail(string $email, string $name, string $link): bool
    {
        $safeName = trim($name) !== '' ? $name : 'there';
        $subject  = 'Reset your CRM password';

        $textBody = "Hello {$safeName},\n\nUse the link below to set a new password:\n{$link}\n\n"
            . "This link expires in 1 hour. If you did not request this, please ignore this email.";

        $htmlBody = '<!DOCTYPE html><html><body style="font-family:sans-serif;color:#1a1a1a;max-width:480px;margin:0 auto;padding:32px 16px">'
            . '<p>Hello ' . htmlspecialchars($safeName) . ',</p>'
            . '<p>Use the button below to set a new password:</p>'
            . '<p style="margin:24px 0"><a href="' . htmlspecialchars($link) . '" style="background:#1a56db;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:600">Reset password</a></p>'
            . '<p style="color:#666;font-size:13px">This link expires in 1 hour. If you did not request this, please ignore this email.</p>'
            . '</body></html>';

        return MailerService::send($email, $safeName, $subject, $textBody, $htmlBody);
    }

    private function baseUrl(): string
    {
        static $config = null;
        $config ??= require dirname(__DIR__, 2) . '/config/app.php';

        return rtrim($config['base_url'], '/');
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
