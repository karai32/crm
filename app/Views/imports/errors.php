<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1>Import issues</h1>
        <span class="count-label"><?= htmlspecialchars($batch['original_filename'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>">Back to imports</a>
    </div>
</div>

<div class="imports-table-card">
    <?php if (empty($errors)): ?>
        <p class="table-empty-state">No issues found for this import.</p>
    <?php else: ?>
    <table class="import-errors-table">
        <thead>
            <tr>
                <th>Row</th>
                <th>Error message</th>
                <th>Raw data</th>
                <th>Time</th>
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
