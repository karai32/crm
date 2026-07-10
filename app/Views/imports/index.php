<?php
function importStatusClass(string $status): string
{
    return match ($status) {
        'completed'  => 'import-status-completed',
        'partial'    => 'import-status-partial',
        'processing' => 'import-status-processing',
        'uploaded', 'previewed', 'pending' => 'import-status-pending',
        'failed'     => 'import-status-failed',
        default      => 'import-status-failed',
    };
}

function importDisplayStatus(array $batch): string
{
    $status = trim((string) ($batch['status'] ?? ''));

    if ($status === '') {
        if ((int) ($batch['error_rows'] ?? 0) > 0 || (int) ($batch['skipped_rows'] ?? 0) > 0) {
            return 'partial';
        }

        return 'failed';
    }

    return $status;
}

$templatesBase = Auth::url('/assets/templates/');
?>

<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1><?= t('imports.title') ?></h1>
        <span class="count-label"><?= htmlspecialchars(Lang::get('imports.n_batches', ['n' => (int) $total]), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="export-layout">

    <!-- Left: preview + mapping (after upload) or placeholder -->
    <div class="imports-main">
        <?php if (!empty($preview)): ?>
            <?php require __DIR__ . '/_preview.php'; ?>
        <?php else: ?>
            <div class="import-placeholder-card">
                <i class="ph ph-upload-simple"></i>
                <div class="import-placeholder-title"><?= t('imports.placeholder_title') ?></div>
                <div class="import-placeholder-text"><?= t('imports.placeholder_text') ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right: upload + templates -->
    <div class="export-sidebar">

        <!-- Upload -->
        <form method="post" action="<?= htmlspecialchars(Auth::url('/imports/upload'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="export-options-card">
                <div class="export-options-body">
                    <div class="import-upload-fields">
                        <div class="field">
                            <label for="entity_type"><?= t('imports.data_type') ?></label>
                            <select id="entity_type" name="entity_type">
                                <option value="contacts"><?= t('contacts.title') ?></option>
                                <option value="clients"><?= t('clients.title') ?></option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="csv_file"><?= t('imports.select_file') ?></label>
                            <input id="csv_file" type="file" name="csv_file"
                                   accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   required>
                            <span class="import-upload-hint"><?= t('imports.format_hint') ?></span>
                        </div>
                    </div>
                    <div class="import-template-hint">
                        <span class="import-template-hint-text"><?= t('imports.unsure_format') ?></span>
                        <span class="import-template-hint-text"><?= t('imports.download_template') ?>:</span>
                        <a href="<?= htmlspecialchars($templatesBase . 'contacts-import-template.csv', ENT_QUOTES, 'UTF-8') ?>"
                           class="import-template-link" data-ext="csv"
                           data-base-href="<?= htmlspecialchars($templatesBase, ENT_QUOTES, 'UTF-8') ?>"
                           download>csv</a>
                        <a href="<?= htmlspecialchars($templatesBase . 'contacts-import-template.xlsx', ENT_QUOTES, 'UTF-8') ?>"
                           class="import-template-link" data-ext="xlsx"
                           data-base-href="<?= htmlspecialchars($templatesBase, ENT_QUOTES, 'UTF-8') ?>"
                           download>xlsx</a>
                    </div>
                </div>
                <div class="export-options-footer">
                    <button class="btn btn-primary btn-download" type="submit">
                        <i class="ph ph-upload-simple"></i>
                        <?= t('imports.upload_preview_btn') ?>
                    </button>
                </div>
            </div>
        </form>

        <!-- Templates -->
        <div class="export-templates-card">
            <div class="export-templates-title"><?= t('imports.templates') ?></div>
            <?php foreach (['contacts' => t('contacts.title'), 'clients' => t('clients.title')] as $tplEntity => $tplLabel): ?>
                <?php foreach (['csv', 'xlsx'] as $tplExt): ?>
                    <a class="export-template-link"
                       href="<?= htmlspecialchars($templatesBase . $tplEntity . '-import-template.' . $tplExt, ENT_QUOTES, 'UTF-8') ?>" download>
                        <i class="ph ph-download-simple"></i>
                        <?= htmlspecialchars($tplLabel, ENT_QUOTES, 'UTF-8') ?> &mdash; <?= strtoupper($tplExt) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<!-- Import history -->
<div class="export-history-section">
    <div class="export-history-header"><?= t('imports.history') ?></div>

    <div class="imports-table-card">
        <?php if (empty($batches)): ?>
            <p class="table-empty-state"><?= t('imports.no_found') ?></p>
        <?php else: ?>
        <table class="data-table imports-table">
            <thead>
                <tr>
                    <?= thSort('id', '#', $sort, $dir, '/imports') ?>
                    <?= thSort('original_filename', t('common.file'), $sort, $dir, '/imports') ?>
                    <?= thSort('entity_type', t('imports.type'), $sort, $dir, '/imports') ?>
                    <?= thSort('status', t('common.status'), $sort, $dir, '/imports') ?>
                    <th class="col-num-header"><?= t('imports.total') ?></th>
                    <th class="col-num-header"><?= t('imports.imported') ?></th>
                    <th class="col-num-header"><?= t('imports.skipped') ?></th>
                    <th class="col-num-header"><?= t('imports.errors') ?></th>
                    <th><?= t('common.created') ?></th>
                    <th class="col-actions"><?= t('common.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                    <?php $displayStatus = importDisplayStatus($batch); ?>
                    <tr>
                        <td class="col-id-muted"><?= (int) $batch['id'] ?></td>
                        <td class="col-file">
                            <div class="import-file-cell">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="import-file-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                                <?= htmlspecialchars($batch['original_filename'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td><?= ($batch['entity_type'] ?? 'contacts') === 'clients' ? t('clients.title') : t('contacts.title') ?></td>
                        <td><span class="import-status <?= importStatusClass($displayStatus) ?>"><?= t('imports.status_' . $displayStatus) ?></span></td>
                        <td class="col-num"><?= (int) $batch['total_rows'] ?></td>
                        <td class="col-num col-imported"><?= (int) $batch['imported_rows'] ?></td>
                        <td class="col-num"><?= (int) $batch['skipped_rows'] ?></td>
                        <td class="col-num <?= (int) $batch['error_rows'] > 0 ? 'col-errors-danger' : '' ?>">
                            <?= (int) $batch['error_rows'] ?>
                        </td>
                        <td class="col-date"><?= htmlspecialchars($batch['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-actions">
                            <?php if ((int) $batch['error_rows'] > 0 || (int) $batch['skipped_rows'] > 0): ?>
                                <div class="action-links">
                                    <a class="action-btn action-view" href="<?= htmlspecialchars(Auth::url('/imports/errors?id=' . $batch['id']), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="ph ph-eye"></i>
                                        <span class="tooltip-text"><?= t('imports.view_issues') ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/imports', ['sort' => $sort, 'dir' => $dir]); ?>
