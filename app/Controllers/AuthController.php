<?php

class AuthController
{
    private AuthService $authService;
    private TwoFactorService $twoFactorService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->twoFactorService = new TwoFactorService();
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            Auth::redirect('/dashboard');
        }

        View::render('auth/login', [
            'title' => 'Login',
            'error' => null,
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->authService->attemptLogin($email, $password);

        if ($user === null) {
            View::render('auth/login', [
                'title' => 'Login',
                'error' => 'Invalid email or password.',
                'email' => $email,
            ], 'auth');
            return;
        }

        // 2FA temporarily disabled — skip email verification
        // if (!$this->twoFactorService->start($user)) {
        //     View::render('auth/login', [
        //         'title' => 'Login',
        //         'error' => 'Password is correct, but the verification email could not be sent. Check mail settings.',
        //         'email' => $email,
        //     ], 'auth');
        //     return;
        // }
        // Auth::redirect('/login/verify');

        $this->authService->completeLogin($user);

        if (!empty($_POST['remember_me'])) {
            $lifetime = 30 * 24 * 3600;
            setcookie('remember_me', '1', [
                'expires'  => time() + $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires'  => time() + $lifetime,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        Auth::redirect('/dashboard');
    }

    public function showTwoFactor(): void
    {
        if (Auth::check()) {
            Auth::redirect('/dashboard');
        }

        if (!$this->twoFactorService->hasPending()) {
            Auth::redirect('/login');
        }

        View::render('auth/two-factor', [
            'title' => 'Two-factor verification',
            'error' => null,
            'message' => null,
            'email' => $this->twoFactorService->maskedEmail(),
            'canResend' => $this->twoFactorService->canResend(),
        ], 'auth');
    }

    public function verifyTwoFactor(): void
    {
        if (!$this->twoFactorService->hasPending()) {
            Auth::redirect('/login');
        }

        $code = trim($_POST['code'] ?? '');
        $user = $this->twoFactorService->verify($code);

        if ($user === null) {
            View::render('auth/two-factor', [
                'title' => 'Two-factor verification',
                'error' => 'Invalid or expired verification code.',
                'message' => null,
                'email' => $this->twoFactorService->maskedEmail(),
                'canResend' => $this->twoFactorService->canResend(),
            ], 'auth');
            return;
        }

        $this->authService->completeLogin($user);
        Auth::redirect('/dashboard');
    }

    public function resendTwoFactor(): void
    {
        if (!$this->twoFactorService->hasPending()) {
            Auth::redirect('/login');
        }

        $sent = $this->twoFactorService->resend();

        View::render('auth/two-factor', [
            'title' => 'Two-factor verification',
            'error' => $sent ? null : 'Please wait before requesting another code.',
            'message' => $sent ? 'A new verification code has been sent.' : null,
            'email' => $this->twoFactorService->maskedEmail(),
            'canResend' => $this->twoFactorService->canResend(),
        ], 'auth');
    }

    public function logout(): void
    {
        $this->twoFactorService->clear();
        Auth::logout();
        setcookie('remember_me', '', time() - 3600, '/');
        Auth::redirect('/login');
    }

}
