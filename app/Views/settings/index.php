<?php
$savedPerPage   = (int) ($prefs['per_page'] ?? 20);
$perPageOptions = [20, 50, 100, 200];
?>

<div class="page-header settings-header">
    <div>
        <h1>Settings</h1>
        <span class="count-label">Your personal preferences</span>
    </div>
</div>

<div class="settings-form-card">
    <form method="post" action="<?= htmlspecialchars(Auth::url('/settings/update'), ENT_QUOTES, 'UTF-8') ?>">
        <?= Csrf::field() ?>
        <div class="settings-form-body">
            <div class="field">
                <label for="per_page">Records per page</label>
                <select id="per_page" name="per_page" class="settings-select-sm">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $savedPerPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="field-hint">Number of rows displayed per page in all tables.</span>
            </div>
        </div>
        <div class="settings-form-actions">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</div>