<!doctype html>
<html lang="<?= htmlspecialchars(Lang::locale(), ENT_QUOTES, 'UTF-8') ?>">

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
    $currentLocale = Lang::locale();
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
                        <span class="sidebar-subtitle"><?= t('nav.subtitle') ?></span>
                    </div>
                </a>

                <nav class="sidebar-nav">

                    <a href="<?= htmlspecialchars(Auth::url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"
                        class="nav-item <?= $isActive('/dashboard') ?>" title="<?= t('nav.dashboard') ?>">
                        <span class="nav-icon"><i class="ph ph-house-line"></i></span>
                        <span class="nav-label"><?= t('nav.dashboard') ?></span>
                    </a>

                    <a href="<?= htmlspecialchars(Auth::url('/contacts'), ENT_QUOTES, 'UTF-8') ?>"
                        class="nav-item <?= $isActive('/contacts') ?>" title="<?= t('nav.contacts') ?>">
                        <span class="nav-icon"><i class="ph ph-address-book"></i></span>
                        <span class="nav-label"><?= t('nav.contacts') ?></span>
                    </a>

                    <a href="<?= htmlspecialchars(Auth::url('/clients'), ENT_QUOTES, 'UTF-8') ?>"
                        class="nav-item <?= $isActive('/clients') ?>" title="<?= t('nav.clients') ?>">
                        <span class="nav-icon"><i class="ph ph-buildings"></i></span>
                        <span class="nav-label"><?= t('nav.clients') ?></span>
                    </a>

                    <?php if ($showSettingsNav): ?>

                        <?php if ($canManageSectors): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/sectors'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/sectors') ?>" title="<?= t('nav.sectors') ?>">
                                <span class="nav-icon"><i class="ph ph-crosshair"></i></span>
                                <span class="nav-label"><?= t('nav.sectors') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($canManageTags): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/tags'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/tags') ?>" title="<?= t('nav.tags') ?>">
                                <span class="nav-icon"><i class="ph ph-tag"></i></span>
                                <span class="nav-label"><?= t('nav.tags') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($canManageCustomFields || $canManageImports || $canUseExports): ?>
                            <div class="sidebar-section-label"><span class="nav-label"><?= t('nav.section_settings') ?></span></div>
                        <?php endif; ?>

                        <?php if ($authUser): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/settings'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/settings') ?>" title="<?= t('nav.settings') ?>">
                                <span class="nav-icon"><i class="ph ph-gear"></i></span>
                                <span class="nav-label"><?= t('nav.settings') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($canManageCustomFields): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/custom-fields'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/custom-fields') ?>" title="<?= t('nav.custom_fields') ?>">
                                <span class="nav-icon"><i class="ph ph-sliders-horizontal"></i></span>
                                <span class="nav-label"><?= t('nav.custom_fields') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($canManageImports): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/imports'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/imports') ?>" title="<?= t('nav.imports') ?>">
                                <span class="nav-icon"><i class="ph ph-download-simple"></i></span>
                                <span class="nav-label"><?= t('nav.imports') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($canUseExports): ?>
                            <a href="<?= htmlspecialchars(Auth::url('/exports'), ENT_QUOTES, 'UTF-8') ?>"
                                class="nav-item <?= $isActive('/exports') ?>" title="<?= t('nav.exports') ?>">
                                <span class="nav-icon"><i class="ph ph-upload-simple"></i></span>
                                <span class="nav-label"><?= t('nav.exports') ?></span>
                            </a>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if ($authUser && ($canManageUsers || $canManageApiKeys)): ?>
                        <div class="sidebar-section-label"><span class="nav-label"><?= t('nav.section_admin') ?></span></div>
                    <?php endif; ?>

                    <?php if ($canManageUsers): ?>
                        <a href="<?= htmlspecialchars(Auth::url('/users'), ENT_QUOTES, 'UTF-8') ?>"
                            class="nav-item <?= $isActive('/users') ?>" title="<?= t('nav.users') ?>">
                            <span class="nav-icon"><i class="ph ph-users"></i></span>
                            <span class="nav-label"><?= t('nav.users') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canManageApiKeys): ?>
                        <a href="<?= htmlspecialchars(Auth::url('/api-keys'), ENT_QUOTES, 'UTF-8') ?>"
                            class="nav-item <?= $isActive('/api-keys') ?>" title="<?= t('nav.api_keys') ?>">
                            <span class="nav-icon"><i class="ph ph-key"></i></span>
                            <span class="nav-label"><?= t('nav.api_keys') ?></span>
                        </a>
                    <?php endif; ?>

                </nav>

                <div class="sidebar-footer">
                    <!-- Language switcher -->
                    <div class="sidebar-lang">
                        <?php foreach (['es' => 'ES', 'en' => 'EN', 'ru' => 'RU'] as $code => $label): ?>
                            <form method="post" action="<?= htmlspecialchars(Auth::url('/lang/switch'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="lang" value="<?= $code ?>">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="lang-btn <?= $currentLocale === $code ? 'lang-btn--active' : '' ?>"><?= $label ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>

                    <div class="sidebar-user-actions" id="sidebarUserActions">
                        <?php if (Auth::isAdmin()): ?>
                            <a class="sidebar-user-action" href="<?= htmlspecialchars(Auth::url('/users/edit?id=' . ($authUser['id'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                <span class="nav-icon">
                                    <i class="ph ph-user"></i>
                                </span>
                                <span class="nav-label"><?= t('nav.view_profile') ?></span>
                            </a>
                        <?php endif; ?>
                        <a class="sidebar-user-action sidebar-user-action--danger"
                            href="<?= htmlspecialchars(Auth::url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="nav-icon">
                                <i class="ph ph-sign-out"></i>
                            </span>
                            <span class="nav-label"><?= t('nav.logout') ?></span>
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
                    <button class="burger-btn" id="burgerBtn" aria-label="<?= t('common.toggle_menu') ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="topbar-search" data-global-search>
                        <i class="ph ph-magnifying-glass topbar-search-icon"></i>
                        <input type="search"
                            data-global-search-input
                            data-endpoint="<?= htmlspecialchars(Auth::url('/ajax/global-search'), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= t('topbar.search_placeholder') ?>"
                            autocomplete="off"
                            spellcheck="false">
                        <div class="topbar-search-dropdown" data-global-search-results></div>
                    </div>

                    <div class="topbar-actions">
                        <a class="topbar-icon-btn" href="<?= htmlspecialchars(Auth::url('/help'), ENT_QUOTES, 'UTF-8') ?>" title="<?= t('common.help') ?>" aria-label="<?= t('common.help') ?>">
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
                                        <?= t('nav.view_profile') ?>
                                    </a>
                                <?php endif; ?>
                                <a class="profile-dropdown-item profile-dropdown-item--danger"
                                    href="<?= htmlspecialchars(Auth::url('/logout'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="ph ph-sign-out"></i>
                                    <?= t('nav.logout') ?>
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

    <script>
        window.I18N = <?= json_encode([
            'no_results'            => Lang::get('common.no_results'),
            'edit'                  => Lang::get('common.edit'),
            'delete'                => Lang::get('common.delete'),
            'active'                => Lang::get('common.active'),
            'inactive'              => Lang::get('common.inactive'),
            'more_filters'          => Lang::get('common.more_filters'),
            'less_filters'          => Lang::get('common.less_filters'),
            'n_selected'            => Lang::get('common.n_selected'),
            'delete_selected'       => Lang::get('common.delete_selected'),
            'client'                => Lang::get('common.client'),
            'contact'               => Lang::get('common.contact'),
            'copied'                => Lang::get('common.copied'),
            'sector_delete_confirm' => Lang::get('sectors.delete_confirm'),
            'tag_delete_confirm'    => Lang::get('tags.delete_confirm_links'),
        ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="<?= htmlspecialchars(Auth::url('/assets/js/admin.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= htmlspecialchars(Auth::url('/assets/js/' . $script), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endforeach; ?>
</body>

</html>
