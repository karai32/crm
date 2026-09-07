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
    <h1><?= t('auth.forgot_password_title') ?></h1>
    <p><?= t('auth.forgot_password_subtitle') ?></p>
</div>

<?php if (!empty($sent)): ?>
    <div class="alert alert-success"><?= t('auth.reset_link_sent') ?></div>
<?php else: ?>
    <form class="auth-form" method="post" action="<?= url('/password/forgot') ?>">
        <?= Csrf::field() ?>
        <div class="field">
            <label for="email"><?= t('auth.email') ?></label>
            <input id="email" type="email" name="email" autocomplete="email" required autofocus>
        </div>

        <button class="btn btn-primary auth-submit" type="submit"><?= t('auth.send_reset_link') ?></button>
    </form>
<?php endif; ?>

<a class="auth-link" href="<?= url('/login') ?>"><?= t('auth.back_to_login') ?></a>
