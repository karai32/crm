<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Create tag</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/tags/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name">Name <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="e.g. VIP" required autofocus>
            </div>
            <div class="field">
                <label for="color">Color</label>
                <div class="color-input-row">
                    <input id="color" type="text" name="color"
                           value="<?= htmlspecialchars($color ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="#2563eb" maxlength="7">
                    <input type="color" id="color-picker" class="color-picker-input"
                           value="<?= htmlspecialchars($color ?: '#2563eb', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <span class="color-hint">Hex format: #rrggbb</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Create tag</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </div>
</form>

<script>
(function () {
    const textInput = document.getElementById('color');
    const picker = document.getElementById('color-picker');
    const hexRe = /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/;

    textInput.addEventListener('input', function () {
        const val = textInput.value.trim();
        if (hexRe.test(val)) picker.value = val;
    });

    picker.addEventListener('input', function () {
        textInput.value = picker.value;
    });
}());
</script>
