<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1><?= t('imports.upload_title') ?></h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/imports/upload'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="import-upload-card">
        <div class="import-upload-body">
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
            <div class="import-template-hint">
                <span class="import-template-hint-text"><?= t('imports.unsure_format') ?></span>
                <a href="<?= htmlspecialchars(Auth::url('/exports/template/contacts'), ENT_QUOTES, 'UTF-8') ?>"
                   id="import-template-link"
                   data-base-href="<?= htmlspecialchars(Auth::url('/exports/template/'), ENT_QUOTES, 'UTF-8') ?>"
                   class="import-template-link">
                    <?= t('imports.download_template') ?>
                </a>
            </div>
        </div>
        <div class="import-upload-actions">
            <button class="btn btn-primary" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
                <?= t('imports.upload_preview_btn') ?>
            </button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
        </div>
    </div>
</form>
