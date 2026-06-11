<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'ContactCore', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(Auth::url('/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/base.css'), ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($styles ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(Auth::url('/assets/css/' . $stylesheet), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>

<?php
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$isActive = function(string $segment) use ($currentPath): string {
    return str_contains($currentPath, $segment) ? 'active' : '';
};

$authUser   = class_exists('Auth') && Auth::check() ? Auth::user() : null;
$initials   = '';
$canManageSectors = class_exists('Auth') && Auth::can('sectors.manage');
$canManageTags = class_exists('Auth') && Auth::can('tags.manage');
$canManageCustomFields = class_exists('Auth') && Auth::can('custom_fields.manage');
$canManageImports = class_exists('Auth') && Auth::can('imports.manage');
$canManageUsers = class_exists('Auth') && Auth::isAdmin();
$showSettingsNav = $canManageSectors || $canManageTags || $canManageCustomFields || $canManageImports || $canManageUsers;
if ($authUser) {
    $parts    = explode(' ', trim($authUser['name'] ?? ''));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-shell">

    <?php if ($authUser): ?>
    <!-- -- Sidebar --------------------------------------- -->
    <aside class="sidebar">

        <a href="<?= htmlspecialchars(Auth::url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-brand">
            <div class="sidebar-logo">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <span class="sidebar-title">ContactCore</span>
                <span class="sidebar-subtitle">Client relationship CRM</span>
            </div>
        </a>

        <nav class="sidebar-nav">

            <a href="<?= htmlspecialchars(Auth::url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/dashboard') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                Dashboard
            </a>

            <a href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/contacts') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
                Contacts
            </a>

            <a href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/clients') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                </svg>
                Clients
            </a>

            <?php if ($showSettingsNav): ?>

            <?php if ($canManageSectors): ?>
            <a href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/sectors') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                Sectors
            </a>
            <?php endif; ?>

            <?php if ($canManageTags): ?>
            <a href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/tags') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3ZM6.75 8.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/>
                </svg>
                Tags
            </a>
            <?php endif; ?>

            <?php if ($canManageCustomFields || $canManageImports || $canManageUsers): ?>
            <div class="sidebar-section-label">Settings</div>
            <?php endif; ?>

            <?php if ($canManageCustomFields): ?>
            <a href="<?= htmlspecialchars(Auth::url('/custom-fields'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/custom-fields') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                </svg>
                Custom Fields
            </a>
            <?php endif; ?>

            <?php if ($canManageImports): ?>
            <a href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/imports') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Imports
            </a>
            <?php endif; ?>

            <?php if ($canManageUsers): ?>
            <a href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>"
               class="nav-item <?= $isActive('/users') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                </svg>
                Users
            </a>
            <?php endif; ?>

            <?php endif; ?>

        </nav>
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
                <svg class="topbar-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
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
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                            </svg>
                            View Profile
                        </a>
                        <?php endif; ?>
                        <a class="profile-dropdown-item profile-dropdown-item--danger"
                           href="<?= htmlspecialchars(Auth::url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                            </svg>
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
