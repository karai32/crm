<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1><?= t('tags.update') ?></h1>
        <span class="count-label"><?= htmlspecialchars($tag['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/tags/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $tag['id'] ?>">
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name"><?= t('tags.name') ?> <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($tag['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label for="color"><?= t('tags.color') ?></label>
                <div class="color-input-row">
                    <input id="color" type="text" name="color"
                           value="<?= htmlspecialchars($tag['color'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="#2563eb" maxlength="7">
                    <input type="color" id="color-picker" class="color-picker-input"
                           value="<?= htmlspecialchars($tag['color'] ?: '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <span class="color-hint"><?= t('tags.color_hint') ?></span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit"><?= t('tags.update') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/tags/delete?id=' . (int) $tag['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('<?= htmlspecialchars(Lang::get('tags.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')"><?= t('common.delete') ?></a>
        </div>
    </div>
</form>
