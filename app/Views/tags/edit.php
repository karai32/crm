<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Edit tag</h1>
        <span class="count-label"><?= htmlspecialchars($tag['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/tags/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $tag['id'] ?>">
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name">Name <span style="color:var(--color-danger)">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($tag['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label for="color">Color</label>
                <div class="color-input-row">
                    <input id="color" type="text" name="color"
                           value="<?= htmlspecialchars($tag['color'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="#2563eb" maxlength="7">
                    <div class="color-preview" id="color-preview"></div>
                </div>
                <span style="font-size:12px;color:var(--color-text-muted);margin-top:4px;display:block">Hex format: #rrggbb</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Update tag</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <a class="btn btn-danger btn-sm" style="margin-left:auto"
               href="<?= htmlspecialchars(Auth::url('/tags/delete?id=' . (int) $tag['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('Delete this tag? Existing contact and client links will be removed.')">Delete</a>
        </div>
    </div>
</form>

<script>
(function () {
    const input = document.getElementById('color');
    const preview = document.getElementById('color-preview');
    function update() {
        const val = input.value.trim();
        preview.style.background = /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(val) ? val : '';
    }
    input.addEventListener('input', update);
    update();
}());
</script>
