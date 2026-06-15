<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Edit custom field</h1>
        <span class="count-label"><?= htmlspecialchars($field['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/custom-fields'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/custom-fields/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $field['id'] ?>">
    <div class="settings-form-card settings-form-card--lg">
        <div class="settings-form-body">
            <div class="settings-form-grid">
                <div class="field">
                    <label for="entity_type">Entity type</label>
                    <select id="entity_type" name="entity_type">
                        <option value="contact" <?= (($field['entity_type'] ?? '') === 'contact') ? 'selected' : '' ?>>Contact</option>
                        <option value="client"  <?= (($field['entity_type'] ?? '') === 'client')  ? 'selected' : '' ?>>Client</option>
                    </select>
                </div>
                <div class="field">
                    <label for="field_type">Field type</label>
                    <select id="field_type" name="field_type">
                        <?php foreach (['text', 'textarea', 'number', 'date', 'email', 'url', 'select', 'checkbox'] as $type): ?>
                            <option value="<?= $type ?>" <?= (($field['field_type'] ?? 'text') === $type) ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="name">Name <span class="required-star">*</span></label>
                    <input id="name" type="text" name="name"
                           value="<?= htmlspecialchars($field['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="slug">Slug <span class="required-star">*</span></label>
                    <input id="slug" type="text" name="slug"
                           value="<?= htmlspecialchars($field['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="sort_order">Sort order</label>
                    <input id="sort_order" type="number" name="sort_order"
                           value="<?= (int) ($field['sort_order'] ?? 0) ?>" min="0">
                </div>
                <div class="checkbox-row">
                    <label>
                        <input type="checkbox" name="is_required" value="1"
                               <?= ((int) ($field['is_required'] ?? 0) === 1) ? 'checked' : '' ?>>
                        Required
                    </label>
                    <label>
                        <input type="checkbox" name="is_filterable" value="1"
                               <?= ((int) ($field['is_filterable'] ?? 1) === 1) ? 'checked' : '' ?>>
                        Filterable
                    </label>
                </div>
                <div class="field field-full" id="options-field">
                    <label for="options">Select options <span class="label-options-hint">(one per line)</span></label>
                    <textarea id="options" name="options" rows="5"
                              placeholder="Option 1&#10;Option 2&#10;Option 3"><?= htmlspecialchars($optionsText ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Update field</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/custom-fields'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/custom-fields/delete?id=' . (int) $field['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('Delete this custom field? Values will also be deleted.')">Delete</a>
        </div>
    </div>
</form>

<script>
(function () {
    var typeSelect   = document.getElementById('field_type');
    var optionsField = document.getElementById('options-field');
    function toggle() {
        optionsField.style.display = typeSelect.value === 'select' ? '' : 'none';
    }
    typeSelect.addEventListener('change', toggle);
    toggle();
}());
</script>
