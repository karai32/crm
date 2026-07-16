<?php
$isEdit = $isEdit ?? false;
$userId = (int) ($user['id'] ?? 0);
$isCurrentUser = $isEdit && $userId === (int) (Auth::user()['id'] ?? 0);
$selectedRoleName = 'user';
foreach ($roles as $role) {
    if ((int) ($user['role_id'] ?? 0) === (int) $role['id']) {
        $selectedRoleName = (string) $role['name'];
        break;
    }
}
?>
<div class="page-header settings-header">
    <div>
        <h1><?= t($isEdit ? 'users.edit_title' : 'users.create_btn') ?></h1>
        <?php if ($isEdit): ?>
            <span class="count-label"><?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars(Auth::url($isEdit ? '/users/update' : '/users/store'), ENT_QUOTES, 'UTF-8') ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $userId ?>">
    <?php endif; ?>
    <div class="settings-form-card settings-form-card--lg">
        <div class="settings-form-body">
            <div class="settings-form-grid">
                <div class="field">
                    <label for="name"><?= t('users.full_name') ?> <span class="required-star">*</span></label>
                    <input id="name" type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required <?= $isEdit ? '' : 'autofocus' ?>>
                </div>
                <div class="field">
                    <label for="email"><?= t('common.email') ?> <span class="required-star">*</span></label>
                    <input id="email" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label for="password"><?= t('users.password') ?><?= $isEdit ? '' : ' <span class="required-star">*</span>' ?></label>
                    <input id="password" type="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                    <?php if ($isEdit): ?><span class="field-hint"><?= t('users.password_hint') ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="role_id"><?= t('users.role') ?></label>
                    <select id="role_id" name="role_id" data-role-select required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['id'] ?>" data-role-name="<?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ((int) ($user['role_id'] ?? 0) === (int) $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="checkbox-row">
                    <label <?= $isCurrentUser ? 'title="' . t('users.cannot_deactivate_self') . '"' : '' ?>>
                        <input type="checkbox" name="is_active" value="1"
                               <?= ((int) ($user['is_active'] ?? 1) === 1) ? 'checked' : '' ?> <?= $isCurrentUser ? 'disabled' : '' ?>>
                        <?= t('common.active') ?>
                    </label>
                    <?php if ($isCurrentUser): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
                </div>
            </div>
            <div class="permissions-panel <?= $selectedRoleName === 'user' ? '' : 'is-hidden' ?>" data-permissions-panel>
                <div class="permissions-panel-header">
                    <h2><?= t('users.individual_permissions') ?></h2>
                    <p><?= t('users.permissions_hint') ?></p>
                </div>
                <div class="permissions-grid">
                    <?php foreach ($permissionDefinitions as $permissionKey => $permissionLabel): ?>
                        <label class="permission-option">
                            <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($permissionKey, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= !empty($userPermissions[$permissionKey]) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="settings-form-actions">
            <button class="btn btn-primary" type="submit"><?= t($isEdit ? 'users.update' : 'users.create_btn') ?></button>
            <a class="btn btn-outlined" href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>"><?= t('common.cancel') ?></a>
            <?php if ($isEdit && !$isCurrentUser && (int) ($user['is_active'] ?? 0) === 1): ?>
                <button class="btn btn-danger btn-sm" type="submit"
                        formaction="<?= htmlspecialchars(Auth::url('/users/delete'), ENT_QUOTES, 'UTF-8') ?>"
                        formnovalidate data-confirm="<?= htmlspecialchars(Lang::get('users.deactivate_confirm'), ENT_QUOTES, 'UTF-8') ?>"
                        onclick="return confirm(this.dataset.confirm)"><?= t('users.deactivate') ?></button>
            <?php endif; ?>
        </div>
    </div>
</form>
