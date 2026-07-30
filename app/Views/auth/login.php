<div class="auth-brand">
    <div class="auth-logo">
        <i class="ph ph-squares-four"></i>
    </div>
    <div>
        <span class="auth-brand-title">ContactCore</span>
        <span class="auth-brand-subtitle"><?= t('nav.subtitle') ?></span>
    </div>
</div>

<div class="auth-header">
    <h1><?= t('auth.sign_in') ?></h1>
    <p><?= t('auth.sign_in_subtitle') ?></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= url('/login') ?>">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="email"><?= t('auth.email') ?></label>
        <input id="email" type="email" name="email" value="<?= e($email ?? '') ?>" autocomplete="email" required autofocus>
    </div>

    <div class="field">
        <label for="password"><?= t('auth.password') ?></label>
        <div class="password-field">
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <button class="password-toggle" type="button"
                    data-password-toggle="password"
                    data-show-label="<?= e(Lang::get('common.show_password')) ?>"
                    data-hide-label="<?= e(Lang::get('common.hide_password')) ?>"
                    aria-label="<?= t('common.show_password') ?>" aria-pressed="false">
                <i class="ph ph-eye password-toggle-icon password-toggle-icon-show" aria-hidden="true"></i>
                <i class="ph ph-eye-closed password-toggle-icon password-toggle-icon-hide" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="field checkbox-field">
        <label class="toggle-switch">
            <input type="checkbox" name="remember_me" value="1">
            <span class="toggle-track"></span>
            <span class="toggle-label"><?= t('auth.remember_me') ?></span>
        </label>
    </div>

    <button class="btn btn-primary auth-submit" type="submit"><?= t('auth.sign_in_btn') ?></button>
</form>
