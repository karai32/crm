<?php
$isEdit = $isEdit ?? false;
$sectorData = $sector ?? [];
$sectorId = (int) ($sectorData['id'] ?? 0);
$selectedIcon = ($sectorData['icon'] ?? null) ?: ($defaultIcon ?? 'crosshair');
?>
<div class="page-header settings-header">
    <div>
        <h1><?= t($isEdit ? 'sectors.edit_title' : 'sectors.create_title') ?></h1>
        <?php if ($isEdit): ?>
            <span class="count-label"><?= htmlspecialchars($sectorData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url($isEdit ? '/sectors/update' : '/sectors/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $sectorId ?>">
    <?php endif; ?>
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name"><?= t('sectors.name') ?> <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($sectorData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       <?= $isEdit ? '' : 'placeholder="' . htmlspecialchars(Lang::get('sectors.name_placeholder'), ENT_QUOTES, 'UTF-8') . '" autofocus' ?> required>
            </div>
            <?php if ($isEdit): ?>
                <div class="field">
                    <label for="sector-icon-search"><?= t('sectors.icon') ?></label>
                    <div class="sector-icon-picker"
                         data-sector-icon-picker
                         data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/icons/search'), ENT_QUOTES, 'UTF-8') ?>"
                         data-empty-icon="<?= htmlspecialchars($defaultIcon ?? 'crosshair', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="icon" data-sector-icon-value value="<?= htmlspecialchars($sectorData['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="sector-icon-current">
                            <span class="sector-icon-preview" data-sector-icon-preview><i class="ph ph-<?= htmlspecialchars($selectedIcon, ENT_QUOTES, 'UTF-8') ?>"></i></span>
                            <div class="sector-icon-current-body">
                                <span class="sector-icon-current-label" data-sector-icon-label>
                                    <?= !empty($sectorData['icon']) ? htmlspecialchars($sectorData['icon'], ENT_QUOTES, 'UTF-8') : t('sectors.default_icon') ?>
                                </span>
                                <button class="sector-icon-clear" type="button" data-sector-icon-clear><?= t('sectors.use_default') ?></button>
                            </div>
                        </div>
                        <div class="sector-icon-search">
                            <i class="ph ph-magnifying-glass"></i>
                            <input id="sector-icon-search" type="search" data-sector-icon-search
                                   placeholder="<?= t('sectors.icon_search_placeholder') ?>" autocomplete="off" spellcheck="false">
                        </div>
                        <div class="sector-icon-results" data-sector-icon-results>
                            <?php foreach ($recommendedIcons ?? [] as $icon): ?>
                                <button class="sector-icon-option <?= ($sectorData['icon'] ?? '') === $icon['name'] ? 'is-selected' : '' ?>"
                                        type="button" data-icon-name="<?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="ph ph-<?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <span><?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <span class="field-hint"><?= t('sectors.icon_search_hint') ?></span>
                </div>
                <div class="field checkbox-field">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?= ((int) ($sectorData['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                        <?= t('common.active') ?>
                    </label>
                </div>
            <?php endif; ?>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit"><?= t($isEdit ? 'sectors.update' : 'sectors.save') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <?php if ($isEdit): ?>
                <button class="btn btn-danger btn-sm" type="submit"
                        formaction="<?= htmlspecialchars(Auth::url('/sectors/delete'), ENT_QUOTES, 'UTF-8') ?>"
                        formnovalidate data-confirm="<?= htmlspecialchars(Lang::get('sectors.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>"
                        onclick="return confirm(this.dataset.confirm)"><?= t('common.delete') ?></button>
            <?php endif; ?>
        </div>
    </div>
</form>
