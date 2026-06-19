<div class="auth-brand">
    <div class="auth-logo">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
        </svg>
    </div>
    <div>
        <span class="auth-brand-title">ContactCore</span>
        <span class="auth-brand-subtitle">Client relationship CRM</span>
    </div>
</div>

<div class="auth-header">
    <h1>Sign in</h1>
    <p>Use your CRM account to continue.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= htmlspecialchars(Auth::url('/login'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required autofocus>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="password-field">
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                <svg class="password-toggle-icon password-toggle-icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.06 12.35a1 1 0 0 1 0-.7C3.43 8.26 6.86 5 12 5s8.57 3.26 9.94 6.65a1 1 0 0 1 0 .7C20.57 15.74 17.14 19 12 19s-8.57-3.26-9.94-6.65Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="password-toggle-icon password-toggle-icon-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.06 12.35a1 1 0 0 1 0-.7C3.43 8.26 6.86 5 12 5s8.57 3.26 9.94 6.65a1 1 0 0 1 0 .7C20.57 15.74 17.14 19 12 19s-8.57-3.26-9.94-6.65Z"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="field checkbox-field">
        <label>
            <input type="checkbox" name="remember_me" value="1">
            Remember me
        </label>
    </div>

    <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
</form>

<script>
(function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('data-password-toggle'));

        if (!input) return;

        button.addEventListener('click', function () {
            var isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
    });
}());
</script>
