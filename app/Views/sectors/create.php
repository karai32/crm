<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Create sector</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/sectors/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name">Name <span style="color:var(--color-danger)">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="e.g. Technology" required autofocus>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Create sector</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </div>
</form>
