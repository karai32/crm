<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1>Upload contacts file</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/imports/upload'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="import-upload-card">
        <div class="import-upload-body">
            <div class="field">
                <label for="csv_file">Select file</label>
                <input id="csv_file" type="file" name="csv_file"
                       accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                       required>
                <span class="import-upload-hint">Supported formats: CSV and XLSX. XLSX requires PhpSpreadsheet.</span>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--color-neutral-200)">
                <span style="font-size:12.5px;color:var(--color-neutral)">Not sure about the format?</span>
                <a href="<?= htmlspecialchars(Auth::url('/exports/template/contacts'), ENT_QUOTES, 'UTF-8') ?>"
                   style="font-size:12.5px;color:var(--color-tertiary);margin-left:6px">
                    Download contacts template
                </a>
            </div>
        </div>
        <div class="import-upload-actions">
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:15px;height:15px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
                Upload &amp; preview
            </button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </div>
</form>
