<?php
$isEdit = $isEdit ?? false;
$clientId = (int) ($client['id'] ?? 0);
$cancelPath = $isEdit ? '/clients/show?id=' . $clientId : '/clients';
$formAction = $isEdit ? '/clients/update' : '/clients/store';
?>
<div class="page-header clients-header">
    <div>
        <h1><?= t($isEdit ? 'clients.edit_title' : 'clients.create_title') ?></h1>
        <span class="count-label">
            <?= $isEdit
                ? htmlspecialchars($client['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8')
                : t('clients.fill_fields') ?>
        </span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url($cancelPath), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url($formAction), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $clientId ?>">
    <?php endif; ?>

    <div class="client-form-card">
        <div class="form-section">
            <div class="form-section-title"><?= t('common.basic_info') ?></div>
            <div class="form-grid">
                <div class="field">
                    <label for="commercial_name"><?= t('clients.commercial_name') ?> <span class="required-star">*</span></label>
                    <input id="commercial_name" type="text" name="commercial_name"
                           value="<?= htmlspecialchars($client['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="legal_name"><?= t('clients.legal_name') ?></label>
                    <input id="legal_name" type="text" name="legal_name"
                           value="<?= htmlspecialchars($client['legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="cif"><?= t('clients.cif') ?></label>
                    <input id="cif" type="text" name="cif"
                           value="<?= htmlspecialchars($client['cif'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label><?= t('common.sector') ?></label>
                    <?php
                    $sectorsOptionsJson = htmlspecialchars(json_encode(array_map(function ($sector) {
                        return ['id' => (int) $sector['id'], 'name' => $sector['name']];
                    }, $sectors)), ENT_QUOTES, 'UTF-8');
                    $selectedSector = [];
                    foreach ($sectors as $sector) {
                        if ((int) $sector['id'] === (int) ($client['sector_id'] ?? 0)) {
                            $selectedSector = [['id' => (int) $sector['id'], 'name' => $sector['name']]];
                            break;
                        }
                    }
                    $selectedSectorJson = htmlspecialchars(json_encode($selectedSector), ENT_QUOTES, 'UTF-8');
                    ?>
                    <input type="hidden" name="sector_id" value="">
                    <div class="token-picker token-picker--single"
                         data-options="<?= $sectorsOptionsJson ?>"
                         data-name="sector_id"
                         data-max="1"
                         data-placeholder="<?= t('common.search_sector') ?>"
                         data-selected="<?= $selectedSectorJson ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php
        $preselectedTagsJson = json_encode(array_values(array_map(function ($tag) {
            return ['id' => (int) $tag['id'], 'name' => $tag['name'], 'color' => $tag['color'] ?? null];
        }, array_filter($tags, function ($tag) use ($selectedTagIds) {
            return in_array((int) $tag['id'], $selectedTagIds ?? [], true);
        }))));
        ?>
        <div class="form-section">
            <div class="form-section-title"><?= t('common.tags') ?></div>
            <div class="token-picker"
                 data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                 data-name="tag_ids[]"
                 data-with-color="1"
                 data-placeholder="<?= t('common.search_tags') ?>"
                 data-paginate="1"
                 data-selected="<?= htmlspecialchars($preselectedTagsJson, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><?= t('common.location') ?></div>
            <div class="form-grid">
                <div class="field field-full">
                    <label for="address"><?= t('common.address') ?></label>
                    <input id="address" type="text" name="address" value="<?= htmlspecialchars($client['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php foreach (['postal_code', 'city', 'province', 'country'] as $locationField): ?>
                    <div class="field">
                        <label for="<?= $locationField ?>"><?= t('common.' . $locationField) ?></label>
                        <input id="<?= $locationField ?>" type="text" name="<?= $locationField ?>"
                               value="<?= htmlspecialchars($client[$locationField] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><?= t('common.other') ?></div>
            <div class="form-grid">
                <div class="field">
                    <label for="website"><?= t('common.website') ?></label>
                    <input id="website" type="url" name="website" value="<?= htmlspecialchars($client['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_web_connected" value="1" <?= !empty($client['is_web_connected']) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        <span class="toggle-label"><?= t('clients.connected_to_web') ?></span>
                    </label>
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1"
                               <?= (!$isEdit && !isset($client['is_active'])) || !empty($client['is_active']) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        <span class="toggle-label"><?= t('clients.active_client') ?></span>
                    </label>
                </div>
                <div class="field field-full">
                    <label for="notes"><?= t('common.notes') ?></label>
                    <textarea id="notes" name="notes" class="textarea-notes"><?= htmlspecialchars($client['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <?php if (!empty($customFields)): ?>
            <div class="form-section">
                <div class="form-section-title"><?= t('common.custom_fields') ?></div>
                <div class="form-grid">
                    <?php foreach ($customFields as $field): ?>
                        <?php require dirname(__DIR__) . '/partials/custom-field-input.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= t($isEdit ? 'clients.update_btn' : 'clients.create_btn_form') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url($cancelPath), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <?php if ($isEdit): ?>
                <button class="btn btn-danger btn-sm" type="submit"
                        formaction="<?= htmlspecialchars(Auth::url('/clients/delete'), ENT_QUOTES, 'UTF-8') ?>"
                        formnovalidate
                        data-confirm="<?= htmlspecialchars(Lang::get('clients.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>"
                        onclick="return confirm(this.dataset.confirm)"><?= t('common.delete') ?></button>
            <?php endif; ?>
        </div>
    </div>
</form>
