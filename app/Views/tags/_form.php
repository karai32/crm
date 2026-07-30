<?php
$isEdit = $isEdit ?? false;
$tagData = $tag ?? [];
$tagId = (int) ($tagData['id'] ?? 0);
?>
<div class="page-header settings-header">
    <div>
        <h1><?= t($isEdit ? 'tags.update' : 'tags.create_btn') ?></h1>
        <?php if ($isEdit): ?>
            <span class="count-label"><?= htmlspecialchars($tagData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url($isEdit ? '/tags/update' : '/tags/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $tagId ?>">
    <?php endif; ?>
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name"><?= t('tags.name') ?> <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($tagData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       <?= $isEdit ? '' : 'placeholder="' . htmlspecialchars(Lang::get('tags.name_placeholder'), ENT_QUOTES, 'UTF-8') . '" autofocus' ?> required>
            </div>
            <div class="field">
                <label for="color"><?= t('tags.color') ?></label>
                <div class="color-input-row">
                    <input id="color" type="text" name="color"
                           value="<?= htmlspecialchars($tagData['color'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="#2563eb" maxlength="7">
                    <input type="color" id="color-picker" class="color-picker-input"
                           value="<?= htmlspecialchars(($tagData['color'] ?? '') ?: '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <span class="color-hint"><?= t('tags.color_hint') ?></span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit"><?= t($isEdit ? 'tags.update' : 'tags.save') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <?php if ($isEdit): ?>
                <button class="btn btn-danger btn-sm" type="submit"
                        formaction="<?= htmlspecialchars(Auth::url('/tags/delete'), ENT_QUOTES, 'UTF-8') ?>"
                        formnovalidate
                        data-confirm="<?= htmlspecialchars(Lang::get('tags.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>"
                        onclick="return confirm(this.dataset.confirm)"><?= t('common.delete') ?></button>
            <?php endif; ?>
        </div>
    </div>
</form>
