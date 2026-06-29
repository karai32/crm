<div class="auth-brand">
    <div class="auth-logo">
        <i class="ph ph-squares-four"></i>
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
                <i class="ph ph-eye password-toggle-icon password-toggle-icon-show" aria-hidden="true"></i>
                <i class="ph ph-eye-closed password-toggle-icon password-toggle-icon-hide" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="field checkbox-field">
        <label class="toggle-switch">
            <input type="checkbox" name="remember_me" value="1">
            <span class="toggle-track"></span>
            <span class="toggle-label">Remember me</span>
        </label>
    </div>

    <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
</form>
