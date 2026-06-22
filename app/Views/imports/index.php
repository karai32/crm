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

function importSortUrl(string $col, string $sort, string $dir): string {
    $nd = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return Auth::url('/imports?' . http_build_query(['sort' => $col, 'dir' => $nd]));
}

function importThSort(string $col, string $label, string $sort, string $dir): string {
    $active = $sort === $col;
    $icon   = $active ? ($dir === 'asc' ? '↑' : '↓') : '↕';
    $cls    = 'th-sort' . ($active ? ' th-sort--' . $dir : '');
    $href   = htmlspecialchars(importSortUrl($col, $sort, $dir), ENT_QUOTES, 'UTF-8');
    return '<th class="' . $cls . '"><a href="' . $href . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' <span class="sort-icon" aria-hidden="true">' . $icon . '</span></a></th>';
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
            <i class="ph ph-upload-simple"></i>
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
                <?= importThSort('id', '#', $sort, $dir) ?>
                <?= importThSort('original_filename', 'File', $sort, $dir) ?>
                <?= importThSort('entity_type', 'Type', $sort, $dir) ?>
                <?= importThSort('status', 'Status', $sort, $dir) ?>
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
                                    <i class="ph ph-eye"></i>
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
