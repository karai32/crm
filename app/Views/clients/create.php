<!-- Header -->
<div class="page-header clients-header">
    <div>
        <h1>Create client</h1>
        <span class="count-label">Fill in the client information below</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/clients/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>

    <div class="client-form-card">

        <!-- Basic info -->
        <div class="form-section">
            <div class="form-section-title">Basic Information</div>
            <div class="form-grid">
                <div class="field">
                    <label for="commercial_name">Commercial name <span class="required-star">*</span></label>
                    <input id="commercial_name" type="text" name="commercial_name"
                           value="<?= htmlspecialchars($client['commercial_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="legal_name">Legal name</label>
                    <input id="legal_name" type="text" name="legal_name"
                           value="<?= htmlspecialchars($client['legal_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="cif">CIF</label>
                    <input id="cif" type="text" name="cif"
                           value="<?= htmlspecialchars($client['cif'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label>Sector</label>
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
                         data-placeholder="Search sector..."
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
            <div class="form-section-title">Tags</div>
            <div class="token-picker"
                 data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/tags/search'), ENT_QUOTES, 'UTF-8') ?>"
                 data-name="tag_ids[]"
                 data-with-color="1"
                 data-placeholder="Search tags..."
                 data-paginate="1"
                 data-selected="<?= htmlspecialchars($preselectedTagsJson, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <!-- Location -->
        <div class="form-section">
            <div class="form-section-title">Location</div>
            <div class="form-grid">
                <div class="field field-full">
                    <label for="address">Address</label>
                    <input id="address" type="text" name="address"
                           value="<?= htmlspecialchars($client['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="postal_code">Postal code</label>
                    <input id="postal_code" type="text" name="postal_code"
                           value="<?= htmlspecialchars($client['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" type="text" name="city"
                           value="<?= htmlspecialchars($client['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="province">Province</label>
                    <input id="province" type="text" name="province"
                           value="<?= htmlspecialchars($client['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="country">Country</label>
                    <input id="country" type="text" name="country"
                           value="<?= htmlspecialchars($client['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
        </div>

        <!-- Online & Notes -->
        <div class="form-section">
            <div class="form-section-title">Other</div>
            <div class="form-grid">
                <div class="field">
                    <label for="website">Website</label>
                    <input id="website" type="url" name="website"
                           value="<?= htmlspecialchars($client['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_web_connected" value="1"
                               <?= !empty($client['is_web_connected']) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        <span class="toggle-label">Connected to Web / API</span>
                    </label>
                </div>
                <div class="field checkbox-field">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1"
                               <?= !isset($client['is_active']) || !empty($client['is_active']) ? 'checked' : '' ?>>
                        <span class="toggle-track"></span>
                        <span class="toggle-label">Active client</span>
                    </label>
                </div>
                <div class="field field-full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="textarea-notes"><?= htmlspecialchars($client['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Custom fields -->
        <?php if (!empty($customFields)): ?>
        <div class="form-section">
            <div class="form-section-title">Custom Fields</div>
            <div class="form-grid">
                <?php foreach ($customFields as $field): ?>
                <?php
                    $fieldId  = (int) $field['id'];
                    $rawValue = $customValues[$fieldId] ?? '';
                    $isCheck  = $field['field_type'] === 'checkbox';
                ?>
                <div class="field <?= $isCheck ? 'checkbox-field' : '' ?>">
                    <?php if ($field['field_type'] === 'textarea'): ?>
                        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]" class="textarea-cf"><?= htmlspecialchars((string) $rawValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php elseif ($field['field_type'] === 'select'): ?>
                        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
                        <select id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]">
                            <option value="">Choose...</option>
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
            <button class="btn btn-primary" type="submit">Create client</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>

    </div>
</form>
