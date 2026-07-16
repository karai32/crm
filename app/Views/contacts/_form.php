<?php
$isEdit = $isEdit ?? false;
$contactId = (int) ($contact['id'] ?? 0);
$cancelPath = $isEdit ? '/contacts/show?id=' . $contactId : '/contacts';
$formAction = $isEdit ? '/contacts/update' : '/contacts/store';
?>
<div class="page-header contacts-header">
    <div>
        <h1><?= t($isEdit ? 'contacts.edit_title' : 'contacts.create_title') ?></h1>
        <span class="count-label">
            <?= $isEdit
                ? htmlspecialchars($contact['full_name'] ?? '', ENT_QUOTES, 'UTF-8')
                : t('common.fill_fields') ?>
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
        <input type="hidden" name="id" value="<?= $contactId ?>">
    <?php endif; ?>

    <div class="contact-form-card">
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
                <?php if ($isEdit): ?>
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
                            <?php foreach (['corporate' => 'buildings', 'consumer' => 'user-circle', 'invalid' => 'warning'] as $state => $icon): ?>
                                <label class="email-state-option email-state-option--<?= $state ?>">
                                    <input type="radio" name="email_state" value="<?= $state ?>" <?= $emailState === $state ? 'checked' : '' ?>>
                                    <span class="email-state-option-inner"><i class="ph ph-<?= $icon ?>"></i><span><?= t('common.' . $state) ?></span></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
            <button class="btn btn-primary" type="submit"><?= t($isEdit ? 'contacts.update' : 'contacts.save') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url($cancelPath), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <?php if ($isEdit): ?>
                <button class="btn btn-danger btn-sm" type="submit"
                        formaction="<?= htmlspecialchars(Auth::url('/contacts/delete'), ENT_QUOTES, 'UTF-8') ?>"
                        formnovalidate
                        data-confirm="<?= htmlspecialchars(Lang::get('contacts.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>"
                        onclick="return confirm(this.dataset.confirm)"><?= t('common.delete') ?></button>
            <?php endif; ?>
        </div>
    </div>
</form>
