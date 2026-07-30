<?php

// Store sessions in a project-specific directory so the system GC on shared
// hosting (which runs with its own short gc_maxlifetime) cannot delete them.
$_sessionPath = dirname(__DIR__) . '/storage/sessions';
if (is_dir($_sessionPath) || @mkdir($_sessionPath, 0700, true)) {
    session_save_path($_sessionPath);
}
ini_set('session.gc_maxlifetime', 30 * 24 * 3600);
session_start();
unset($_sessionPath);

// Baseline security headers. script-src/style-src need 'unsafe-inline' because
// the views still rely on inline onclick="" handlers and style="" attributes.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline'; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
    . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'self';");

function logApplicationError(string $message): void
{
    error_log($message);

    $logPath = dirname(__DIR__) . '/storage/app.log';
    $entry = '[' . date('c') . '] ' . $message . PHP_EOL;

    @file_put_contents($logPath, $entry, FILE_APPEND);
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoloadPath) && PHP_VERSION_ID >= 80300) {
    try {
        require_once $autoloadPath;
    } catch (Throwable $exception) {
        error_log('Composer autoload unavailable: ' . $exception->getMessage());
    }
} elseif (file_exists($autoloadPath)) {
    error_log('Composer autoload skipped: PHP 8.3 or newer is required for installed dependencies. Current PHP: ' . PHP_VERSION);
}

require_once __DIR__ . '/../app/Core/Lang.php';
Lang::load($_SESSION['lang'] ?? 'es');

require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/IdList.php';
require_once __DIR__ . '/../app/Core/SortableTrait.php';
require_once __DIR__ . '/../app/Core/ControllerHelperTrait.php';
require_once __DIR__ . '/../app/Helpers/view_helpers.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Core/LoginThrottle.php';
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Repositories/DashboardRepository.php';
require_once __DIR__ . '/../app/Repositories/SectorRepository.php';
require_once __DIR__ . '/../app/Repositories/TagRepository.php';
require_once __DIR__ . '/../app/Repositories/EntityTagRepository.php';
require_once __DIR__ . '/../app/Repositories/ClientRepository.php';
require_once __DIR__ . '/../app/Repositories/ContactRepository.php';
require_once __DIR__ . '/../app/Repositories/CustomFieldRepository.php';
require_once __DIR__ . '/../app/Repositories/ExportRepository.php';
require_once __DIR__ . '/../app/Repositories/AiRepository.php';
require_once __DIR__ . '/../app/Repositories/ImportRepository.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/WriteException.php';
require_once __DIR__ . '/../app/Services/EmailInspector.php';
require_once __DIR__ . '/../app/Services/ContactWriteService.php';
require_once __DIR__ . '/../app/Services/ClientWriteService.php';
require_once __DIR__ . '/../app/Services/PhosphorIconCatalog.php';
require_once __DIR__ . '/../app/Services/ExportService.php';
require_once __DIR__ . '/../app/Services/Export/ExportWriter.php';
require_once __DIR__ . '/../app/Services/Export/ExportManager.php';
require_once __DIR__ . '/../app/Services/Import/ImportFileReader.php';
require_once __DIR__ . '/../app/Services/Import/ImportMapping.php';
require_once __DIR__ . '/../app/Services/Import/ImportRowException.php';
require_once __DIR__ . '/../app/Services/Import/AbstractImportProcessor.php';
require_once __DIR__ . '/../app/Services/Import/ContactImportProcessor.php';
require_once __DIR__ . '/../app/Services/Import/ClientImportProcessor.php';
require_once __DIR__ . '/../app/Services/Import/ImportManager.php';
require_once __DIR__ . '/../app/Services/MailerService.php';
require_once __DIR__ . '/../app/Services/WeeklyReportService.php';
require_once __DIR__ . '/../app/Services/TwoFactorService.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/SectorController.php';
require_once __DIR__ . '/../app/Controllers/TagController.php';
require_once __DIR__ . '/../app/Controllers/ClientController.php';
require_once __DIR__ . '/../app/Controllers/ContactController.php';
require_once __DIR__ . '/../app/Controllers/CustomFieldController.php';
require_once __DIR__ . '/../app/Controllers/ExportController.php';
require_once __DIR__ . '/../app/Controllers/ImportController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/AjaxController.php';
require_once __DIR__ . '/../app/Controllers/HelpController.php';
require_once __DIR__ . '/../app/Controllers/AiController.php';
require_once __DIR__ . '/../app/Repositories/ApiKeyRepository.php';
require_once __DIR__ . '/../app/Repositories/SettingsRepository.php';
require_once __DIR__ . '/../app/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Services/Api/ApiAuthenticator.php';
require_once __DIR__ . '/../app/Controllers/ApiKeyController.php';
require_once __DIR__ . '/../app/Controllers/Api/ApiController.php';
require_once __DIR__ . '/../app/Services/Api/AbstractApiService.php';
require_once __DIR__ . '/../app/Services/Api/ContactApiService.php';
require_once __DIR__ . '/../app/Services/Api/ClientApiService.php';

$router = new Router();
$authController = new AuthController();
$dashboardController = new DashboardController();
$sectorController = new SectorController();
$tagController = new TagController();
$clientController = new ClientController();
$contactController = new ContactController();
$customFieldController = new CustomFieldController();
$exportController = new ExportController();
$importController = new ImportController();
$userController = new UserController();
$ajaxController   = new AjaxController();
$helpController   = new HelpController();
$aiController     = new AiController();
$apiKeyController    = new ApiKeyController();
$settingsController  = new SettingsController();
$apiControllers = [
    'contacts' => new ApiController('contacts', new ContactApiService()),
    'clients' => new ApiController('clients', new ClientApiService()),
];

$publicPolicy = ['auth' => 'public'];
$apiPolicy = ['auth' => 'public', 'response' => 'json'];
$authenticatedPolicy = ['auth' => 'user'];
$adminPolicy = ['auth' => 'admin'];
$jsonAuthenticatedPolicy = ['auth' => 'user', 'response' => 'json'];
$jsonAdminPolicy = ['auth' => 'admin', 'response' => 'json'];
$permissionPolicy = static fn (string $permission, string $response = 'html'): array => [
    'permission' => $permission,
    'response' => $response,
];

$router->get('/', function () {
    Auth::redirect(Auth::check() ? '/dashboard' : '/login');
}, $publicPolicy);

$router->get('/login', [$authController, 'showLogin'], $publicPolicy);
$router->post('/login', [$authController, 'login'], $publicPolicy);
$router->get('/login/verify', [$authController, 'showTwoFactor'], $publicPolicy);
$router->post('/login/verify', [$authController, 'verifyTwoFactor'], $publicPolicy);
$router->post('/login/resend-code', [$authController, 'resendTwoFactor'], $publicPolicy);
$router->get('/logout', [$authController, 'logout'], $authenticatedPolicy);
$router->get('/dashboard', [$dashboardController, 'index'], $authenticatedPolicy);
$router->get('/sectors', [$sectorController, 'index'], $permissionPolicy('sectors.manage'));
$router->get('/sectors/create', [$sectorController, 'create'], $permissionPolicy('sectors.manage'));
$router->post('/sectors/store', [$sectorController, 'store'], $permissionPolicy('sectors.manage'));
$router->get('/sectors/edit', [$sectorController, 'edit'], $permissionPolicy('sectors.manage'));
$router->post('/sectors/update', [$sectorController, 'update'], $permissionPolicy('sectors.manage'));
$router->post('/sectors/delete', [$sectorController, 'delete'], $permissionPolicy('sectors.manage'));
$router->get('/tags', [$tagController, 'index'], $permissionPolicy('tags.manage'));
$router->get('/tags/create', [$tagController, 'create'], $permissionPolicy('tags.manage'));
$router->post('/tags/store', [$tagController, 'store'], $permissionPolicy('tags.manage'));
$router->get('/tags/edit', [$tagController, 'edit'], $permissionPolicy('tags.manage'));
$router->post('/tags/update', [$tagController, 'update'], $permissionPolicy('tags.manage'));
$router->post('/tags/delete', [$tagController, 'delete'], $permissionPolicy('tags.manage'));
$router->get('/clients', [$clientController, 'index'], $authenticatedPolicy);
$router->get('/clients/create', [$clientController, 'create'], $permissionPolicy('clients.create'));
$router->post('/clients/store', [$clientController, 'store'], $permissionPolicy('clients.create'));
$router->get('/clients/edit', [$clientController, 'edit'], $permissionPolicy('clients.edit'));
$router->post('/clients/update', [$clientController, 'update'], $permissionPolicy('clients.edit'));
$router->post('/clients/bulk-action', [$clientController, 'bulkAction'], [
    'permission' => static fn (): string => ($_POST['bulk_action'] ?? '') === 'delete'
        ? 'clients.delete'
        : 'clients.edit',
]);
$router->get('/clients/show', [$clientController, 'show'], $authenticatedPolicy);
$router->post('/clients/delete', [$clientController, 'delete'], $permissionPolicy('clients.delete'));
$router->get('/contacts', [$contactController, 'index'], $authenticatedPolicy);
$router->get('/contacts/create', [$contactController, 'create'], $permissionPolicy('contacts.create'));
$router->post('/contacts/store', [$contactController, 'store'], $permissionPolicy('contacts.create'));
$router->get('/contacts/edit', [$contactController, 'edit'], $permissionPolicy('contacts.edit'));
$router->post('/contacts/update', [$contactController, 'update'], $permissionPolicy('contacts.edit'));
$router->post('/contacts/bulk-action', [$contactController, 'bulkAction'], [
    'permission' => static fn (): string => ($_POST['bulk_action'] ?? '') === 'delete'
        ? 'contacts.delete'
        : 'contacts.edit',
]);
$router->get('/contacts/show', [$contactController, 'show'], $authenticatedPolicy);
$router->post('/contacts/delete', [$contactController, 'delete'], $permissionPolicy('contacts.delete'));
$router->get('/custom-fields', [$customFieldController, 'index'], $permissionPolicy('custom_fields.manage'));
$router->get('/custom-fields/create', [$customFieldController, 'create'], $permissionPolicy('custom_fields.manage'));
$router->post('/custom-fields/store', [$customFieldController, 'store'], $permissionPolicy('custom_fields.manage'));
$router->get('/custom-fields/edit', [$customFieldController, 'edit'], $permissionPolicy('custom_fields.manage'));
$router->post('/custom-fields/update', [$customFieldController, 'update'], $permissionPolicy('custom_fields.manage'));
$router->post('/custom-fields/delete', [$customFieldController, 'delete'], $permissionPolicy('custom_fields.manage'));
$router->get('/exports', [$exportController, 'index'], $permissionPolicy('exports.use'));
$router->post('/exports/download', [$exportController, 'download'], $permissionPolicy('exports.use'));
$router->get('/imports', [$importController, 'index'], $permissionPolicy('imports.manage'));
$router->post('/imports/upload', [$importController, 'storeUpload'], $permissionPolicy('imports.manage'));
$router->get('/imports/errors', [$importController, 'errors'], $permissionPolicy('imports.manage'));
$router->post('/imports/process', [$importController, 'process'], $permissionPolicy('imports.manage'));
$router->get('/users', [$userController, 'index'], $adminPolicy);
$router->get('/users/create', [$userController, 'create'], $adminPolicy);
$router->post('/users/store', [$userController, 'store'], $adminPolicy);
$router->get('/users/edit', [$userController, 'edit'], $adminPolicy);
$router->post('/users/update', [$userController, 'update'], $adminPolicy);
$router->post('/users/delete', [$userController, 'delete'], $adminPolicy);
$router->post('/users/purge', [$userController, 'purge'], $adminPolicy);
$router->get('/help', [$helpController, 'index'], $authenticatedPolicy);
$router->get('/help/technical/{section}', [$helpController, 'technicalPage'], $authenticatedPolicy);
$router->get('/help/{topic}', [$helpController, 'show'], $authenticatedPolicy);
$router->get('/api-keys', [$apiKeyController, 'index'], $adminPolicy);
$router->post('/api-keys/store', [$apiKeyController, 'store'], $adminPolicy);
$router->post('/api-keys/rename', [$apiKeyController, 'rename'], $adminPolicy);
$router->post('/api-keys/revoke', [$apiKeyController, 'revoke'], $adminPolicy);
$router->post('/api-keys/enable', [$apiKeyController, 'enable'], $adminPolicy);
$router->post('/api-keys/sync-scopes', [$apiKeyController, 'syncScopes'], $adminPolicy);
$router->post('/api-keys/delete', [$apiKeyController, 'delete'], $adminPolicy);
$router->get('/api-logs', [$apiKeyController, 'logs'], $adminPolicy);
$router->get('/ai', [$aiController, 'index'], $adminPolicy);
$router->get('/settings', [$settingsController, 'index'], $authenticatedPolicy);
$router->post('/settings/update', [$settingsController, 'update'], $authenticatedPolicy);
$router->post('/settings/send-report', [$settingsController, 'sendReport'], $adminPolicy);
$router->post('/lang/switch', function () {
    $allowed = ['en', 'es', 'ru'];
    $lang    = $_POST['lang'] ?? 'es';
    $_SESSION['lang'] = in_array($lang, $allowed, true) ? $lang : 'es';
    Auth::redirect($_POST['redirect'] ?? '/');
}, $publicPolicy);
// Session policy is public; /api/v1 authenticates client_id, secret and scopes in its own API layer.
foreach ($apiControllers as $resource => $controller) {
    $collectionPath = '/api/v1/' . $resource;
    $itemPath = $collectionPath . '/{id}';

    $router->get($collectionPath, [$controller, 'index'], $apiPolicy);
    $router->get($itemPath, [$controller, 'show'], $apiPolicy);
    $router->post($collectionPath, [$controller, 'create'], $apiPolicy);
    $router->patch($itemPath, [$controller, 'update'], $apiPolicy);
    $router->delete($itemPath, [$controller, 'destroy'], $apiPolicy);
}
$router->get('/ajax/global-search', [$ajaxController, 'globalSearch'], $jsonAuthenticatedPolicy);
$router->get('/ajax/clients/search', [$ajaxController, 'clientsSearch'], $jsonAuthenticatedPolicy);
$router->get('/ajax/clients/field', [$ajaxController, 'clientFieldValues'], $jsonAuthenticatedPolicy);
$router->get('/ajax/tags/search', [$ajaxController, 'tagsSearch'], $jsonAuthenticatedPolicy);
$router->get('/ajax/sectors/search', [$ajaxController, 'sectorsSearch'], $permissionPolicy('sectors.manage', 'json'));
$router->get('/ajax/icons/search', [$ajaxController, 'iconsSearch'], $permissionPolicy('sectors.manage', 'json'));
$router->get('/ajax/custom-field/values', [$ajaxController, 'customFieldValues'], $jsonAuthenticatedPolicy);
$router->post('/ajax/contacts/inspect-email-batch', [$ajaxController, 'inspectEmailBatch'], $jsonAdminPolicy);
$router->post('/ajax/contacts/gemini-company', [$ajaxController, 'geminiCompany'], $jsonAdminPolicy);
$router->post('/ajax/contacts/company', [$ajaxController, 'saveContactCompany'], $jsonAdminPolicy);
$router->post('/ajax/contacts/company/skip', [$ajaxController, 'skipContactCompany'], $jsonAdminPolicy);

try {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $isApiRequest = str_contains($requestPath, '/api/v1/');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApiRequest && !Csrf::validate($_POST['_csrf_token'] ?? null)) {
        http_response_code(419);
        echo 'Invalid form token. Please go back and try again.';
        exit;
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (PDOException $exception) {
    logApplicationError('Database error: ' . $exception->getMessage());
    if (Database::isIntegrityViolation($exception)) {
        http_response_code(409);
        echo 'The requested change conflicts with existing or related data.';
    } else {
        http_response_code(500);
        echo 'A database error occurred. Please try again later.';
    }
} catch (Throwable $exception) {
    logApplicationError('Application error: ' . $exception->getMessage());
    http_response_code(500);
    echo 'An application error occurred. Please try again later.';
}
