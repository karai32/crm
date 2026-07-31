<?php

return [
    'title' => 'Code structure',
    'description' => 'ContactCore architecture, request lifecycle, and rules for developing new features.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        ['id' => 'code-architecture', 'title' => 'Application architecture', 'paragraphs' => [
            'ContactCore is a server-side PHP application without a full framework. It is a modular monolith: the interface, API, business operations, and data access live in one project and share a MySQL database. Responsibilities are separated into layers, forming a simplified MVC architecture with dedicated repositories and services.',
            'The main patterns are a Front Controller in public_html/index.php, Router for handler selection, Controller for HTTP workflows, Repository for SQL, Service Layer for business processes, and View with layouts for HTML. The API uses composition: one ApiController receives a resource name and service; ApiResult represents a result and ApiException an expected error.',
            'Layer boundaries matter more than class names: a controller manages the request, a service makes business decisions, a repository handles persistence, and a view only renders prepared data. Place new code according to this rule rather than in the nearest file.',
        ], 'examples' => [[
            'title' => 'Simplified layer diagram',
            'code' => <<<'CODE'
Browser or external service
            │
            ▼
public_html/index.php  — Front Controller and bootstrap
            │
            ▼
Router                 — route and HTTP method
            │
            ▼
Controller             — access, input, workflow selection
        │           │
        ▼           ▼
     Service     Repository
        │           │
        └─────┬─────┘
              ▼
            MySQL
      (Query Builder)
              │
              ▼
View + Layout → HTML   or   ApiResult → JSON
CODE,
        ]]],
        ['id' => 'code-directories', 'title' => 'Project directories', 'paragraphs' => [
            'All executable application code is in app; the public entry point and static files are in public_html. Configuration and storage deliberately sit above the document root so they cannot be retrieved through an ordinary HTTP request.',
            'Controllers, repositories, and views are generally grouped by entity: ContactController works with ContactRepository and app/Views/contacts. Complex workflows receive their own directory under Services, as with import, export, and the API.',
        ], 'examples' => [[
            'title' => 'Purpose of the main directories',
            'code' => <<<'CODE'
app/
├── Controllers/       HTTP workflows for the interface, AJAX, and API
├── Core/              Router, Database, Auth, View, Lang, CSRF
├── Helpers/           shared view functions
├── Repositories/      queries and data retrieval through Query Builder
├── Services/          business processes and integrations
└── Views/             PHP templates, layouts, and partials

public_html/
├── index.php          the only PHP entry point
└── assets/            ready-made CSS, JavaScript, CSV/XLSX templates

bin/                   CLI and cron commands
config/                local configuration and secrets
database/              initial SQL schema
lang/                  interface translations
storage/               mutable application data and logs
vendor/                Composer dependencies
CODE,
        ]]],
        ['id' => 'code-request-lifecycle', 'title' => 'HTTP request lifecycle', 'paragraphs' => [
            'Nginx sends a virtual URL to public_html/index.php. The entry point selects the session directory, starts the session, sets security headers, loads Composer and application classes, loads the language, creates controllers, and registers routes.',
            'Before dispatch, a common CSRF check runs for every POST except /api/v1. Router then separates the path from the query string, accounts for subdirectory installation, finds an exact or parameterized route, and calls its method. Segment values such as {id} are written to $_GET, so controller methods do not receive them as arguments.',
            'Unhandled PDOException and Throwable instances are caught at the end of the entry point. The user receives a neutral 500 response, while the technical message is written to error_log and storage/app.log.',
        ], 'examples' => [[
            'title' => 'Sequence of a normal request',
            'code' => <<<'CODE'
GET /clients/show?id=42
  → Nginx: /index.php
  → session_start() and Lang::load()
  → Router::dispatch('GET', '/clients/show?id=42')
  → Router checks policy: auth = user
  → ClientController::show()
  → ClientRepository::find(42)
  → View::render('clients/show', $data)
  → app/Views/layouts/main.php
  → HTML response
CODE,
        ]]],
        ['id' => 'code-bootstrap-routing', 'title' => 'Bootstrap, class loading, and routes', 'paragraphs' => [
            'Composer automatically loads only third-party libraries. Application classes do not yet use namespaces or PSR-4: every new PHP file must be added with require_once in public_html/index.php before an object depending on it is created. Loading order matters for inheritance and type declarations.',
            'After loading files, the entry point manually creates controller instances and maps an HTTP method and path to a callable. Router supports GET, POST, PATCH, DELETE, exact routes, and {name} parameters. A route’s third argument defines auth, permission, and the response format on denial. There is no automatic controller discovery from the URL.',
            'Register specific routes before general parameterized routes. Every route must have a policy: auth = public for an intentionally open endpoint, or auth = user/admin or permission for a protected one. Router rejects routes without a policy. Use Auth::url() for internal links so subdirectory installations continue to work.',
        ], 'examples' => [[
            'title' => 'Loading and registering a new controller',
            'code' => <<<'PHP'
require_once __DIR__ . '/../app/Repositories/ProjectRepository.php';
require_once __DIR__ . '/../app/Services/ProjectService.php';
require_once __DIR__ . '/../app/Controllers/ProjectController.php';

$projectController = new ProjectController();

// First add projects.manage to Auth::permissionDefinitions().
$router->get('/projects', [$projectController, 'index'], ['auth' => 'user']);
$router->get('/projects/create', [$projectController, 'create'], [
    'permission' => 'projects.manage',
]);
$router->post('/projects/store', [$projectController, 'store'], [
    'permission' => 'projects.manage',
]);
$router->get('/projects/{id}', [$projectController, 'show'], ['auth' => 'user']);
PHP,
        ]]],
        ['id' => 'code-controllers', 'title' => 'Controllers', 'paragraphs' => [
            'A controller adapts HTTP to application code. Router verifies login, role, or permission before calling a public method. The controller reads $_GET, $_POST, or $_FILES, converts simple values to expected types, calls a repository or service, and selects an HTML, redirect, JSON, or error response.',
            'A controller may coordinate a workflow and perform simple form validation. Move complex rules, reusable operations, and transactions into a service. Do not add SQL, HTML markup, or direct configuration-file reads to a controller.',
            'A successful POST normally follows Post/Redirect/Get: save the data and call Auth::redirect(). This prevents resubmission on refresh. On error, render the form again with the entered values and a clear message.',
        ], 'examples' => [[
            'title' => 'Typical create operation',
            'code' => <<<'PHP'
public function store(): void
{
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        View::render('projects/create', [
            'title' => Lang::get('projects.create_title'),
            'error' => Lang::get('projects.name_required'),
            'name'  => $name,
        ]);
        return;
    }

    $this->projects->create($name);
    Auth::redirect('/projects');
}
PHP,
        ]]],
        ['id' => 'code-repositories', 'title' => 'Repositories and data access', 'paragraphs' => [
            'A Repository isolates data access for a domain and returns ordinary PHP arrays, numbers, or null. Queries use Database::table(); Database::rows() and Database::row() convert Illuminate Database results to the arrays expected by the application.',
            'Values are passed through Query Builder bindings. Column names and sort direction must never come directly from a request; select them from a fixed allowlist. A repository may implement a small persistence-specific rule, such as deleting an unused sector or deactivating one that is in use.',
            'Do not pass a mutable Builder outside the data layer without a reason or insert unchecked values into raw expressions. A service coordinates operations across repositories, and the transaction boundary surrounds the complete business workflow.',
        ], 'examples' => [[
            'title' => 'Safe query with a sorting allowlist',
            'code' => <<<'PHP'
public function paginate(int $page, int $perPage, string $sort): array
{
    $allowed = ['name' => 'name', 'created_at' => 'created_at'];
    $column = $allowed[$sort] ?? 'name';
    return Database::rows(
        Database::table('projects')
            ->orderBy($column)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
    );
}
PHP,
        ]]],
        ['id' => 'code-services', 'title' => 'Services and business processes', 'paragraphs' => [
            'Use the Service Layer when an action is more than one query against one table. ContactWriteService and ClientWriteService are the single application-level entry points for creating and updating contacts and clients: they normalize and validate the main record, check duplicates, and save tags, relationships, and custom fields. HTML controllers, API services, and import processors only adapt their input and convert shared WriteException errors for their transport.',
            'A service may use several repositories and other services but must not depend on HTML. Composite operations use Database::transaction(). It opens a transaction when none exists or joins the API batch or import-row transaction, so a nested service does not attempt to create another nested transaction.',
            'A small service may be created directly in a controller constructor because the project has no DI container yet. Keep dependencies in typed private properties so the object composition remains visible.',
        ], 'examples' => [[
            'title' => 'Transactional business workflow',
            'code' => <<<'PHP'
$contactId = $this->contactWriter->create(
    data: $contact,
    tagIds: $tagIds,
    clientIds: $clientIds,
    customFields: $fields,
    customValues: $values
);

// ContactWriteService saves the complete record through
// Database::transaction() and returns the id after a successful commit.
PHP,
        ]]],
        ['id' => 'code-views', 'title' => 'Views, layouts, and JavaScript', 'paragraphs' => [
            'View::render() receives a template path and data array, converts array keys to local variables with extract(EXTR_SKIP), buffers the result, and inserts it into a layout. app/Views/layouts/main.php is the default; login pages use the auth layout. Shared form sections are partial files whose names begin with an underscore.',
            'Views must receive prepared data. SQL and business decisions do not belong in templates. Escape dynamic values with e() from Illuminate Support and build internal URLs with url() from app/Helpers/view_helpers.php. t() already returns an escaped translation, and every POST form includes Csrf::field().',
            'CSS and JavaScript are not built by a bundler. A controller passes additional filenames in styles and scripts, and the layout loads them from public_html/assets. JavaScript handles interface behavior and AJAX, but the server checks access and input again: browser validation is not security.',
        ], 'examples' => [
            ['title' => 'Passing data from a controller', 'code' => <<<'PHP'
View::render('projects/index', [
    'title'    => Lang::get('projects.title'),
    'styles'   => ['settings.css'],
    'scripts'  => ['projects.js'],
    'projects' => $this->projects->paginate($page, $perPage, $sort),
]);
PHP],
            ['title' => 'Safe PHP form template', 'code' => <<<'HTML'
<form method="post" action="<?= url('/projects/store') ?>">
    <?= Csrf::field() ?>
    <input name="name" value="<?= e($name ?? '') ?>" required>
    <button type="submit"><?= t('common.save') ?></button>
</form>
HTML],
        ]],
        ['id' => 'code-auth-security', 'title' => 'Authorization and security boundaries', 'paragraphs' => [
            'Auth keeps minimal signed-in user data in the session, restores remember-login, and resolves permissions. Router centrally applies each web and AJAX route policy before calling its handler. An administrator receives every known permission; an unknown key, absent row, or loading error denies the action. A hidden menu item is only an interface feature and never replaces a policy.',
            'The CSRF token is stored in the session. The entry point centrally checks normal POST requests, so every such form must add Csrf::field(). The API is excluded because it uses HTTP Basic with client_id and secret, scopes, and its own request log.',
            'Data from $_GET, $_POST, $_FILES, JSON, and headers is always untrusted. Normalize it, validate it against allowlists, and only then pass it onward. Escape at HTML output, not when saving to the database.',
        ], 'examples' => [[
            'title' => 'Selecting an access level',
            'code' => <<<'PHP'
$router->get('/dashboard', [$dashboardController, 'index'], [
    'auth' => 'user',
]);
$router->post('/contacts/update', [$contactController, 'update'], [
    'permission' => 'contacts.edit',
]);
$router->post('/ajax/admin-task', [$ajaxController, 'adminTask'], [
    'auth' => 'admin', 'response' => 'json',
]);
PHP,
        ]]],
        ['id' => 'code-localization', 'title' => 'Localization', 'paragraphs' => [
            'The language is stored in the session and loaded through Lang::load(). ru, es, and en are supported; if a Russian or Spanish file lacks a key, Lang adds the English value as a fallback. Translations are flat arrays in lang/ru.php, lang/es.php, and lang/en.php.',
            'Use Lang::get() in PHP logic and t() in HTML. Add every new key under the same name to all language files. User-entered and database values are not translations and must be escaped separately.',
        ], 'examples' => [[
            'title' => 'Key with substitution',
            'code' => <<<'PHP'
// lang/en.php
'projects.created' => 'Project “:name” was created.',

// Controller or service
$message = Lang::get('projects.created', ['name' => $project['name']]);

// View: t() escapes the result immediately
<h1><?= t('projects.title') ?></h1>
PHP,
        ]]],
        ['id' => 'code-api-internals', 'title' => 'Internal API structure', 'paragraphs' => [
            'The API uses the same Front Controller and Router but has a separate class chain. One ApiController implements standard CRUD methods, authentication, scopes, JSON parsing, a common error format, X-Request-Id, and api_logs writes. The entry point creates two instances with ContactApiService and ClientApiService. Resource differences belong in services; micro-controller classes are not used.',
            'Every API service method returns ApiResult with a status, body, and item count. An expected business error is represented by ApiException with a status, code, and details. Unexpected exceptions are not exposed to the client but enter the server log with the request ID.',
            'AbstractApiService::batch() handles batch creation: each item gets a separate transaction and result, and the overall response uses status 207. To add an API resource, create a meaningful service and configure the shared controller instead of copying key, JSON, and logging code.',
        ], 'examples' => [
            ['title' => 'Configuring a resource controller', 'code' => <<<'PHP'
$apiControllers = [
    'contacts' => new ApiController('contacts', new ContactApiService()),
    'clients' => new ApiController('clients', new ClientApiService()),
];

// One loop registers GET/POST/PATCH/DELETE for every resource.
PHP],
            ['title' => 'Result and expected error', 'code' => <<<'PHP'
return new ApiResult(200, [
    'success' => true,
    'data' => $project,
], 1);

throw new ApiException(
    422, 'validation_error', 'Project validation failed', ['name is required']
);
PHP],
        ]],
        ['id' => 'code-feature-flow', 'title' => 'How one operation flows', 'paragraphs' => [
            'Creating a contact through the interface starts with the POST route policy checking contacts.create. ContactController extracts form fields and passes the data, tag and client ids, and custom values to ContactWriteService. The service normalizes and validates the record, classifies the email, and creates the main record and relationships in one transaction; the controller only translates WriteException and redirects.',
            'Through the API, the same operation enters the ApiController configured for contacts and then ContactApiService. The API service validates JSON, resolves external tag and client names to ids, and invokes the same ContactWriteService. Shared domain errors receive stable API codes through WriteException, the batch transaction includes automatically created catalogs, and api_logs records the result.',
            'Import uses the same write boundary: ContactImportProcessor and ClientImportProcessor map row columns and call ContactWriteService or ClientWriteService inside the row transaction. Shared validation and duplicate semantics are no longer copied into processors; ImportManager converts WriteException to error or skipped. Only the slow email DNS check is explicitly disabled for bulk processing.',
        ], 'examples' => [[
            'title' => 'Two entry points into one domain',
            'code' => <<<'CODE'
HTML form
  → ContactController
  → ContactWriteService [validation + EmailInspector + transaction]
  → redirect + HTML

JSON API
  → ApiController::handle() [contacts]
  → ContactApiService
  → ContactWriteService
  → ApiResult + api_logs
CODE,
        ]]],
        ['id' => 'code-new-feature', 'title' => 'Adding a new section', 'paragraphs' => [
            'First define the entity, user workflows, and permissions. Then prepare the database change, which is covered in the next documentation section. After the database, create a Repository, optional Service, Controller, routes, and Views. Finally add translations, a menu item, CSS/JavaScript, and server-side access checks.',
            'A simple catalog usually needs Repository + Controller + Views. An operation spanning entities needs a Service. A new external JSON resource needs a meaningful ApiService and shared ApiController configuration. Only the HTTP protocol is universal; do not mix the domain logic of different areas in one service.',
            'Before completion, test the successful path, empty and invalid data, a user without permission, a missing record, form resubmission, output escaping, and transaction rollback. For the API, also test an invalid key, insufficient scope, invalid JSON, and request-ID logging.',
        ], 'examples' => [[
            'title' => 'Minimum files for a new entity',
            'code' => <<<'CODE'
app/Repositories/ProjectRepository.php
app/Controllers/ProjectController.php
app/Views/projects/index.php
app/Views/projects/create.php
app/Views/projects/edit.php
app/Views/projects/_form.php
public_html/assets/js/projects.js        # when behavior is needed
public_html/assets/css/projects.css      # when base styles are insufficient
lang/ru.php, lang/en.php, lang/es.php
public_html/index.php                    # require_once, object, routes
CODE,
        ]]],
        ['id' => 'code-conventions', 'title' => 'Conventions and current limitations', 'paragraphs' => [
            'Application classes currently use the global namespace and dependencies are created manually. A filename should match its class purpose, one primary class belongs in one file, and properties and return values are typed. Do not change this style in one module only: moving to namespaces, PSR-4, or DI must be a coordinated refactoring.',
            'The project has no Eloquent models or automatic migration runner: data access uses Illuminate Database Query Builder, and existing databases are changed manually with SQL files from database/migrations. database/schema.sql is for first installation, not migration. There is no automated test directory yet, so changes are checked with PHP lint, interface/API scenarios, and logs; critical domain rules should gradually receive unit or integration tests.',
            'Comments should explain a reason or constraint, not repeat the code. New logic must not expose secrets or internal exceptions to users. Log unexpected errors with enough context, but never write passwords, API secrets, SMTP keys, or complete sensitive payloads to an ordinary log.',
        ], 'examples' => [[
            'title' => 'Basic checks for changed PHP files',
            'code' => <<<'SHELL'
php -l app/Controllers/ProjectController.php
php -l app/Repositories/ProjectRepository.php
php -l app/Views/projects/index.php
composer check-platform-reqs --no-dev
SHELL,
        ]]],
    ],
];
