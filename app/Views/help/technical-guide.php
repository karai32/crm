<?php

$toc = [
    ['id' => 'overview',     'label' => 'Technology stack'],
    ['id' => 'structure',    'label' => 'File structure'],
    ['id' => 'lifecycle',    'label' => 'Request lifecycle'],
    ['id' => 'core',         'label' => 'Core layer'],
    ['id' => 'layers',       'label' => 'Application layers'],
    ['id' => 'database',     'label' => 'Database schema'],
    ['id' => 'security',     'label' => 'Auth & security'],
    ['id' => 'i18n',         'label' => 'Internationalization'],
    ['id' => 'api',          'label' => 'REST API layer'],
    ['id' => 'mail',         'label' => 'Email & cron jobs'],
    ['id' => 'frontend',     'label' => 'Frontend assets'],
    ['id' => 'dependencies', 'label' => 'Dependencies'],
    ['id' => 'config',       'label' => 'Config & deployment'],
];
?>

<?php require __DIR__ . '/_topic-header.php'; ?>

<!-- Layout: TOC + content -->
<div class="help-layout help-layout-mt">

    <!-- Sticky TOC -->
    <aside class="help-toc">
        <span class="help-toc-label">Contents</span>
        <nav class="help-toc-nav">
            <?php foreach ($toc as $item): ?>
            <a href="#<?= $item['id'] ?>"
               class="help-toc-item help-toc-item--slate"
               data-section="<?= $item['id'] ?>">
                <span class="help-toc-dot"></span>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="help-content">

        <div class="help-callout help-callout--slate">
            <i class="ph ph-lock-key"></i>
            <p><strong>Administrators only.</strong> This guide documents the internals of the platform:
            how it is built, how data is stored, and how it is deployed. It is not visible to
            regular users.</p>
        </div>

        <!-- ══════════════════════════════════════ OVERVIEW ══ -->
        <section class="help-card help-card-mt" id="overview">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-stack"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Platform overview &amp; technology stack</h2>
                    <p class="help-card-summary">A dependency-light custom PHP application — no external framework, ORM, or template engine.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-p">
                    ContactCore is a hand-written PHP application following an <strong>MVC-style architecture</strong>
                    (Controllers → Services → Repositories → Views) on top of a small custom core
                    (~6 classes: router, view renderer, database connector, auth, CSRF, i18n).
                    There is deliberately <strong>no framework</strong>: the entire codebase can be read
                    top to bottom, and there is no build step for either backend or frontend.
                </p>
                <div class="help-table-wrap">
                    <table class="help-table">
                        <thead>
                            <tr><th>Layer</th><th>Technology</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Language</td><td>PHP <strong>8.3+</strong> (required by the Composer dependencies; the front controller degrades gracefully on older versions)</td></tr>
                            <tr><td>Database</td><td>MySQL / MariaDB via <strong>PDO</strong>, InnoDB, <code>utf8mb4_unicode_ci</code></td></tr>
                            <tr><td>Web server</td><td>Apache-compatible; works from the domain root or a subdirectory (e.g. XAMPP <code>/CRM/public_html/</code>)</td></tr>
                            <tr><td>Templates</td><td>Plain PHP views rendered through <code>View::render()</code> with a shared layout</td></tr>
                            <tr><td>Frontend</td><td>Vanilla JavaScript (no framework, no bundler) + hand-written CSS with design tokens</td></tr>
                            <tr><td>Icons / font</td><td>Phosphor Icons 2.1.1 and the Inter font, both loaded from CDN</td></tr>
                            <tr><td>Spreadsheets</td><td>PhpSpreadsheet (XLSX import/export)</td></tr>
                            <tr><td>Email</td><td>PHPMailer over SMTP (2FA codes, weekly reports)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════ STRUCTURE ══ -->
        <section class="help-card" id="structure">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--blue">
                    <i class="ph ph-tree-structure"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Directory &amp; file structure</h2>
                    <p class="help-card-summary">Only <code>public_html/</code> is exposed to the web; everything else lives above the document root.</p>
                </div>
            </div>
            <div class="help-card-body">
<pre class="help-pre">crm/
├── app/
│   ├── Controllers/     Route handlers, one per module; Api/ holds the REST controllers
│   ├── Core/            Framework core: Router, View, Database, Auth, Csrf, Lang, traits
│   ├── Helpers/         view_helpers.php — global template helper functions
│   ├── Repositories/    All SQL lives here (prepared statements via PDO)
│   ├── Services/        Business logic: auth, 2FA, import/export pipelines, mail, API
│   └── Views/           PHP templates, grouped per module + layouts/ (main, auth)
├── bin/                 CLI scripts run by cron (weekly-report.php)
├── config/              app.php, database.php, mail.php (+ committed .example templates)
├── database/            schema.sql (full schema + seed data) and migrations/*.sql
├── lang/                UI translation files: en.php, es.php, ru.php
├── public_html/         ── WEB ROOT ──
│   ├── index.php        Front controller: bootstrap + route table + dispatch
│   └── assets/          css/ and js/ (one file per page, plus shared admin/base)
├── storage/             Runtime data (auto-created): sessions/, remember/, app.log
└── vendor/              Composer dependencies (gitignored)</pre>
                <p class="help-p">
                    Naming is 1-to-1 across layers: the <em>Clients</em> module is
                    <code>ClientController</code> + <code>ClientRepository</code> +
                    <code>app/Views/clients/*</code> + <code>assets/css/clients.css</code> +
                    <code>assets/js/clients.js</code>. When adding a module, follow the same chain and
                    register its routes and <code>require_once</code> lines in
                    <code>public_html/index.php</code>.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ LIFECYCLE ══ -->
        <section class="help-card" id="lifecycle">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--violet">
                    <i class="ph ph-path"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Request lifecycle &amp; routing</h2>
                    <p class="help-card-summary">Every request flows through the single front controller <code>public_html/index.php</code>.</p>
                </div>
            </div>
            <div class="help-card-body">
                <ol class="help-steps">
                    <li class="help-step help-step--violet"><span><strong>Session bootstrap.</strong> Sessions are stored in <code>storage/sessions</code> with a 30-day <code>gc_maxlifetime</code>, so the hosting provider's aggressive session GC cannot log users out.</span></li>
                    <li class="help-step help-step--violet"><span><strong>Autoload guard.</strong> Composer's autoloader is only loaded when PHP ≥ 8.3; otherwise a warning is logged and framework-free pages keep working.</span></li>
                    <li class="help-step help-step--violet"><span><strong>Class loading.</strong> Application classes are loaded with explicit <code>require_once</code> calls — there is no PSR-4 autoloading for <code>app/</code> code, so new files must be added to the list.</span></li>
                    <li class="help-step help-step--violet"><span><strong>i18n.</strong> <code>Lang::load($_SESSION['lang'] ?? 'es')</code> loads the UI dictionary before anything renders.</span></li>
                    <li class="help-step help-step--violet"><span><strong>Route table.</strong> All routes are registered on a <code>Router</code> instance: exact paths plus <code>{param}</code> patterns (e.g. <code>/api/v1/contacts/{id}</code>) compiled to regex, with the captured params injected into <code>$_GET</code>.</span></li>
                    <li class="help-step help-step--violet"><span><strong>Global CSRF gate.</strong> Every browser <code>POST</code>, including <code>/ajax/</code> requests, must carry a valid <code>_csrf_token</code> field or the request dies with HTTP 419. Only authenticated <code>/api/v1/</code> requests are excluded.</span></li>
                    <li class="help-step help-step--violet"><span><strong>Dispatch &amp; error handling.</strong> The dispatch is wrapped in a global try/catch: <code>PDOException</code> and other <code>Throwable</code>s are logged to <code>storage/app.log</code> (via <code>logApplicationError()</code>) and the user gets a generic 500 message — stack traces are never exposed.</span></li>
                </ol>
                <div class="help-callout help-callout--violet">
                    <i class="ph ph-info"></i>
                    <p><strong>Subdirectory support.</strong> <code>Router::getPath()</code> and <code>Auth::url()</code> strip/prepend the base path derived from <code>SCRIPT_NAME</code>, so the app runs unchanged from <code>/</code> or from something like <code>/CRM/public_html/</code>. Always build links with <code>Auth::url('/path')</code>, never with hard-coded absolute paths.</p>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════ CORE ══ -->
        <section class="help-card" id="core">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-cpu"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Core layer (<code>app/Core/</code>)</h2>
                    <p class="help-card-summary">Six small static classes plus two traits replace the framework.</p>
                </div>
            </div>
            <div class="help-card-body">
                <div class="help-table-wrap">
                    <table class="help-table">
                        <thead>
                            <tr><th>Class</th><th>Responsibility</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>Router</code></td><td>Registers <code>GET/POST/PATCH/DELETE</code> handlers; exact-match map plus regex-compiled <code>{param}</code> patterns; returns 404 for unmatched paths.</td></tr>
                            <tr><td><code>View</code></td><td><code>View::render($view, $data, $layout = 'main')</code> — <code>extract()</code>s data, buffers the template, then injects the result as <code>$content</code> into <code>app/Views/layouts/main.php</code> (or <code>auth.php</code> for login screens). <code>$styles</code> / <code>$scripts</code> arrays add per-page CSS/JS.</td></tr>
                            <tr><td><code>Database</code></td><td><code>Database::connect()</code> — lazy static-singleton PDO from <code>config/database.php</code>; <code>ERRMODE_EXCEPTION</code>, <code>FETCH_ASSOC</code>, and <code>ATTR_STRINGIFY_FETCHES = true</code>.</td></tr>
                            <tr><td><code>Auth</code></td><td>Session-based identity and authorization: <code>login()/logout()</code>, <code>check()/user()/isAdmin()</code>, <code>can()</code>, the guard methods <code>requireLogin()/requireAdmin()/requirePermission()</code>, remember-me tokens, and the base-path-aware <code>url()/redirect()</code> helpers.</td></tr>
                            <tr><td><code>Csrf</code></td><td>Per-session random token; <code>Csrf::field()</code> renders the hidden input for forms; <code>validate()</code> compares with <code>hash_equals()</code>.</td></tr>
                            <tr><td><code>Lang</code></td><td>Loads <code>lang/&lt;locale&gt;.php</code> with English fallback; exposes <code>Lang::get()</code> and the escaping shortcut <code>t()</code>.</td></tr>
                            <tr><td><code>SortableTrait</code> / <code>ControllerHelperTrait</code></td><td>Shared list-page behaviour (sorting, common controller helpers) mixed into controllers.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="help-callout help-callout--red">
                    <i class="ph ph-warning"></i>
                    <p><strong>PDO stringify gotcha.</strong> Because <code>ATTR_STRINGIFY_FETCHES</code> is enabled, every value fetched from the database is a <em>string</em> (<code>"1"</code>, not <code>1</code>). Never strict-compare DB values against int/bool literals (<code>=== 1</code>, <code>=== true</code>) — cast first or use loose comparison.</p>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════ LAYERS ══ -->
        <section class="help-card" id="layers">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--teal">
                    <i class="ph ph-stack-simple"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Application layers</h2>
                    <p class="help-card-summary">Controllers stay thin; SQL stays in repositories; multi-step logic lives in services.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-sub-label">Controllers (<code>app/Controllers/</code>)</p>
                <p class="help-p">
                    One per module (<code>ContactController</code>, <code>ClientController</code>,
                    <code>UserController</code>…). The first line of every action is an auth guard
                    (<code>Auth::requireLogin()</code>, <code>requirePermission('contacts.edit')</code>,
                    or <code>requireAdmin()</code>). They read <code>$_GET/$_POST</code>, delegate to
                    repositories/services, and finish with <code>View::render()</code> or
                    <code>Auth::redirect()</code>. <code>AjaxController</code> serves the
                    <code>/ajax/*</code> JSON endpoints (global search, autocomplete, icon search,
                    e-mail inspection); the <code>Api/</code> subfolder holds the REST controllers.
                </p>
                <p class="help-sub-label">Repositories (<code>app/Repositories/</code>)</p>
                <p class="help-p">
                    The only layer that talks to the database. Each repository wraps a module's tables
                    with prepared statements obtained from <code>Database::connect()</code>. Filtering,
                    sorting and pagination for list pages are built here.
                </p>
                <p class="help-sub-label">Services (<code>app/Services/</code>)</p>
                <p class="help-p">
                    Multi-step business logic: <code>AuthService</code> (credential check),
                    <code>TwoFactorService</code> (e-mail login codes), <code>EmailInspector</code>
                    (e-mail quality checks), the <code>Import/</code> pipeline
                    (file reader → column mapping → per-entity processors for contacts and clients),
                    the <code>Export/</code> pipeline (field selection → CSV/XLSX writer),
                    <code>MailerService</code> + <code>WeeklyReportService</code> (see the e-mail section),
                    <code>ApiAuthenticator</code> and the <code>Api/</code> services,
                    and <code>PhosphorIconCatalog</code> (icon picker data).
                </p>
                <p class="help-sub-label">Views (<code>app/Views/</code>)</p>
                <p class="help-p">
                    Plain PHP templates grouped per module. All output is escaped with
                    <code>htmlspecialchars()</code> or the <code>t()</code> helper.
                    <code>layouts/main.php</code> renders the sidebar/topbar shell and hides navigation
                    items the current user's permissions do not allow; <code>layouts/auth.php</code>
                    is the minimal shell for login/2FA screens.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ DATABASE ══ -->
        <section class="help-card" id="database">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-database"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Database schema</h2>
                    <p class="help-card-summary">21 InnoDB tables, utf8mb4, with foreign keys throughout. Source of truth: <code>database/schema.sql</code>.</p>
                </div>
            </div>
            <div class="help-card-body">
                <div class="help-table-wrap">
                    <table class="help-table">
                        <thead>
                            <tr><th>Group</th><th>Tables</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Identity</td>
                                <td><code>roles</code>, <code>users</code>, <code>user_permissions</code>, <code>user_preferences</code></td>
                                <td>Two seeded roles (<code>admin</code>, <code>user</code>); per-user permission overrides keyed by strings like <code>contacts.edit</code>; per-user prefs (e.g. rows per page).</td>
                            </tr>
                            <tr>
                                <td>CRM core</td>
                                <td><code>contacts</code>, <code>clients</code>, <code>sectors</code>, <code>tags</code></td>
                                <td>Contacts have e-mail status fields (<code>is_corporate_email</code>, <code>email_status</code>) and a FULLTEXT index on name/e-mail/phone. Clients carry address data, web-connected and active flags with date stamps, and <code>created_by/updated_by</code> user references. Sectors and tags ship with seed data.</td>
                            </tr>
                            <tr>
                                <td>Relations</td>
                                <td><code>contact_tags</code>, <code>client_tags</code>, <code>client_contacts</code></td>
                                <td>Pure many-to-many pivots; <code>client_contacts</code> adds <code>relation_label</code> and <code>is_primary</code>.</td>
                            </tr>
                            <tr>
                                <td>Custom fields</td>
                                <td><code>custom_fields</code>, <code>custom_field_options</code>, <code>custom_field_values</code></td>
                                <td>EAV model. A field belongs to <code>contact</code> or <code>client</code>, has one of 8 types, and values land in the typed column (<code>value_text/number/date/bool</code>) matching the type — one row per field + entity, enforced by a unique key. Filterable fields feed the advanced-filter panels.</td>
                            </tr>
                            <tr>
                                <td>Imports</td>
                                <td><code>import_batches</code>, <code>import_rows</code>, <code>import_errors</code></td>
                                <td>A batch stores the file, entity type (contacts/clients), the JSON column mapping and counters; every source row and every error is persisted for the history and error-report screens.</td>
                            </tr>
                            <tr>
                                <td>Exports</td>
                                <td><code>export_batches</code></td>
                                <td>History of downloads: format, entity, JSON filters and selected fields, row count.</td>
                            </tr>
                            <tr>
                                <td>API</td>
                                <td><code>api_keys</code>, <code>api_logs</code></td>
                                <td>Keys hold a SHA-256 <code>secret_hash</code> and a JSON <code>scopes</code> array. Every API request is logged with request id, status, duration and (truncated) bodies.</td>
                            </tr>
                            <tr>
                                <td>Audit</td>
                                <td><code>audit_logs</code></td>
                                <td>Generic action log with JSON old/new values, IP and user agent.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="help-p">
                    <strong>Migrations:</strong> <code>database/schema.sql</code> drops and recreates the full
                    schema (destructive — for fresh installs only). Incremental changes live as dated files in
                    <code>database/migrations/</code> (e.g. <code>2026_07_01_add_client_date_fields.sql</code>)
                    and are applied manually against existing databases. There is no migration runner.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ SECURITY ══ -->
        <section class="help-card" id="security">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--red">
                    <i class="ph ph-shield-check"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Authentication &amp; security model</h2>
                    <p class="help-card-summary">Session auth with optional e-mail 2FA, remember-me tokens, CSRF protection, and per-user permissions.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-sub-label">Login flow</p>
                <p class="help-p">
                    Passwords are hashed with <code>password_hash()</code> / verified with
                    <code>password_verify()</code> (<code>AuthService</code>). If the account has
                    two-factor enabled, <code>TwoFactorService</code> hashes a 6-digit code, e-mails it,
                    and the session is only established after <code>/login/verify</code>.
                    <code>Auth::login()</code> regenerates the session id to prevent fixation.
                </p>
                <p class="help-sub-label">Remember me</p>
                <p class="help-p">
                    A 64-hex-char random token is stored as a file in <code>storage/remember/</code>
                    (30-day expiry) and in an <code>httponly</code>, <code>SameSite=Lax</code> cookie.
                    On use the token is <strong>rotated</strong> (old file deleted, new token issued).
                    Deactivated users fail the lookup, so disabling a user also kills their remember-me access.
                </p>
                <p class="help-sub-label">Authorization</p>
                <p class="help-p">
                    Two roles: <strong>admin</strong> (bypasses every check, including
                    <code>users.manage</code> and API credentials) and <strong>user</strong>.
                    For users, <code>Auth::can()</code> consults the <code>user_permissions</code> rows;
                    a permission with no explicit row <strong>defaults to allowed</strong>, and
                    <code>users.manage</code> is hard-denied. The same checks gate controllers
                    (<code>requirePermission()</code>), sidebar links (layout), and Help Center topics
                    (<code>HelpController::canViewTopic()</code>).
                </p>
                <p class="help-sub-label">Request protection</p>
                <p class="help-p">
                    CSRF tokens on all web POSTs (global gate in the front controller, 419 on failure);
                    prepared statements everywhere (no string-built SQL with user input);
                    <code>htmlspecialchars()</code>/<code>t()</code> on all template output;
                    generic error pages with details going only to <code>storage/app.log</code>.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ I18N ══ -->
        <section class="help-card" id="i18n">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--cyan">
                    <i class="ph ph-translate"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Internationalization</h2>
                    <p class="help-card-summary">Session-scoped UI language (en / es / ru) with English fallback — plus a separate locale system for the Help Center.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-p">
                    The main UI dictionary lives in <code>lang/en.php</code>, <code>lang/es.php</code> and
                    <code>lang/ru.php</code> — flat <code>'key' =&gt; 'string'</code> arrays with
                    <code>:placeholder</code> substitution. <code>Lang::load()</code> runs at bootstrap with
                    the locale from <code>$_SESSION['lang']</code> (default <code>es</code>) and merges English
                    underneath as a fallback for missing keys. Templates call <code>t('nav.contacts')</code>,
                    which translates <em>and</em> HTML-escapes. <code>POST /lang/switch</code> stores the
                    chosen locale in the session. A small <code>window.I18N</code> object in the layout
                    bridges translated strings to JavaScript.
                </p>
                <div class="help-callout help-callout--cyan">
                    <i class="ph ph-info"></i>
                    <p><strong>Help Center content</strong> follows the global UI locale but is authored
                    inline in <code>HelpController::content()</code> (Spanish and English; other locales
                    fall back to English). The API reference and this technical guide are
                    <strong>English-only</strong>.</p>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════ API ══ -->
        <section class="help-card" id="api">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--amber">
                    <i class="ph ph-plugs-connected"></i>
                </div>
                <div class="help-card-titles">
                    <h2>REST API layer</h2>
                    <p class="help-card-summary">Versioned JSON API under <code>/api/v1</code> for contacts, clients, sectors and tags.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-p">
                    Four resources × five verbs: <code>POST</code> (create, accepts batches),
                    <code>GET</code> collection, <code>GET /{id}</code>, <code>PATCH /{id}</code>,
                    <code>DELETE /{id}</code>. Authentication is <strong>HTTP Basic</strong> with
                    <code>client_id:secret</code>; the secret is stored as a SHA-256 hash and compared with
                    <code>hash_equals()</code> (<code>ApiAuthenticator</code>). Each key carries a JSON
                    <strong>scopes</strong> list (e.g. <code>contacts:write</code>) checked per endpoint.
                </p>
                <p class="help-p">
                    Code is split in two layers: controllers extending
                    <code>AbstractApiController</code> (authentication, scope enforcement, JSON
                    request/response envelope, error mapping via <code>ApiException</code>, logging) and
                    services extending <code>AbstractApiService</code> (validation + persistence through the
                    repositories). Batch create/update requests return <strong>207 Multi-Status</strong>
                    responses with a per-item result. API routes are exempt from the CSRF gate.
                </p>
                <p class="help-p">
                    <strong>Operations:</strong> keys are managed at <code>/api-keys</code> and every request
                    is recorded in <code>api_logs</code> (request id, method, path, status, duration,
                    truncated bodies) with a viewer at <code>/api-logs</code> — both admin-only. Endpoint
                    documentation with examples lives in this Help Center under <em>API Reference</em>.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ MAIL ══ -->
        <section class="help-card" id="mail">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--sky">
                    <i class="ph ph-envelope"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Email &amp; scheduled tasks</h2>
                    <p class="help-card-summary">PHPMailer over SMTP; one cron entry point in <code>bin/</code>.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-p">
                    <code>MailerService::send()</code> wraps PHPMailer with the SMTP settings from
                    <code>config/mail.php</code> (host, port, SSL, from-address). It is used for
                    <strong>two-factor login codes</strong> and the <strong>weekly report</strong>.
                </p>
                <p class="help-p">
                    <code>WeeklyReportService</code> collects last week's activity (<code>collect()</code>)
                    and renders HTML + plain-text bodies. Two ways to send it:
                </p>
                <ol class="help-steps">
                    <li class="help-step help-step--sky"><span><strong>Cron:</strong> <code>bin/weekly-report.php</code> e-mails all active administrators. Schedule it weekly, e.g. <code>0 8 * * 1 php /path/to/crm/bin/weekly-report.php</code>. Failures are logged to <code>storage/app.log</code>.</span></li>
                    <li class="help-step help-step--sky"><span><strong>Manual:</strong> the <em>Settings</em> page (<code>POST /settings/send-report</code>) lets an administrator send the current week's report to themselves on demand.</span></li>
                </ol>
            </div>
        </section>

        <!-- ══════════════════════════════════════ FRONTEND ══ -->
        <section class="help-card" id="frontend">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--indigo">
                    <i class="ph ph-paint-brush-broad"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Frontend assets</h2>
                    <p class="help-card-summary">No framework, no build step — plain per-page CSS and JS files.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-p">
                    <strong>CSS:</strong> <code>base.css</code> contains the app shell and shared components
                    (design tokens as CSS variables, reset, shared components) load on every page; each page
                    adds its own file (e.g. <code>contacts.css</code>, <code>help.css</code>) through the
                    <code>$styles</code> array passed to <code>View::render()</code>.
                </p>
                <p class="help-p">
                    <strong>JavaScript:</strong> <code>admin.js</code> powers the shared shell (sidebar
                    toggle, global search, profile dropdown); page-specific behaviour ships in its own file
                    (e.g. <code>imports-preview.js</code>, <code>color-picker.js</code>) via the
                    <code>$scripts</code> array. Dynamic UI talks to the <code>/ajax/*</code> JSON endpoints.
                </p>
                <div class="help-callout help-callout--amber">
                    <i class="ph ph-cloud-warning"></i>
                    <p><strong>CDN dependencies:</strong> Phosphor Icons 2.1.1 (regular + fill, jsDelivr) and
                    the Inter font (Google Fonts) load from CDNs — icons and typography degrade if the
                    server's users have no internet access to those hosts.</p>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════ DEPENDENCIES ══ -->
        <section class="help-card" id="dependencies">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--slate">
                    <i class="ph ph-package"></i>
                </div>
                <div class="help-card-titles">
                    <h2>External dependencies</h2>
                    <p class="help-card-summary">Exactly two Composer packages; everything else is hand-written.</p>
                </div>
            </div>
            <div class="help-card-body">
                <div class="help-table-wrap">
                    <table class="help-table">
                        <thead>
                            <tr><th>Package</th><th>Version</th><th>Used for</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>phpoffice/phpspreadsheet</code></td><td><code>^5.8</code></td><td>Reading XLSX files during import and writing XLSX during export (CSV is handled natively). This package is the reason for the PHP 8.3+ requirement.</td></tr>
                            <tr><td><code>phpmailer/phpmailer</code></td><td><code>^7.1</code></td><td>SMTP delivery of 2FA codes and weekly reports.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="help-p">
                    Install with <code>composer install</code>; exact versions are pinned in
                    <code>composer.lock</code>. Frontend CDN assets (Phosphor Icons, Inter) are listed in the
                    previous section. There are no other runtime dependencies — no framework, ORM, template
                    engine, or JS packages.
                </p>
            </div>
        </section>

        <!-- ══════════════════════════════════════ CONFIG ══ -->
        <section class="help-card" id="config">
            <div class="help-card-head">
                <div class="help-card-icon help-card-icon--green">
                    <i class="ph ph-gear"></i>
                </div>
                <div class="help-card-titles">
                    <h2>Configuration, storage &amp; deployment</h2>
                    <p class="help-card-summary">Three PHP config files, a writable <code>storage/</code> directory, and a document root pointed at <code>public_html/</code>.</p>
                </div>
            </div>
            <div class="help-card-body">
                <p class="help-sub-label">Configuration files (<code>config/</code>)</p>
                <div class="help-table-wrap">
                    <table class="help-table">
                        <thead>
                            <tr><th>File</th><th>Contents</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>app.php</code></td><td><code>base_url</code> of the installation (used in e-mails/links).</td></tr>
                            <tr><td><code>database.php</code></td><td>PDO credentials: host, database, user, password, charset.</td></tr>
                            <tr><td><code>mail.php</code></td><td>SMTP host/port/encryption, credentials, from-address and from-name.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="help-p">
                    The real files are <strong>gitignored</strong>; committed <code>*.example.php</code>
                    templates document the expected keys. To configure a new environment, copy each example
                    file and fill in real values.
                </p>
                <p class="help-sub-label">Runtime storage (<code>storage/</code>)</p>
                <p class="help-p">
                    Created automatically at runtime and must be writable by PHP:
                    <code>sessions/</code> (PHP session files), <code>remember/</code> (remember-me token
                    files), and <code>app.log</code> (application error log — the first place to look when
                    something returns a 500). It sits outside the web root and its contents are gitignored.
                </p>
                <p class="help-sub-label">Deployment checklist</p>
                <ol class="help-steps">
                    <li class="help-step help-step--green"><span>PHP <strong>8.3+</strong> with the PDO MySQL extension; MySQL or MariaDB.</span></li>
                    <li class="help-step help-step--green"><span>Point the document root (or a subdirectory) at <code>public_html/</code> — <code>app/</code>, <code>config/</code> and <code>storage/</code> must not be web-accessible.</span></li>
                    <li class="help-step help-step--green"><span>Run <code>composer install</code>.</span></li>
                    <li class="help-step help-step--green"><span>Create the three config files from their <code>.example</code> templates.</span></li>
                    <li class="help-step help-step--green"><span>Fresh install: import <code>database/schema.sql</code>. Existing install: apply any new files from <code>database/migrations/</code>.</span></li>
                    <li class="help-step help-step--green"><span>Ensure <code>storage/</code> is writable by the PHP user.</span></li>
                    <li class="help-step help-step--green"><span>Add the weekly-report cron entry (see <em>Email &amp; scheduled tasks</em>).</span></li>
                </ol>
            </div>
        </section>

    </div>
</div>
