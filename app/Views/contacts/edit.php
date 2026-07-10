<!-- Header -->
<div class="page-header contacts-header">
    <div>
        <h1><?= t('contacts.edit_title') ?></h1>
        <span class="count-label"><?= htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/contacts/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">

    <div class="contact-form-card">

        <!-- Basic info -->
        <div class="form-section">
            <div class="form-section-title"><?= t('common.basic_info') ?></div>
            <div class="form-grid">
                <div class="field">
                    <label for="full_name"><?= t('contacts.full_name') ?> <span class="required-star">*</span></label>
                    <input id="full_name" type="text" name="full_name"
                           value="<?= htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="email"><?= t('common.email') ?></label>
                    <input id="email" type="email" name="email"
                           value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php
                $emailState = '';
                if (($contact['email_status'] ?? null) === 'invalid') {
                    $emailState = 'invalid';
                } elseif (isset($contact['is_corporate_email'])) {
                    $emailState = (int) $contact['is_corporate_email'] === 1 ? 'corporate' : 'consumer';
                }
                ?>
                <div class="field">
                    <label><?= t('filter.email_status') ?></label>
                    <div class="email-state-switch" role="radiogroup" aria-label="<?= t('filter.email_status') ?>">
                        <label class="email-state-option email-state-option--corporate">
                            <input type="radio" name="email_state" value="corporate" <?= $emailState === 'corporate' ? 'checked' : '' ?>>
                            <span class="email-state-option-inner"><i class="ph ph-buildings"></i><span><?= t('common.corporate') ?></span></span>
                        </label>
                        <label class="email-state-option email-state-option--consumer">
                            <input type="radio" name="email_state" value="consumer" <?= $emailState === 'consumer' ? 'checked' : '' ?>>
                            <span class="email-state-option-inner"><i class="ph ph-user-circle"></i><span><?= t('common.consumer') ?></span></span>
                        </label>
                        <label class="email-state-option email-state-option--invalid">
                            <input type="radio" name="email_state" value="invalid" <?= $emailState === 'invalid' ? 'checked' : '' ?>>
                            <span class="email-state-option-inner"><i class="ph ph-warning"></i><span><?= t('common.invalid') ?></span></span>
                        </label>
                    </div>
                </div>
                <div class="field">
                    <label for="phone"><?= t('common.phone') ?></label>
                    <input id="phone" type="text" name="phone"
                           value="<?= htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="company"><?= t('contacts.company') ?></label>
                    <input id="company" type="text" name="company" placeholder="<?= t('contacts.company_placeholder') ?>"
                           value="<?= htmlspecialchars($contact['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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

        <!-- Clients -->
        <?php
        $preselectedClientsJson = json_encode(array_values(array_map(function ($client) {
            return ['id' => (int) $client['id'], 'name' => $client['commercial_name']];
        }, array_filter($clients, function ($client) use ($selectedClientIds) {
            return in_array((int) $client['id'], $selectedClientIds ?? [], true);
        }))));
        ?>
        <div class="form-section">
            <div class="form-section-title"><?= t('contacts.linked_clients') ?></div>
            <div class="token-picker"
                 data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/clients/search'), ENT_QUOTES, 'UTF-8') ?>"
                 data-name="client_ids[]"
                 data-placeholder="<?= t('common.search_clients') ?>"
                 data-selected="<?= htmlspecialchars($preselectedClientsJson, ENT_QUOTES, 'UTF-8') ?>">
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
            <button class="btn btn-primary" type="submit"><?= t('contacts.update') ?></button>
            <a class="btn btn-outlined"
               href="<?= htmlspecialchars(Auth::url('/contacts/show?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/contacts/delete?id=' . (int) $contact['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('<?= htmlspecialchars(Lang::get('contacts.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>')"><?= t('common.delete') ?></a>
        </div>

    </div>
</form>
