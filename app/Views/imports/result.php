<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1><?= !empty($result['failed']) ? 'Import failed' : 'Import complete' ?></h1>
        <?php if (!empty($result['message'])): ?>
            <span class="count-label"><?= htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="import-result-grid">
    <div class="import-stat-card">
        <div class="import-stat-value"><?= (int) $result['total_rows'] ?></div>
        <div class="import-stat-label">Total rows</div>
    </div>
    <div class="import-stat-card">
        <div class="import-stat-value import-stat-value--green"><?= (int) $result['imported'] ?></div>
        <div class="import-stat-label">Imported</div>
    </div>
    <div class="import-stat-card">
        <div class="import-stat-value import-stat-value--amber"><?= (int) $result['skipped'] ?></div>
        <div class="import-stat-label">Skipped</div>
    </div>
    <div class="import-stat-card">
        <div class="import-stat-value <?= (int) $result['errors'] > 0 ? 'import-stat-value--red' : '' ?>"><?= (int) $result['errors'] ?></div>
        <div class="import-stat-label">Errors</div>
    </div>
</div>

<!-- Actions -->
<div class="import-result-actions">
    <?php $resultEntity = ($result['entity_type'] ?? 'contacts') === 'clients' ? 'clients' : 'contacts'; ?>
    <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/' . $resultEntity), ENT_QUOTES, 'UTF-8') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
        </svg>
        Go to <?= htmlspecialchars($resultEntity, ENT_QUOTES, 'UTF-8') ?>
    </a>
    <?php if (!empty($result['batch']['id']) && ((int) $result['errors'] > 0 || (int) $result['skipped'] > 0)): ?>
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports/errors?id=' . $result['batch']['id']), ENT_QUOTES, 'UTF-8') ?>">View issues</a>
    <?php endif; ?>
    <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>">Import history</a>
</div>
