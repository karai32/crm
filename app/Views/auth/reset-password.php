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
    <h1><?= t('auth.reset_password_title') ?></h1>
    <p><?= t('auth.reset_password_subtitle') ?></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($token)): ?>
    <form class="auth-form" method="post" action="<?= url('/password/reset') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="field">
            <label for="password"><?= t('auth.new_password') ?></label>
            <div class="password-field">
                <input id="password" type="password" name="password" autocomplete="new-password" required autofocus>
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

        <div class="field">
            <label for="password_confirmation"><?= t('auth.confirm_password') ?></label>
            <div class="password-field">
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                <button class="password-toggle" type="button"
                        data-password-toggle="password_confirmation"
                        data-show-label="<?= e(Lang::get('common.show_password')) ?>"
                        data-hide-label="<?= e(Lang::get('common.hide_password')) ?>"
                        aria-label="<?= t('common.show_password') ?>" aria-pressed="false">
                    <i class="ph ph-eye password-toggle-icon password-toggle-icon-show" aria-hidden="true"></i>
                    <i class="ph ph-eye-closed password-toggle-icon password-toggle-icon-hide" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button class="btn btn-primary auth-submit" type="submit"><?= t('auth.reset_password_btn') ?></button>
    </form>
<?php else: ?>
    <a class="auth-link" href="<?= url('/password/forgot') ?>"><?= t('auth.forgot_password') ?></a>
<?php endif; ?>
