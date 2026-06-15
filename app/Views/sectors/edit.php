<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Edit sector</h1>
        <span class="count-label"><?= htmlspecialchars($sector['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/sectors/update'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $sector['id'] ?>">
    <div class="settings-form-card">
        <div class="settings-form-body">
            <div class="field">
                <label for="name">Name <span class="required-star">*</span></label>
                <input id="name" type="text" name="name"
                       value="<?= htmlspecialchars($sector['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field checkbox-field">
                <label>
                    <input type="checkbox" name="is_active" value="1"
                           <?= ((int) ($sector['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                    Active
                </label>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Update sector</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <a class="btn btn-danger btn-sm"
               href="<?= htmlspecialchars(Auth::url('/sectors/delete?id=' . (int) $sector['id']), ENT_QUOTES, 'UTF-8') ?>"
               onclick="return confirm('Delete this sector? If it is used by clients, it will be deactivated.')">Delete</a>
        </div>
    </div>
</form>
