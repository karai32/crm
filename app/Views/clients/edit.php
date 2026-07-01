<!-- Header -->
<div class="page-header clients-header">
    <div>
        <h1><?= t('clients.edit_title') ?></h1>
        <span class="count-label"><?= htmlspecialchars($client['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $client['id']), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/clients/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">

    <div class="client-form-card">

        <!-- Basic info -->
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
                    $sectorsOptionsJson = htmlspecialchars(json_encode(array_map(function ($s) {
                        return ['id' => (int) $s['id'], 'name' => $s['name']];
                    }, $sectors)), ENT_QUOTES, 'UTF-8');
                    $selectedSectorArr = [];
                    foreach ($sectors as $s) {
                        if ((int) $s['id'] === (int) ($client['sector_id'] ?? 0)) {
                            $selectedSectorArr = [['id' => (int) $s['id'], 'name' => $s['name']]];
                            break;
                        }
                    }
                    $selectedSectorJson = htmlspecialchars(json_encode($selectedSectorArr), ENT_QUOTES, 'UTF-8');
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

        <!-- Tags -->
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

        <!-- Location -->
        <div class="form-section">
            <div class="form-section-title"><?= t('common.location') ?></div>
            <div class="form-grid">
                <div class="field field-full">
                    <label for="address"><?= t('common.address') ?></label>
                    <input id="address" type="text" name="address"
                           value="<?= htmlspecialchars($client['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="postal_code"><?= t('common.postal_code') ?></label>
                    <input id="postal_code" type="text" name="postal_code"
                           value="<?= htmlspecialchars($client['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="city"><?= t('common.city') ?></label>
                    <input id="city" type="text" name="city"
                           value="<?= htmlspecialchars($client['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="province"><?= t('common.province') ?></label>
                    <input id="province" type="text" name="province"
                           value="<?= htmlspecialchars($client['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="country"><?= t('common.country') ?></label>
                    <input id="country" type="text" name="country"
                           value="<?= htmlspecialchars($client['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <!-- Online & Notes -->
        <div class="form-section">
            <div class="form-section-title"><?= t('common.other') ?></div>
            <div class="form-grid">
                <div class="field">
                    <label for="website"><?= t('common.website') ?></label>
                    <input id="website" type="url" name="website"
                           value="<?= htmlspecialchars($client['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_web_connected" value="1"
                               <?= !empty($client['is_web_connected']) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        <span class="toggle-label"><?= t('clients.connected_to_web') ?></span>
                    </label>
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1"
                               <?= !empty($client['is_active']) ? 'checked' : '' ?>>
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

        <!-- Custom fields -->
        <?php if (!empty($customFields)): ?>
        <div class="form-section">
            <div class="form-section-title"><?= t('common.custom_fields') ?></div>
            <div class="form-grid">
                <?php foreach ($customFields as $field): ?>
                <?php
                    $fieldId  = (int) $field['id'];
                    $valueRow = $customValues[$fieldId] ?? null;
                    $rawValue = is_array($valueRow)
                        ? ($valueRow['value_text'] ?? $valueRow['value_number'] ?? $valueRow['value_date'] ?? $valueRow['value_bool'] ?? '')
                        : $valueRow;
                    $isCheck  = $field['field_type'] === 'checkbox';
                ?>
                <div class="field <?= $isCheck ? 'checkbox-field' : '' ?>">
                    <?php if ($field['field_type'] === 'textarea'): ?>
                        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]" class="textarea-cf"><?= htmlspecialchars((string) $rawValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php elseif ($field['field_type'] === 'select'): ?>
                        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]">
                            <option value=""><?= t('common.choose') ?></option>
                            <?php foreach ($field['options'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['value'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= ((string) $rawValue === (string) $opt['value']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt['label'] ?? $opt['value'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($isCheck): ?>
                        <label class="label-checkbox-top">
                            <input id="cf_<?= $fieldId ?>" type="checkbox" name="custom_fields[<?= $fieldId ?>]" value="1"
                                   <?= $rawValue ? 'checked' : '' ?>>
                            <?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php else: ?>
                        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <input id="cf_<?= $fieldId ?>"
                               type="<?= htmlspecialchars($field['field_type'], ENT_QUOTES, 'UTF-8') ?>"
                               name="custom_fields[<?= $fieldId ?>]"
                               value="<?= htmlspecialchars((string) $rawValue, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= t('clients.update_btn') ?></button>
            <a class="btn btn-outlined"
               href="<?= htmlspecialchars(Auth::url('/clients/show?id=' . (int) $client['id']), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/clients/delete?id=' . (int) $client['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('<?= htmlspecialchars(Lang::get('clients.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')"><?= t('common.delete') ?></a>
        </div>

    </div>
</form>
