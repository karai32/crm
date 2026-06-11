<!-- Header -->
<div class="page-header contacts-header">
    <div>
        <h1>Create contact</h1>
        <span class="count-label">Fill in the fields below</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/contacts/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>

    <div class="contact-form-card">

        <!-- Basic info -->
        <div class="form-section">
            <div class="form-section-title">Basic Information</div>
            <div class="form-grid">
                <div class="field">
                    <label for="first_name">First name <span style="color:var(--color-danger)">*</span></label>
                    <input id="first_name" type="text" name="first_name"
                           value="<?= htmlspecialchars($contact['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input id="last_name" type="text" name="last_name"
                           value="<?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email"
                           value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input id="phone" type="text" name="phone"
                           value="<?= htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field checkbox-field">
                    <label style="padding-top:22px">
                        <input type="checkbox" name="is_company" value="1"
                               <?= ((int) ($contact['is_company'] ?? 0) === 1) ? 'checked' : '' ?>>
                        Is company
                    </label>
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
                 data-selected="<?= htmlspecialchars($preselectedTagsJson, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <!-- Clients -->
        <?php
        $preselectedClientsJson = json_encode(array_values(array_map(function ($client) {
            return ['id' => (int) $client['id'], 'name' => $client['commercial_name']];
        }, array_filter($clients, function ($client) use ($selectedClientIds) {
            return in_array((int) $client['id'], $selectedClientIds ?? [], true);
        }))));
        ?>
        <div class="form-section">
            <div class="form-section-title">Linked Clients</div>
            <div class="token-picker"
                 data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/search'), ENT_QUOTES, 'UTF-8') ?>"
                 data-name="client_ids[]"
                 data-placeholder="Search clients..."
                 data-selected="<?= htmlspecialchars($preselectedClientsJson, ENT_QUOTES, 'UTF-8') ?>">
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
                        <textarea id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]" style="min-height:70px"><?= htmlspecialchars((string) $rawValue, ENT_QUOTES, 'UTF-8') ?></textarea>
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
                        <label style="padding-top:22px">
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
            <button class="btn btn-primary" type="submit">Save contact</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>

    </div>
</form>
