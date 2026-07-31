<?php

return [
    'title' => 'Technical reference',
    'description' => 'A concise ContactCore map for daily development: entry points, directories, settings, routes, entity names, permissions, states, limits, and maintenance commands.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'reference-purpose',
            'title' => 'Purpose of the reference',
            'paragraphs' => [
                'This section is designed for quickly finding an exact name, path, or value during development and maintenance. It does not replace the detailed articles about the server, installation, code structure, database, domain model, web interface, API, import, and security. It collects their principal contracts in a compact form.',
                'Executable code and database/schema.sql remain the sources of truth. Routes are defined in public_html/index.php, permissions in Auth::permissionDefinitions(), API scopes in ApiController::SCOPES, import formats in ImportMapping and ImportFileReader, and database values in ENUMs and constraints in schema.sql. Update this reference in the same change whenever a source changes.',
                'Pay particular attention to singular and plural entity names. URLs and import/export batches use contacts and clients in plural. custom_fields, custom_field_values, and EntityTagRepository use contact and client in singular. These values are not interchangeable.',
            ],
            'examples' => [[
                'title' => 'Main sources of truth',
                'code' => <<<'CODE'
Routes                 public_html/index.php
Database contract      database/schema.sql
Permissions            app/Core/Auth.php
API authentication     app/Services/Api/ApiAuthenticator.php
API protocol/scopes    app/Controllers/ApiController.php
API resource rules     app/Services/Api/*ApiService.php
Import fields/types    app/Services/Import/ImportMapping.php
Help navigation        lang/help/{locale}/index.php
Translations           lang/{locale}.php
CODE,
            ]],
        ],
        [
            'id' => 'reference-runtime',
            'title' => 'Runtime and dependencies',
            'paragraphs' => [
                'Supported project versions are PHP 8.4 and 8.5. public_html/index.php deliberately does not load Composer autoload on an unsupported version. A MySQL-compatible database with InnoDB, utf8mb4, foreign keys, JSON, and FULLTEXT is required. The recommended server arrangement is Nginx or Apache, PHP-FPM, and a separate PHP CLI of the same version.',
                'Composer installs illuminate/database ~13.0, guzzlehttp/guzzle ^8.0, openspout/openspout ^5.8, and phpmailer/phpmailer ^7.1. Illuminate Database provides Query Builder and a shared connection without Laravel; Guzzle performs external HTTP calls; OpenSpout streams XLSX reads and writes; PHPMailer sends weekly reports and prepared 2FA emails. There is no package.json, bundler, or npm dependency: CSS and JavaScript are ready-made assets.',
                'Critical PHP capabilities are PDO MySQL, mbstring, fileinfo, dom, SimpleXML, XMLReader/XMLWriter, zip, zlib, gd, iconv, ctype, filter, hash, and OpenSSL. curl is recommended for Guzzle; without it, the library can use PHP streams. The code also uses random_bytes, password_hash/password_verify, checkdnsrr, flock, finfo, set_time_limit, and file sessions.',
            ],
            'examples' => [[
                'title' => 'Quick environment check',
                'code' => <<<'SHELL'
php8.5 --version
composer check-platform-reqs --no-dev
php8.5 -m | grep -E 'curl|dom|fileinfo|gd|mbstring|PDO|pdo_mysql|SimpleXML|xmlreader|xmlwriter|zip'
mysql --version
SHELL,
            ]],
        ],
        [
            'id' => 'reference-entry-points',
            'title' => 'Entry points',
            'paragraphs' => [
                'public_html/index.php is the only HTTP entry point. It configures file sessions and security headers, loads classes, creates controllers, registers routes, performs the global CSRF check, and passes the request to Router. Unknown physical paths are directed to this file through Nginx try_files or public_html/.htaccess.',
                'bin/weekly-report.php is the only CLI entry point. It does not load the complete HTTP bootstrap, but includes Composer, Database, MailerService, and WeeklyReportService directly. The script selects active administrators and emails them a report for the previous seven days. bin must not be published through the web server.',
                'Static CSS, JavaScript, favicon, icon catalog, and import templates are served directly from public_html/assets. They do not pass through Router, Auth, or CSRF. Configuration, user uploads, and diagnostic files must not be placed there.',
            ],
            'examples' => [[
                'title' => 'HTTP request lifecycle',
                'code' => <<<'CODE'
Web server
  → public_html/index.php
  → session_start + headers
  → require_once classes
  → instantiate controllers
  → register routes
  → global POST CSRF check
  → Router::dispatch(method, URI)
  → controller → service/repository → View or JSON
CODE,
            ]],
        ],
        [
            'id' => 'reference-directories',
            'title' => 'Directory map',
            'paragraphs' => [
                'app contains executable code by layer. Controllers accept HTTP input and select a response; Services coordinate application logic; Repositories encapsulate SQL; Core contains infrastructure; Views render HTML; Helpers provide shared view functions. API and import/export have additional subdirectories for their class families.',
                'config contains active secrets and .example.php templates. database contains the complete source schema. lang/{locale}.php contains short interface strings, and lang/help/{locale} contains long help pages and their manifest. bin contains CLI entry points. public_html is the document root. storage is created and modified by the application.',
            ],
            'examples' => [[
                'title' => 'Top-level structure',
                'code' => <<<'CODE'
app/
  Controllers/          HTML, AJAX and API controllers
  Core/                 Router, View, Database, Auth, Csrf, helpers
  Helpers/              global view helpers
  Repositories/         SQL access
  Services/             application logic and shared entity writers
  Views/                PHP templates and layouts
bin/                    CLI entry points
config/                 local configuration and secrets
database/               schema.sql for clean installations
lang/                   UI and help translations
public_html/            document root and static assets
storage/                runtime state and private files
vendor/                 Composer dependencies
CODE,
            ]],
        ],
        [
            'id' => 'reference-configuration',
            'title' => 'Configuration and environment variables',
            'paragraphs' => [
                'Active PHP configuration files are created from four .example.php files. config/app.php contains the external base_url for report links. config/database.php defines host, database, user, password, and charset. config/mail.php defines the sender and SMTP. config/gemini.php contains api_key for the AI tool.',
                'For Gemini, GEMINI_API_KEY takes priority over config/gemini.php. Other settings are read only from PHP files. There is no universal .env loader or shared Config class. Adding a setting means explicitly reading it in the relevant service and updating the example file and documentation.',
                'Secret files must not enter Git. On Linux, root ownership, www-data group, and 0640 permissions are recommended. base_url must be the external HTTPS address without a trailing slash. The database charset must remain utf8mb4 unless schema and connection are changed together.',
            ],
            'examples' => [[
                'title' => 'Configuration keys',
                'code' => <<<'CODE'
config/app.php
  base_url

config/database.php
  host, database, user, password, charset

config/mail.php
  from_email, from_name
  smtp_host, smtp_port, smtp_username, smtp_password, smtp_secure

config/gemini.php
  api_key

Environment
  GEMINI_API_KEY    overrides config/gemini.php
CODE,
            ]],
        ],
        [
            'id' => 'reference-storage',
            'title' => 'Runtime and storage files',
            'paragraphs' => [
                'storage/sessions stores PHP sessions; storage/remember persistent-login files; storage/imports source CSV/XLSX; storage/login_throttle.json failed-login counters; and storage/app.log application errors. Cron may also write storage/weekly-report-cron.log. None of these objects may be served over HTTP.',
                'The application creates some directories automatically, but installation must assign ownership and permissions beforehand. PHP-FPM and the cron CLI user must read config and write required parts of storage. The project has no shared cleanup worker: import, remember-file, and log retention is an operational policy.',
            ],
            'examples' => [[
                'title' => 'Purpose of runtime files',
                'code' => <<<'CODE'
storage/sessions/*                PHP session data
storage/remember/{64hex}          remember-me bearer records
storage/imports/*.{csv,xlsx}      uploaded source files
storage/login_throttle.json       login failure counters
storage/app.log                   application diagnostics
storage/weekly-report-cron.log    optional cron stdout/stderr
CODE,
            ]],
        ],
        [
            'id' => 'reference-web-routes',
            'title' => 'Web-interface routes',
            'paragraphs' => [
                'HTML routes use GET for reads and forms and POST for changes. Older HTML page identifiers use query parameter id rather than a path segment. Router applies policy before an action: auth = user/admin or permission with a known key. Every browser POST, including login, passes the global CSRF check.',
                'Contact and client CRUD follows index/create/store/edit/update/show/delete plus bulk-action. Sectors, tags, and custom fields have no show or bulk-action. Import and export are separate workflows. Users, API keys, API logs, and AI tools are administrator-only.',
            ],
            'examples' => [[
                'title' => 'HTML route summary',
                'code' => <<<'CODE'
Authentication
  GET  /login
  POST /login
  GET  /login/verify
  POST /login/verify
  POST /login/resend-code
  GET  /logout

Core pages
  GET  /dashboard
  GET  /contacts | /contacts/create | /contacts/edit?id= | /contacts/show?id=
  POST /contacts/store | /contacts/update | /contacts/delete | /contacts/bulk-action
  GET  /clients  | /clients/create  | /clients/edit?id=  | /clients/show?id=
  POST /clients/store  | /clients/update  | /clients/delete  | /clients/bulk-action

Classification and fields
  GET/POST /sectors/*
  GET/POST /tags/*
  GET/POST /custom-fields/*

Data exchange
  GET  /imports | /imports/errors?id=
  POST /imports/upload | /imports/process
  GET  /exports
  POST /exports/download

Administration and system
  GET/POST /users/*
  GET/POST /api-keys/*
  GET      /api-logs
  GET      /ai
  GET/POST /settings*
  POST     /lang/switch

Help
  GET /help
  GET /help/{topic}
  GET /help/technical/{section}
CODE,
            ]],
        ],
        [
            'id' => 'reference-ajax-routes',
            'title' => 'Internal AJAX routes',
            'paragraphs' => [
                'Internal endpoints have the /ajax prefix and return JSON. GET is used for search lists and does not require CSRF, but it is protected by route policy. POST modifies data or starts processing and is also checked by global CSRF before Router.',
                'A typical search accepts q and sometimes page and returns items and has_more. id values are cast to int. A new AJAX action must register a route with auth or permission and response = json, validate input, and finish through json() for the correct Content-Type and status.',
            ],
            'examples' => [[
                'title' => 'Current AJAX endpoints',
                'code' => <<<'CODE'
GET  /ajax/global-search
GET  /ajax/clients/search
GET  /ajax/clients/field
GET  /ajax/tags/search
GET  /ajax/sectors/search
GET  /ajax/icons/search
GET  /ajax/custom-field/values

POST /ajax/contacts/inspect-email-batch  admin
POST /ajax/contacts/gemini-company      admin
POST /ajax/contacts/company             admin
POST /ajax/contacts/company/skip        admin
CODE,
            ]],
        ],
        [
            'id' => 'reference-api-routes',
            'title' => 'API routes and protocol',
            'paragraphs' => [
                'The public API version is under /api/v1 and contains contacts and clients resources. Each has the same CRUD surface. Collection GET requires resource:read; POST, PATCH, and DELETE require resource:write. A write scope also satisfies the same resource’s read check. Sectors and tags are passed inside these resources without independent endpoints.',
                'Authorization uses HTTP Basic with client_id as username and secret as password. POST and PATCH bodies are JSON. POST accepts one object or an array of up to 100 and returns 207 Multi-Status with a result for every position, even for one object. PATCH accepts one non-empty object. Every response receives a 24-character hexadecimal X-Request-Id and is written to api_logs.',
                'Contacts and clients support page defaulting to 1 and per_page defaulting to 25, from 1 to 100. Detailed fields and relationship behavior are covered in API and Internal API architecture.',
            ],
            'examples' => [[
                'title' => 'Shared API CRUD matrix',
                'code' => <<<'CODE'
GET     /api/v1/{resource}       {resource}:read
GET     /api/v1/{resource}/{id}  {resource}:read
POST    /api/v1/{resource}       {resource}:write
PATCH   /api/v1/{resource}/{id}  {resource}:write
DELETE  /api/v1/{resource}/{id}  {resource}:write

resource = contacts | clients
CODE,
            ]],
        ],
        [
            'id' => 'reference-access-keys',
            'title' => 'Roles, permissions, and scopes',
            'paragraphs' => [
                'Database roles are admin and user. admin bypasses individual permissions for known keys; an unknown key is always denied. user uses user_permissions rows under a fail-closed rule. users.manage is not a configurable key: user management uses auth = admin. Reading contacts and clients requires auth = user but no separate read permission.',
                'API scopes are unrelated to user permissions and belong to api_key. There are four: read/write for contacts and clients. A permission value cannot be used as a scope or vice versa. The current set is centralized in ApiController::SCOPES and used by key creation, syncScopes, and the view.',
            ],
            'examples' => [
                ['title' => 'Web-user permissions', 'code' => <<<'CODE'
contacts.create
contacts.edit
contacts.delete
clients.create
clients.edit
clients.delete
exports.use
imports.manage
sectors.manage
tags.manage
custom_fields.manage
CODE],
                ['title' => 'API-key scopes', 'code' => <<<'CODE'
contacts:read     contacts:write
clients:read      clients:write
CODE],
            ],
        ],
        [
            'id' => 'reference-entity-names',
            'title' => 'Entity names and conventions',
            'paragraphs' => [
                'Core PHP classes use singular names: ContactController, ContactRepository, ContactApiService. Tables and URLs are generally plural: contacts, clients, sectors, tags. EntityTagRepository methods accept exactly contact or client because that key selects the relationship table.',
                'custom_fields.entity_type and custom_field_values.entity_type use contact/client. import_batches.entity_type and export_batches.entity_type use contacts/clients. API resources and scopes are also plural. An incorrect form does not always produce a clear error: code may apply a fallback, create an empty set, or choose another branch.',
                'API custom fields are sent as nested custom_fields or flat custom_fields.{slug} keys. Import does not use that syntax: the source column maps to __custom, and name and slug are created from the header.',
            ],
            'examples' => [[
                'title' => 'Singular/plural quick reference',
                'code' => <<<'CODE'
Context                              Values
PHP domain name                      Contact | Client
Database main tables                 contacts | clients
HTML/API URL                         /contacts | /clients
API scopes                           contacts:* | clients:*
import/export batch entity_type      contacts | clients
custom field entity_type             contact | client
EntityTagRepository entity argument  contact | client
CODE,
            ]],
        ],
        [
            'id' => 'reference-database-map',
            'title' => 'Database table map',
            'paragraphs' => [
                'schema.sql creates 21 tables. Users and access are separate from business data. Many-to-many relationships use contact_tags, client_tags, and client_contacts. Custom fields use definitions, select options, and a shared typed-value table. Import, export, API, and preferences have their own log tables.',
                'audit_logs exists in the schema but current code does not populate it. export_batches stores export metadata rather than the file. import_rows currently records only skipped and error. These distinctions matter during diagnostics and report construction.',
            ],
            'examples' => [[
                'title' => 'Tables by subsystem',
                'code' => <<<'CODE'
Identity
  roles, users, user_permissions, user_preferences

CRM
  sectors, tags, clients, contacts
  contact_tags, client_tags, client_contacts

Custom fields
  custom_fields, custom_field_options, custom_field_values

Import/export
  import_batches, import_rows, import_errors, export_batches

API and audit
  api_keys, api_logs, audit_logs
CODE,
            ]],
        ],
        [
            'id' => 'reference-types-statuses',
            'title' => 'Types, states, and ENUM values',
            'paragraphs' => [
                'A custom-field type determines its value column. number uses value_number, date value_date, checkbox value_bool, and other types value_text. select is also stored as text, while permitted options are in custom_field_options.',
                'Import statuses form a job state machine, while a row status describes only one row. Export has a shorter state machine. email_status reflects only address-check results, while is_corporate_email is a separate nullable Boolean domain classification.',
            ],
            'examples' => [[
                'title' => 'Allowed schema values',
                'code' => <<<'CODE'
custom_fields.entity_type
  contact | client

custom_fields.field_type
  text | textarea | number | date | email | url | select | checkbox

contacts.email_status
  valid | invalid | unknown | NULL

import_batches.file_type
  csv | xlsx
import_batches.entity_type
  contacts | clients
import_batches.status
  uploaded | previewed | processing | completed | partial | failed

import_rows.status
  pending | imported | skipped | error

export_batches.file_type
  csv | xlsx
export_batches.entity_type
  contacts | clients
export_batches.status
  processing | completed | failed
CODE,
            ]],
        ],
        [
            'id' => 'reference-limits',
            'title' => 'Limits and default values',
            'paragraphs' => [
                'Limits live in different layers and are not yet centralized in configuration. Change a number together with an assessment of memory, timeouts, interface, and database behavior. This is especially important for XLSX, complete exports, and batch API operations.',
                'User-selected interface pagination is stored under preference_key per_page, defaults to 20, and permits 5–500. It applies to lists using SortableTrait::pageParams(). AJAX catalogs normally read 20 items plus one probe for has_more.',
            ],
            'examples' => [[
                'title' => 'Current numeric limits',
                'code' => <<<'CODE'
Import upload                     20 MB
Import preview                    first 10 rows; full file counted
Import issues screen              maximum 500 issues
Import formats                    csv, xlsx

Web per_page default              20
Web per_page allowed              5..500
AJAX select page                  20 (+1 probe for has_more)
Email inspection AJAX batch       50 contacts

API POST batch                    maximum 100 items
API contacts/clients per_page     default 25; allowed 1..100
API logged request/response body  64,000 bytes each before suffix

Remember-me lifetime              30 days
Session GC lifetime               30 days
Login throttle                    5 failures / 15 min; lock 15 min
Prepared 2FA code                 10 min; resend 60 sec; 5 attempts

Export row limit                  none
XLSX                              workbook accumulated in memory
GROUP_CONCAT session limit        65,535 bytes during export
CODE,
            ]],
        ],
        [
            'id' => 'reference-http',
            'title' => 'HTTP responses and errors',
            'paragraphs' => [
                'HTML controllers normally redirect after a successful POST. An unauthenticated web user is redirected to login; insufficient permission returns 403 with short text; a missing record returns 404. Invalid global CSRF returns 419. Unhandled bootstrap errors return a generic 500, with details sent to logs.',
                'AJAX returns application/json; its guard uses 401 for a missing session and 403 for insufficient permission. The API always returns JSON and X-Request-Id. Normal successful operations use 200 and batch POST uses 207. Creation does not use 201 and deletion does not use 204.',
            ],
            'examples' => [
                ['title' => 'Main API statuses', 'code' => <<<'CODE'
200  successful list/show/update/delete
207  batch POST result, including partial success
401  missing or invalid API key
403  missing scope
404  record not found or invalid route id
409  database integrity conflict
422  invalid JSON, validation error, empty PATCH, batch > 100
500  internal error
CODE],
                ['title' => 'API error shape', 'code' => <<<'JSON'
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "...",
    "details": ["..."]
  }
}
JSON],
            ],
        ],
        [
            'id' => 'reference-core-classes',
            'title' => 'Core infrastructure classes',
            'paragraphs' => [
                'Core classes do not form a framework but establish shared conventions. Router matches methods and paths; View loads a template and layout; Database configures Illuminate Database and provides Query Builder and transactions; Auth handles sessions and permissions; Csrf creates and checks tokens; Lang loads locales; LoginThrottle limits login.',
                'IdList normalizes an array of positive unique ids. Illuminate Support builds slugs through Str::slug() with Unicode transliteration. SortableTrait validates sort/dir and calculates pages. ControllerHelperTrait handles nullable strings, id arrays, tag filters, and custom-filter values.',
            ],
            'examples' => [[
                'title' => 'Class quick reference',
                'code' => <<<'CODE'
Router                 HTTP method/path dispatch
View                   PHP view + layout rendering
Database               shared Query Builder connection and transactions
Auth                   session, remember-me, roles, permissions
Csrf                   session token and hidden field
LoginThrottle          file-backed login limiting
Lang                   locale dictionary
IdList                 positive unique integer arrays
Illuminate Support     Unicode slugs and date handling through Carbon
SortableTrait          sort, direction and pagination
ControllerHelperTrait  common request normalization
CODE,
            ]],
        ],
        [
            'id' => 'reference-commands',
            'title' => 'Development and maintenance commands',
            'paragraphs' => [
                'The project defines no Composer scripts, PHPUnit configuration, or migration system. Composer installs dependencies, database/schema.sql deploys a clean database, and php -l checks syntax. The administrator prepares changes to an existing database, verifies them on a copy, and applies them manually through an SQL client. Rerunning schema.sql deletes existing tables and data.',
                'The weekly report runs only through PHP CLI. By default, collect() covers the previous seven days and emails every active admin. Before cron, run the command manually as the same system user. npm install and asset compilation are unnecessary.',
            ],
            'examples' => [
                ['title' => 'Typical commands', 'code' => <<<'SHELL'
cd /var/www/contactcore

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev

php8.5 -l public_html/index.php
find app config bin lang public_html -name '*.php' -print0 | xargs -0 -n1 php8.5 -l

# Only for an empty database: schema.sql contains DROP TABLE
mysql -u crm_user -p crm < database/schema.sql

# There is no automatic command for an existing database:
# prepare and verify the SQL, then apply it manually through an SQL client

sudo -u www-data /usr/bin/php8.5 bin/weekly-report.php
tail -n 50 storage/app.log
SHELL],
                ['title' => 'Weekly-report cron', 'code' => <<<'CRON'
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

0 8 * * 1 www-data cd /var/www/contactcore && /usr/bin/php8.5 bin/weekly-report.php >> storage/weekly-report-cron.log 2>&1
CRON],
            ],
        ],
        [
            'id' => 'reference-change-checklist',
            'title' => 'Change-consistency checks',
            'paragraphs' => [
                'ContactCore uses explicit registration rather than automatic discovery. A new class needs require_once in public_html/index.php before a dependent object is created. A new controller must be instantiated, routed, and protected with Auth/CSRF. A new asset is passed through styles or scripts in View::render. New interface text is added to every supported locale.',
                'Changing an entity usually affects schema or a manual SQL update, Repository, Controller/Service, View, filters, import, export, API, reports, and documentation. Not every module must change, but each must be reviewed deliberately. Manually updating existing records and preserving fail-closed behavior are especially important for a new permission or scope.',
                'Before delivery, check all PHP syntax, actual HTTP paths, the access matrix, CSRF, SQL on clean and existing databases, locales, mobile display, the error log, and related batch operations. The technical reference is updated only with exact values from accepted code.',
            ],
            'examples' => [[
                'title' => 'Universal new-feature checklist',
                'code' => <<<'CODE'
[ ] database schema/update SQL and indexes
[ ] Repository queries and transaction boundary
[ ] Service/domain rules
[ ] Controller and require_once
[ ] Route and HTTP method
[ ] Auth permission/admin guard
[ ] CSRF for browser mutation
[ ] View + contextual escaping
[ ] CSS/JS assets
[ ] translations: en, es, ru
[ ] import/export impact
[ ] API fields, scopes and backward compatibility
[ ] reports, logs and retention impact
[ ] integration and access-matrix tests
[ ] user and technical documentation
CODE,
            ]],
        ],
    ],
];
