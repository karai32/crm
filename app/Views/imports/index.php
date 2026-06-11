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
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
            </svg>
            Upload CSV / XLSX
        </a>
    </div>
</div>

<div class="imports-table-card">
    <?php if (empty($batches)): ?>
        <p style="padding:24px 16px;color:var(--color-text-muted);font-size:14px;">No imports yet.</p>
    <?php else: ?>
    <table class="imports-table">
        <thead>
            <tr>
                <th>#</th>
                <th>File</th>
                <th>Status</th>
                <th style="text-align:right">Total</th>
                <th style="text-align:right">Imported</th>
                <th style="text-align:right">Skipped</th>
                <th style="text-align:right">Errors</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
                <?php $displayStatus = importDisplayStatus($batch); ?>
                <tr>
                    <td style="color:var(--color-neutral);font-size:12.5px"><?= (int) $batch['id'] ?></td>
                    <td class="col-file">
                        <div style="display:flex;align-items:center;gap:8px">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="width:15px;height:15px;color:var(--color-neutral);flex-shrink:0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            <?= htmlspecialchars($batch['original_filename'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </td>
                    <td><span class="import-status <?= importStatusClass($displayStatus) ?>"><?= htmlspecialchars(ucfirst($displayStatus), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="col-num"><?= (int) $batch['total_rows'] ?></td>
                    <td class="col-num" style="color:var(--color-secondary)"><?= (int) $batch['imported_rows'] ?></td>
                    <td class="col-num"><?= (int) $batch['skipped_rows'] ?></td>
                    <td class="col-num" style="<?= (int) $batch['error_rows'] > 0 ? 'color:var(--color-danger)' : '' ?>">
                        <?= (int) $batch['error_rows'] ?>
                    </td>
                    <td class="col-date"><?= htmlspecialchars($batch['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ((int) $batch['error_rows'] > 0): ?>
                            <a class="action-view" href="<?= htmlspecialchars(Auth::url('/imports/errors?id=' . $batch['id']), ENT_QUOTES, 'UTF-8') ?>">View errors</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
