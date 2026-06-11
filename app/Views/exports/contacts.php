<?php
$activeFilters = array_filter($filters, function ($value) {
    return is_array($value) ? !empty($value) : ($value !== '' && $value !== 0 && $value !== null);
});

$backUrl = Auth::url('/contacts' . (!empty($activeFilters) ? '?' . http_build_query($activeFilters) : ''));
$isCsv   = str_contains($actionUrl, 'export-csv');
$icon    = $isCsv
    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>'
    : '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>';
?>

<!-- Header -->
<div class="page-header contacts-header">
    <div>
        <h1><?= htmlspecialchars($exportTitle ?? 'Export contacts', ENT_QUOTES, 'UTF-8') ?></h1>
        <span class="count-label">Select the fields to include in the file</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Back to contacts</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>

    <?php foreach ($filters as $name => $value): ?>
        <?php if (is_array($value)): ?>
            <?php foreach ($value as $childName => $childValue): ?>
                <input type="hidden"
                       name="<?= htmlspecialchars($name . '[' . $childName . ']', ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars((string) $childValue, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <input type="hidden"
                   name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                   value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="export-card">

        <!-- Fields section -->
        <div class="form-section">
            <div class="form-section-title">Fields to export</div>
            <div class="export-fields-grid">
                <?php foreach ($fields as $field => $label): ?>
                    <label>
                        <input type="checkbox" name="fields[]"
                               value="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>"
                               <?= in_array($field, $selectedFields, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Active filters notice -->
        <?php if (!empty($activeFilters)): ?>
        <div class="export-filters-info">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
            </svg>
            <span>The export will only include contacts matching your active filters.</span>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="form-actions">
            <button class="btn btn-green" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="width:16px;height:16px">
                    <?= $icon ?>
                </svg>
                <?= htmlspecialchars($buttonText ?? 'Download', ENT_QUOTES, 'UTF-8') ?>
            </button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>

    </div>
</form>
