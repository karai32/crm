<?php
function userInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $a = strtoupper(substr($parts[0] ?? '', 0, 1));
    $b = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $a . $b;
}

function userPermissionIcon(string $permission): string
{
    return match ($permission) {
        'contacts.create' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 21a7.5 7.5 0 0 1 15 0M19 8v6M22 11h-6"/></svg>',
        'contacts.edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 21a7.5 7.5 0 0 1 10.4-6.9M16.5 21l4.6-4.6a1.6 1.6 0 0 0-2.3-2.3l-4.6 4.6-.7 3 3-.7Z"/></svg>',
        'contacts.delete' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 21a7.5 7.5 0 0 1 11-6.65M18 17l4 4M22 17l-4 4"/></svg>',
        'clients.create' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h10M5 21V4h9v17M8 8h.01M11 8h.01M8 12h.01M11 12h.01M8 16h.01M18 12v8M22 16h-8"/></svg>',
        'clients.edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h9M5 21V4h9v12M8 8h.01M11 8h.01M8 12h.01M11 12h.01M16 21l4.6-4.6a1.6 1.6 0 0 0-2.3-2.3l-4.6 4.6-.7 3 3-.7Z"/></svg>',
        'clients.delete' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h10M5 21V4h9v17M8 8h.01M11 8h.01M8 12h.01M11 12h.01M18 17l4 4M22 17l-4 4"/></svg>',
        'exports.use' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14H6V3ZM14 3v5h5M12 11v7M9 15l3 3 3-3"/></svg>',
        'imports.manage' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14H6V3ZM14 3v5h5M12 18v-7M9 14l3-3 3 3"/></svg>',
        'sectors.manage' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3M12 18v3M3 12h3M18 12h3M6.6 6.6l2.1 2.1M15.3 15.3l2.1 2.1M17.4 6.6l-2.1 2.1M8.7 15.3l-2.1 2.1M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>',
        'tags.manage' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5V11l8.8 8.8a2 2 0 0 0 2.8 0l4.2-4.2a2 2 0 0 0 0-2.8L11 4H5.5A1.5 1.5 0 0 0 4 5.5ZM8 8h.01"/></svg>',
        'custom_fields.manage' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v5M14 3v5h5M6 21h6M6 3v18M18 14v2M18 22v-2M14 18h2M22 18h-2M20.8 15.2l-1.4 1.4M16.6 19.4l-1.4 1.4M20.8 20.8l-1.4-1.4M16.6 16.6l-1.4-1.4"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5c5 0 8.5 4.5 9.5 7-1 2.5-4.5 7-9.5 7S3.5 14.5 2.5 12C3.5 9.5 7 5 12 5Z"/><circle cx="12" cy="12" r="3"/></svg>',
    };
}
?>

<!-- Header -->
<div class="page-header settings-header">
    <div>
        <h1>Users</h1>
        <span class="count-label"><?= count($users) ?> users</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= htmlspecialchars(Auth::url('/users/create'), ENT_QUOTES, 'UTF-8') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="btn-icon-sm">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create user
        </a>
    </div>
</div>

<div class="settings-table-card">
    <?php if (empty($users)): ?>
        <p class="table-empty-state">No users found.</p>
    <?php else: ?>
    <table class="settings-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Permissions</th>
                <th>Status</th>
                <th>Last login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <?php $isCurrentUser = ((int) $user['id'] === (int) (Auth::user()['id'] ?? 0)); ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm"><?= userInitials($user['name']) ?></div>
                            <div class="user-cell-body">
                                <span class="user-cell-name">
                                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="user-current-label">(you)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="user-cell-email"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php $role = strtolower($user['role'] ?? 'user'); ?>
                        <span class="badge-role badge-role-<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($user['role_label'] ?? $user['role'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <div class="permission-icons" aria-label="User permissions">
                            <?php
                            $hasPermissions = false;
                            foreach ($permissionDefinitions as $permissionKey => $permissionLabel):
                                if (empty($user['permissions'][$permissionKey])) {
                                    continue;
                                }
                                $hasPermissions = true;
                            ?>
                                <span class="permission-icon permission-icon-<?= htmlspecialchars(str_replace(['.', '_'], '-', $permissionKey), ENT_QUOTES, 'UTF-8') ?>"
                                      title="<?= htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8') ?>"
                                      aria-label="<?= htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= userPermissionIcon($permissionKey) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (!$hasPermissions): ?>
                                <span class="permission-icon permission-icon-muted"
                                      title="View only"
                                      aria-label="View only">
                                    <?= userPermissionIcon('view.only') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ((int) $user['is_active'] === 1): ?>
                            <span class="badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-last-login">
                        <?= htmlspecialchars($user['last_login_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <div class="action-links">
                            <a class="action-btn action-edit" title="Edit" href="<?= htmlspecialchars(Auth::url('/users/edit?id=' . $user['id']), ENT_QUOTES, 'UTF-8') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
