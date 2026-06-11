<!-- Header -->
<?php
$selectedRoleName = 'user';
foreach ($roles as $role) {
    if ((int) ($user['role_id'] ?? 0) === (int) $role['id']) {
        $selectedRoleName = $role['name'];
        break;
    }
}
?>

<div class="page-header settings-header">
    <div>
        <h1>Create user</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url('/users/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <div class="settings-form-card settings-form-card--lg">
        <div class="settings-form-body">
            <div class="settings-form-grid">
                <div class="field">
                    <label for="name">Name <span style="color:var(--color-danger)">*</span></label>
                    <input id="name" type="text" name="name"
                           value="<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required autofocus>
                </div>
                <div class="field">
                    <label for="email">Email <span style="color:var(--color-danger)">*</span></label>
                    <input id="email" type="email" name="email"
                           value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>
                <div class="field">
                    <label for="password">Password <span style="color:var(--color-danger)">*</span></label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="field">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['id'] ?>"
                                    data-role-name="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ((int) ($user['role_id'] ?? 0) === (int) $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="checkbox-row">
                    <label>
                        <input type="checkbox" name="is_active" value="1"
                               <?= ((int) ($user['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                        Active
                    </label>
                </div>
            </div>
            <div class="permissions-panel <?= $selectedRoleName === 'user' ? '' : 'is-hidden' ?>" data-permissions-panel>
                <div class="permissions-panel-header">
                    <h2>Individual permissions</h2>
                    <p>These checkboxes apply only to users with the User role. Administrators always have full access.</p>
                </div>
                <div class="permissions-grid">
                    <?php foreach ($permissionDefinitions as $permissionKey => $permissionLabel): ?>
                        <label class="permission-option">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="<?= htmlspecialchars($permissionKey, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= !empty($userPermissions[$permissionKey]) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit">Create user</button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
    </div>
</form>

<script>
(function () {
    var roleSelect = document.getElementById('role_id');
    var panel = document.querySelector('[data-permissions-panel]');

    if (!roleSelect || !panel) return;

    function syncPermissionsPanel() {
        var selected = roleSelect.options[roleSelect.selectedIndex];
        var roleName = selected ? selected.getAttribute('data-role-name') : 'user';
        panel.classList.toggle('is-hidden', roleName !== 'user');
    }

    roleSelect.addEventListener('change', syncPermissionsPanel);
    syncPermissionsPanel();
}());
</script>
