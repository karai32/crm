<?php
$fieldId = (int) $field['id'];
$valueRow = $customValues[$fieldId] ?? null;
$rawValue = is_array($valueRow)
    ? ($valueRow['value_text'] ?? $valueRow['value_number'] ?? $valueRow['value_date'] ?? $valueRow['value_bool'] ?? '')
    : $valueRow;
$isCheckbox = $field['field_type'] === 'checkbox';
?>
<div class="field <?= $isCheckbox ? 'checkbox-field' : '' ?>">
    <?php if ($field['field_type'] === 'textarea'): ?>
        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
        <textarea id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]" class="textarea-cf"><?= htmlspecialchars((string) $rawValue, ENT_QUOTES, 'UTF-8') ?></textarea>
    <?php elseif ($field['field_type'] === 'select'): ?>
        <label for="cf_<?= $fieldId ?>"><?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?></label>
        <select id="cf_<?= $fieldId ?>" name="custom_fields[<?= $fieldId ?>]">
            <option value=""><?= t('common.choose') ?></option>
            <?php foreach ($field['options'] as $option): ?>
                <option value="<?= htmlspecialchars($option['value'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= (string) $rawValue === (string) $option['value'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($option['label'] ?? $option['value'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($isCheckbox): ?>
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
