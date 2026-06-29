<?php
// Group fields by their 'group' key
$groupedFields = [];
foreach ($fieldDefs as $key => $def) {
    $groupedFields[$def['group']][$key] = $def;
}

// Entity label
$entityLabel      = $entity === 'contacts' ? 'Contacts' : 'Clients';
$otherEntity      = $entity === 'contacts' ? 'clients' : 'contacts';
$otherEntityLabel = $entity === 'contacts' ? 'Clients' : 'Contacts';

$xlsxAvailable = class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);

?>

<!-- Header -->
<div class="page-header exports-header">
    <div>
        <h1>Export data</h1>
        <span class="count-label">Download contacts and clients as CSV or XLSX</span>
    </div>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] === 'phpspreadsheet'): ?>
<div class="export-notice">
    <i class="ph ph-warning"></i>
    PhpSpreadsheet is not installed. XLSX export is unavailable. Run: <code>composer require phpoffice/phpspreadsheet</code>
</div>
<?php endif; ?>

<!-- Entity tabs -->
<div class="data-tabs">
    <a href="<?= htmlspecialchars(Auth::url('/exports?entity=contacts'), ENT_QUOTES, 'UTF-8') ?>"
       class="data-tab <?= $entity === 'contacts' ? 'active' : '' ?>">
        <i class="ph ph-user"></i>
        Contacts
    </a>
    <a href="<?= htmlspecialchars(Auth::url('/exports?entity=clients'), ENT_QUOTES, 'UTF-8') ?>"
       class="data-tab <?= $entity === 'clients' ? 'active' : '' ?>">
        <i class="ph ph-buildings"></i>
        Clients
    </a>
</div>

<!-- Main form -->
<form method="post" action="<?= htmlspecialchars(Auth::url('/exports/download'), ENT_QUOTES, 'UTF-8') ?>" id="exportForm">
    <?= Csrf::field() ?>
    <input type="hidden" name="entity" value="<?= htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="format" value="csv" id="formatInput">

    <div class="export-layout">

        <!-- Left: field selection -->
        <div class="export-fields-card">
            <?php foreach ($groupedFields as $groupName => $groupFields): ?>
            <div class="export-section">
                <div class="export-section-title">
                    <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>
                    <div class="export-section-title-actions">
                        <button type="button" class="btn btn-outlined btn-xs" onclick="toggleGroup(this, true)">All</button>
                        <button type="button" class="btn btn-outlined btn-xs" onclick="toggleGroup(this, false)">None</button>
                    </div>
                </div>
                <div class="export-fields-grid">
                    <?php foreach ($groupFields as $key => $def): ?>
                    <label class="export-field-label">
                        <input type="checkbox"
                               name="fields[]"
                               value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                               <?= in_array($key, $defaultFields, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($def['label'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: options + download -->
        <div class="export-sidebar">

            <!-- Format selection -->
            <div class="export-options-card">
                <div class="export-options-body">
                    <label>Format</label>
                    <div class="format-options">
                        <label class="format-option selected" id="formatCsv" onclick="selectFormat('csv', this)">
                            <input type="radio" name="_format_display" value="csv" checked>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            CSV
                        </label>
                        <label class="format-option <?= !$xlsxAvailable ? 'format-option--disabled' : '' ?>" id="formatXlsx" onclick="<?= $xlsxAvailable ? "selectFormat('xlsx', this)" : '' ?>">
                            <input type="radio" name="_format_display" value="xlsx" <?= !$xlsxAvailable ? 'disabled' : '' ?>>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 19.5m9.375-14.625c0-.621-.504-1.125-1.125-1.125H3.75A1.125 1.125 0 0 0 2.625 4.875v14.25A1.125 1.125 0 0 0 3.75 20.25h16.5a1.125 1.125 0 0 0 1.125-1.125V4.875Z"/>
                            </svg>
                            XLSX
                            <?php if (!$xlsxAvailable): ?>
                            <span class="format-option-na">N/A</span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
                <div class="export-options-footer">
                    <button type="submit" class="btn btn-primary btn-download" id="downloadBtn">
                        <i class="ph ph-download-simple"></i>
                        Download <?= htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <div class="export-summary-card">
                <div class="export-summary-title">Selection</div>
                <div class="export-summary-row">
                    <span>Entity</span>
                    <span><?= htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="export-summary-row">
                    <span>Fields selected</span>
                    <span id="selectedCount"><?= count($defaultFields) ?></span>
                </div>
                <div class="export-summary-row">
                    <span>Format</span>
                    <span id="selectedFormat">CSV</span>
                </div>
            </div>

            <!-- Import templates -->
            <div class="export-templates-card">
                <div class="export-templates-title">Import templates</div>
                <a class="export-template-link"
                   href="<?= htmlspecialchars(Auth::url('/exports/template/contacts'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="ph ph-download-simple"></i>
                    Contacts CSV template
                </a>
                <a class="export-template-link"
                   href="<?= htmlspecialchars(Auth::url('/exports/template/clients'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="ph ph-download-simple"></i>
                    Clients CSV template
                </a>
            </div>

        </div>

    </div>
</form>

<!-- Export history -->
<div class="export-history-section">
    <div class="export-history-header">Export history</div>

    <div class="export-history-card">
        <?php if (empty($recentExports)): ?>
            <p class="export-no-history">No exports yet.</p>
        <?php // note: $recentExports variable holds paginated results ?>
        <?php else: ?>
        <table class="data-table export-history-table">
            <thead>
                <tr>
                    <?= thSort('id', '#', $sort, $dir, '/exports', ['entity' => $entity]) ?>
                    <?= thSort('entity_type', 'Entity', $sort, $dir, '/exports', ['entity' => $entity]) ?>
                    <?= thSort('stored_filename', 'File', $sort, $dir, '/exports', ['entity' => $entity]) ?>
                    <th>Format</th>
                    <th class="col-num-header">Rows</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentExports as $export): ?>
                <?php
                    $filename  = $export['stored_filename'] ?? '';
                    $entityKey = ($export['entity_type'] ?? 'contacts') === 'clients' ? 'clients' : 'contacts';
                    $entityBadgeClass = 'export-entity-' . $entityKey;
                    $entityBadgeLabel = ucfirst($entityKey);
                ?>
                <tr>
                    <td class="col-export-id"><?= (int) $export['id'] ?></td>
                    <td>
                        <span class="export-entity-badge <?= $entityBadgeClass ?>">
                            <?= $entityBadgeLabel ?>
                        </span>
                    </td>
                    <td class="col-export-file">
                        <?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <span class="export-format-badge">
                            <?= htmlspecialchars(strtoupper($export['file_type'] ?? 'CSV'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="col-num"><?= number_format((int) ($export['total_rows'] ?? 0)) ?></td>
                    <td class="col-export-muted">
                        <?= htmlspecialchars($export['user_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="col-export-date">
                        <?= htmlspecialchars(substr($export['finished_at'] ?? $export['created_at'] ?? '', 0, 16), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php renderPagination($page, $totalPages, $total, $perPage, '/exports', ['entity' => $entity, 'sort' => $sort, 'dir' => $dir]); ?>
