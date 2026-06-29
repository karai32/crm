<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'ContactCore', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(Auth::url('/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
 <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/base.css'), ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($styles ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/' . $stylesheet), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>

<?php
    $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $isActive = function (string $segment) use ($currentPath): string {
        return str_contains($currentPath, $segment) ? 'active' : '';
    };

    $authUser   = class_exists('Auth') && Auth::check() ? Auth::user() : null;
    $initials   = '';
    $canManageSectors = class_exists('Auth') && Auth::can('sectors.manage');
    $canManageTags = class_exists('Auth') && Auth::can('tags.manage');
    $canManageCustomFields = class_exists('Auth') && Auth::can('custom_fields.manage');
    $canManageImports = class_exists('Auth') && Auth::can('imports.manage');
    $canUseExports = class_exists('Auth') && Auth::can('exports.use');
    $canManageUsers   = class_exists('Auth') && Auth::isAdmin();
    $canManageApiKeys = class_exists('Auth') && Auth::isAdmin();
    $showSettingsNav = $canManageSectors || $canManageTags || $canManageCustomFields || $canManageImports || $canUseExports || $canManageUsers || $canManageApiKeys;
    if ($authUser) {
        $parts    = explode(' ', trim($authUser['name'] ?? ''));
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }
    ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-shell">

    <?php if ($authUser): ?>
    <!-- -- Sidebar --------------------------------------- -->
    <aside class="sidebar" id="sidebar">

        <a href="<?= htmlspecialchars(Auth::url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-brand">
            <div class="sidebar-logo">
                <i class="ph ph-squares-four"></i>
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-title">ContactCore</span>
                <span class="sidebar-subtitle">Client relationship CRM</span>
            </div>
        </a>

        <nav class="sidebar-nav">

            <a href="<?= htmlspecialchars(Auth::url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/dashboard') ?>" title="Dashboard">
                <span class="nav-icon"><i class="ph ph-house-line"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>

            <a href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/contacts') ?>" title="Contacts">
                <span class="nav-icon"><i class="ph ph-address-book"></i></span>
                <span class="nav-label">Contacts</span>
            </a>

            <a href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/clients') ?>" title="Clients">
                <span class="nav-icon"><i class="ph ph-buildings"></i></span>
                <span class="nav-label">Clients</span>
            </a>

            <?php if ($showSettingsNav): ?>

            <?php if ($canManageSectors): ?>
            <a href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/sectors') ?>" title="Sectors">
                <span class="nav-icon"><i class="ph ph-crosshair"></i></span>
                <span class="nav-label">Sectors</span>
            </a>
            <?php endif; ?>

            <?php if ($canManageTags): ?>
            <a href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/tags') ?>" title="Tags">
                <span class="nav-icon"><i class="ph ph-tag"></i></span>
                <span class="nav-label">Tags</span>
            </a>
            <?php endif; ?>

            <?php if ($canManageCustomFields || $canManageImports || $canUseExports): ?>
            <div class="sidebar-section-label"><span class="nav-label">Settings</span></div>
            <?php endif; ?>

            <?php if ($authUser): ?>
            <a href="<?= htmlspecialchars(Auth::url('/settings'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/settings') ?>" title="Settings">
                <span class="nav-icon"><i class="ph ph-gear"></i></span>
                <span class="nav-label">Settings</span>
            </a>
            <?php endif; ?>

            <?php if ($canManageCustomFields): ?>
            <a href="<?= htmlspecialchars(Auth::url('/custom-fields'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/custom-fields') ?>" title="Custom Fields">
                <span class="nav-icon"><i class="ph ph-sliders-horizontal"></i></span>
                <span class="nav-label">Custom Fields</span>
            </a>
            <?php endif; ?>

            <?php if ($canManageImports): ?>
            <a href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/imports') ?>" title="Imports">
                <span class="nav-icon"><i class="ph ph-download-simple"></i></span>
                <span class="nav-label">Imports</span>
            </a>
            <?php endif; ?>

            <?php if ($canUseExports): ?>
            <a href="<?= htmlspecialchars(Auth::url('/exports'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/exports') ?>" title="Exports">
                <span class="nav-icon"><i class="ph ph-upload-simple"></i></span>
                <span class="nav-label">Exports</span>
            </a>
            <?php endif; ?>
            
            <?php endif; ?>

            <?php if ($authUser && ($canManageUsers || $canManageApiKeys)): ?>
                <div class="sidebar-section-label"><span class="nav-label">Admin</span></div>
            <?php endif; ?>

            <?php if ($canManageUsers): ?>
                <a href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>"
                class="nav-item <?= $isActive('/users') ?>" title="Users">
                    <span class="nav-icon"><i class="ph ph-users"></i></span>
                    <span class="nav-label">Users</span>
               </a>
            <?php endif; ?>

            <?php if ($canManageApiKeys): ?>
            <a href="<?= htmlspecialchars(Auth::url('/api-keys'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/api-keys') ?>" title="API Credentials">
                <span class="nav-icon"><i class="ph ph-key"></i></span>
                <span class="nav-label">API Credentials</span>
            </a>
            <?php endif; ?>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user-actions" id="sidebarUserActions">
                <?php if (Auth::isAdmin()): ?>
                <a class="sidebar-user-action" href="<?= htmlspecialchars(Auth::url('/users/edit?id=' . ($authUser['id'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="nav-icon">
                        <i class="ph ph-user"></i>
                    </span>
                    <span class="nav-label">View Profile</span>
                </a>
                <?php endif; ?>
                <a class="sidebar-user-action sidebar-user-action--danger"
                   href="<?= htmlspecialchars(Auth::url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="nav-icon">
                        <i class="ph ph-sign-out"></i>
                    </span>
                    <span class="nav-label">Logout</span>
                </a>
            </div>
            <button class="sidebar-user" id="sidebarUserBtn" type="button">
                <div class="sidebar-user-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?= htmlspecialchars($authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="sidebar-user-role"><?= htmlspecialchars(ucfirst($authUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </button>
        </div>

    </aside>
    <?php endif; ?>

    <!-- -- Main Area -------------------------------------- -->
    <div class="main-area">

        <?php if ($authUser): ?>
        <header class="topbar">
            <button class="burger-btn" id="burgerBtn" aria-label="Toggle menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <div class="topbar-search" data-global-search>
                <i class="ph ph-magnifying-glass topbar-search-icon"></i>
                <input type="search"
                       data-global-search-input
                       data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/global-search'), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Search contacts and clients..."
                       autocomplete="off"
                       spellcheck="false">
                <div class="topbar-search-dropdown" data-global-search-results></div>
            </div>

            <div class="topbar-actions">
                <a class="topbar-icon-btn" href="<?= htmlspecialchars(Auth::url('/help'), ENT_QUOTES, 'UTF-8') ?>" title="Help" aria-label="Open help">
                    <span class="topbar-help-mark" aria-hidden="true">?</span>
                </a>
                <div class="profile-wrap" id="profileWrap">
                    <button class="topbar-avatar" id="profileBtn"
                            title="<?= htmlspecialchars($authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-dropdown-header">
                            <span class="profile-dropdown-name"><?= htmlspecialchars($authUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="profile-dropdown-role"><?= htmlspecialchars(ucfirst($authUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="profile-dropdown-divider"></div>
                        <?php if (Auth::isAdmin()): ?>
                        <a class="profile-dropdown-item" href="<?= htmlspecialchars(Auth::url('/users/edit?id=' . ($authUser['id'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="ph ph-user"></i>
                            View Profile
                        </a>
                        <?php endif; ?>
                        <a class="profile-dropdown-item profile-dropdown-item--danger"
                           href="<?= htmlspecialchars(Auth::url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="ph ph-sign-out"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <?php endif; ?>

        <main class="page-content">
            <?= $content ?>
        </main>

    </div>
</div>

<script src="<?= htmlspecialchars(Auth::url('/assets/js/admin.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
