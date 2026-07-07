<!-- Header -->
<div class="page-header imports-header">
    <div>
        <h1><?= t('imports.preview_title') ?></h1>
        <span class="count-label">
            <?= htmlspecialchars($preview['batch']['original_filename'], ENT_QUOTES, 'UTF-8') ?>
            &nbsp;-&nbsp; <?= ($preview['batch']['entity_type'] ?? 'contacts') === 'clients' ? t('clients.title') : t('contacts.title') ?>
            &nbsp;-&nbsp; <?= htmlspecialchars(Lang::get('imports.rows_found', ['n' => (int) $preview['total_rows']]), ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (empty($preview['headers'])): ?>
    <div class="alert alert-error"><?= t('imports.no_headers') ?></div>
<?php else: ?>

<!-- Data preview -->
<div class="import-preview-card">
    <div class="import-card-header">
        <span class="import-card-header-title"><?= t('imports.first_rows_preview') ?></span>
        <span class="import-card-header-meta"><?= t('imports.showing_up_to_10') ?></span>
    </div>
    <div class="import-data-scroll">
        <table class="import-data-table">
            <thead>
                <tr>
                    <?php foreach ($preview['headers'] as $header): ?>
                        <th><?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($preview['headers'] as $header): ?>
                            <td><?= htmlspecialchars($row[$header] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Column mapping -->
<form method="post" action="<?= htmlspecialchars(Auth::url('/imports/process'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $preview['batch']['id'] ?>">

    <div class="import-preview-card">
        <div class="import-card-header">
            <span class="import-card-header-title"><?= t('imports.column_mapping') ?></span>
            <span class="import-card-header-meta"><?= t('imports.map_hint') ?></span>
        </div>

        <table class="import-mapping-table">
            <thead>
                <tr>
                    <th><?= t('imports.csv_column') ?></th>
                    <th><?= t('imports.map_to_field') ?></th>
                    <th><?= t('imports.custom_field_settings') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview['headers'] as $header): ?>
                    <tr>
                        <td class="col-csv"><?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <select name="mapping[<?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?>]"
                                    data-custom-mapping-select
                                    data-custom-target="custom-settings-<?= md5($header) ?>">
                                <option value=""><?= t('imports.do_not_import') ?></option>
                                <?php foreach ($preview['system_fields'] as $fieldKey => $label): ?>
                                    <option value="<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= (($preview['suggested_mapping'][$header] ?? '') === $fieldKey) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom"><?= t('imports.create_custom_field') ?></option>
                            </select>
                        </td>
                        <td id="custom-settings-<?= md5($header) ?>" class="custom-field-settings">
                            <select name="custom_fields[<?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?>][field_type]">
                                <?php foreach (['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'] as $type): ?>
                                    <option value="<?= $type ?>"><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mapping-actions">
            <button class="btn btn-green" type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                <?= t('imports.confirm_import') ?>
            </button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
        </div>
    </div>
</form>

<?php endif; ?>
