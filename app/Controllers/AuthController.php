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
            'title' => Lang::get('auth.login_title'),
            'error' => null,
        ], 'auth');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (LoginThrottle::isLocked($email)) {
            $minutes = (int) ceil(LoginThrottle::secondsRemaining($email) / 60);

            View::render('auth/login', [
                'title' => Lang::get('auth.login_title'),
                'error' => Lang::get('auth.too_many_attempts', ['minutes' => max(1, $minutes)]),
                'email' => $email,
            ], 'auth');
            return;
        }

        $user = $this->authService->attemptLogin($email, $password);

        if ($user === null) {
            LoginThrottle::recordFailure($email);

            View::render('auth/login', [
                'title' => Lang::get('auth.login_title'),
                'error' => Lang::get('auth.invalid_credentials'),
                'email' => $email,
            ], 'auth');
            return;
        }

        LoginThrottle::clear($email);

        if (!$this->twoFactorService->start($user, !empty($_POST['remember_me']))) {
            View::render('auth/login', [
                'title' => Lang::get('auth.login_title'),
                'error' => Lang::get('auth.2fa_send_failed'),
                'email' => $email,
            ], 'auth');
            return;
        }

        Auth::redirect('/login/verify');
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
            'title' => Lang::get('auth.2fa_title'),
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
        $remember = $this->twoFactorService->pendingRemember();
        $user = $this->twoFactorService->verify($code);

        if ($user === null) {
            View::render('auth/two-factor', [
                'title' => Lang::get('auth.2fa_title'),
                'error' => Lang::get('auth.invalid_code'),
                'message' => null,
                'email' => $this->twoFactorService->maskedEmail(),
                'canResend' => $this->twoFactorService->canResend(),
            ], 'auth');
            return;
        }

        $this->authService->completeLogin($user);

        if ($remember) {
            Auth::issueRememberToken((int) $user['id']);
        }

        Auth::redirect('/dashboard');
    }

    public function resendTwoFactor(): void
    {
        if (!$this->twoFactorService->hasPending()) {
            Auth::redirect('/login');
        }

        $sent = $this->twoFactorService->resend();

        View::render('auth/two-factor', [
            'title' => Lang::get('auth.2fa_title'),
            'error' => $sent ? null : Lang::get('auth.please_wait'),
            'message' => $sent ? Lang::get('auth.code_resent') : null,
            'email' => $this->twoFactorService->maskedEmail(),
            'canResend' => $this->twoFactorService->canResend(),
        ], 'auth');
    }

    public function logout(): void
    {
        $this->twoFactorService->clear();
        Auth::revokeRememberToken();
        Auth::logout();
        Auth::redirect('/login');
    }
}
