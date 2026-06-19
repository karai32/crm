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
?>

<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1>Imports</h1>
        <span class="count-label"><?= count($batches) ?> batches</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/imports/upload'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
            </svg>
            Upload CSV / XLSX
        </a>
    </div>
</div>

<div class="imports-table-card">
    <?php if (empty($batches)): ?>
        <p class="table-empty-state">No imports yet.</p>
    <?php else: ?>
    <table class="data-table imports-table">
        <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Type</th>
                <th>Status</th>
                <th class="col-num-header">Total</th>
                <th class="col-num-header">Imported</th>
                <th class="col-num-header">Skipped</th>
                <th class="col-num-header">Errors</th>
                <th>Created</th>
                <th class="col-actions">Actions</th>
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
                    <td><?= htmlspecialchars(ucfirst($batch['entity_type'] ?? 'contacts'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="import-status <?= importStatusClass($displayStatus) ?>"><?= htmlspecialchars(ucfirst($displayStatus), ENT_QUOTES, 'UTF-8') ?></span></td>
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
                                <a class="action-btn action-view" title="View issues" href="<?= htmlspecialchars(Auth::url('/imports/errors?id=' . $batch['id']), ENT_QUOTES, 'UTF-8') ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
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
