<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Edit sector</h1>
        <span class="count-label"><?= htmlspecialchars($sector['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php
$selectedIcon = $sector['icon'] ?: ($defaultIcon ?? 'crosshair');
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/sectors/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $sector['id'] ?>">
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name">Name <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($sector['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label for="sector-icon-search">Icon</label>
                <div class="sector-icon-picker"
                     data-sector-icon-picker
                     data-search-endpoint="<?= htmlspecialchars(Auth::url('/ajax/icons/search'), ENT_QUOTES, 'UTF-8') ?>"
                     data-empty-icon="<?= htmlspecialchars($defaultIcon ?? 'crosshair', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden"
                           name="icon"
                           data-sector-icon-value
                           value="<?= htmlspecialchars($sector['icon'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div class="sector-icon-current">
                        <span class="sector-icon-preview" data-sector-icon-preview>
                            <i class="ph ph-<?= htmlspecialchars($selectedIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                        </span>
                        <div class="sector-icon-current-body">
                            <span class="sector-icon-current-label" data-sector-icon-label>
                                <?= htmlspecialchars($sector['icon'] ?: 'Default icon', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <button class="sector-icon-clear" type="button" data-sector-icon-clear>Use default</button>
                        </div>
                    </div>

                    <div class="sector-icon-search">
                        <i class="ph ph-magnifying-glass"></i>
                        <input id="sector-icon-search"
                               type="search"
                               data-sector-icon-search
                               placeholder="Search icons by name or tag..."
                               autocomplete="off"
                               spellcheck="false">
                    </div>

                    <div class="sector-icon-results" data-sector-icon-results>
                        <?php foreach ($recommendedIcons ?? [] as $icon): ?>
                            <button class="sector-icon-option <?= ($sector['icon'] ?? '') === $icon['name'] ? 'is-selected' : '' ?>"
                                    type="button"
                                    data-icon-name="<?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="ph ph-<?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                <span><?= htmlspecialchars($icon['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <span class="field-hint">Search uses Phosphor icon names, categories and tags.</span>
            </div>
            <div class="field checkbox-field">
                <label>
                    <input type="checkbox" name="is_active" value="1"
                           <?= ((int) ($sector['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                    Active
                </label>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Update sector</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/sectors/delete?id=' . (int) $sector['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('Delete this sector? If it is used by clients, it will be deactivated.')">Delete</a>
        </div>
    </div>
</form>
