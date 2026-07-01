<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('tags.create_btn') ?></h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/tags/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name"><?= t('tags.name') ?> <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="e.g. VIP" required autofocus>
            </div>
            <div class="field">
                <label for="color"><?= t('tags.color') ?></label>
                <div class="color-input-row">
                    <input id="color" type="text" name="color"
                           value="<?= htmlspecialchars($color ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="#2563eb" maxlength="7">
                    <input type="color" id="color-picker" class="color-picker-input"
                           value="<?= htmlspecialchars($color ?: '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <span class="color-hint">Hex format: #rrggbb</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit"><?= t('tags.save') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
        </div>
    </div>
</form>
