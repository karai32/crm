<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1><?= t('imports.errors_title') ?></h1>
        <span class="count-label"><?= htmlspecialchars($batch['original_filename'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"><?= t('imports.back_to_imports') ?></a>
    </div>
</div>

<div class="imports-table-card">
    <?php if (empty($errors)): ?>
        <p class="table-empty-state"><?= t('imports.no_issues') ?></p>
    <?php else: ?>
    <table class="import-errors-table">
        <thead>
            <tr>
                <th><?= t('imports.col_row') ?></th>
                <th><?= t('imports.col_error_message') ?></th>
                <th><?= t('imports.col_raw_data') ?></th>
                <th><?= t('common.time') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($errors as $error): ?>
                <tr>
                    <td class="col-row"><?= (int) ($error['row_number'] ?? 0) ?></td>
                    <td class="col-error"><?= htmlspecialchars($error['error_message'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-raw"><?= htmlspecialchars($error['raw_data'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-time"><?= htmlspecialchars($error['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
