<div class="auth-brand">
    <div class="auth-logo">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
        </svg>
    </div>
    <div>
        <span class="auth-brand-title">ContactCore</span>
        <span class="auth-brand-subtitle"><?= t('nav.subtitle') ?></span>
    </div>
</div>

<div class="auth-header">
    <h1><?= t('auth.2fa_title') ?></h1>
    <p><?= t('auth.2fa_subtitle') ?></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= url('/login/verify') ?>">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="code"><?= t('auth.2fa_code') ?></label>
        <input class="auth-code-input" id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
    </div>

    <button class="btn btn-primary auth-submit" type="submit"><?= t('auth.2fa_verify') ?></button>
</form>

<form class="auth-secondary-form" method="post" action="<?= url('/login/resend-code') ?>">
    <?= Csrf::field() ?>
    <button class="btn btn-outlined auth-submit" type="submit" <?= empty($canResend) ? 'disabled' : '' ?>><?= t('auth.2fa_resend') ?></button>
</form>
