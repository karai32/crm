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

require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/SortableTrait.php';
require_once __DIR__ . '/../app/Core/ControllerHelperTrait.php';
require_once __DIR__ . '/../app/Helpers/view_helpers.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Repositories/UserRepository.php';
require_once __DIR__ . '/../app/Repositories/DashboardRepository.php';
require_once __DIR__ . '/../app/Repositories/SectorRepository.php';
require_once __DIR__ . '/../app/Repositories/TagRepository.php';
require_once __DIR__ . '/../app/Repositories/ClientRepository.php';
require_once __DIR__ . '/../app/Repositories/ContactRepository.php';
require_once __DIR__ . '/../app/Repositories/CustomFieldRepository.php';
require_once __DIR__ . '/../app/Repositories/ExportRepository.php';
require_once __DIR__ . '/../app/Repositories/ImportRepository.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/EmailInspector.php';
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
require_once __DIR__ . '/../app/Repositories/ApiKeyRepository.php';
require_once __DIR__ . '/../app/Repositories/SettingsRepository.php';
require_once __DIR__ . '/../app/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Services/ApiAuthenticator.php';
require_once __DIR__ . '/../app/Controllers/ApiKeyController.php';
require_once __DIR__ . '/../app/Controllers/Api/ApiException.php';
require_once __DIR__ . '/../app/Controllers/Api/ApiResult.php';
require_once __DIR__ . '/../app/Controllers/Api/AbstractApiController.php';
require_once __DIR__ . '/../app/Services/Api/AbstractApiService.php';
require_once __DIR__ . '/../app/Services/Api/ContactApiService.php';
require_once __DIR__ . '/../app/Services/Api/ClientApiService.php';
require_once __DIR__ . '/../app/Services/Api/SectorApiService.php';
require_once __DIR__ . '/../app/Services/Api/TagApiService.php';
require_once __DIR__ . '/../app/Controllers/Api/ContactApiController.php';
require_once __DIR__ . '/../app/Controllers/Api/ClientApiController.php';
require_once __DIR__ . '/../app/Controllers/Api/SectorApiController.php';
require_once __DIR__ . '/../app/Controllers/Api/TagApiController.php';

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
$apiKeyController    = new ApiKeyController();
$settingsController  = new SettingsController();
$contactApiController = new ContactApiController();
$clientApiController  = new ClientApiController();
$sectorApiController  = new SectorApiController();
$tagApiController     = new TagApiController();

$router->get('/', function () {
    Auth::redirect(Auth::check() ? '/dashboard' : '/login');
});

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/login/verify', [$authController, 'showTwoFactor']);
$router->post('/login/verify', [$authController, 'verifyTwoFactor']);
$router->post('/login/resend-code', [$authController, 'resendTwoFactor']);
$router->get('/logout', [$authController, 'logout']);
$router->get('/dashboard', [$dashboardController, 'index']);
$router->get('/sectors', [$sectorController, 'index']);
$router->get('/sectors/create', [$sectorController, 'create']);
$router->post('/sectors/store', [$sectorController, 'store']);
$router->get('/sectors/edit', [$sectorController, 'edit']);
$router->post('/sectors/update', [$sectorController, 'update']);
$router->get('/sectors/delete', [$sectorController, 'delete']);
$router->get('/tags', [$tagController, 'index']);
$router->get('/tags/create', [$tagController, 'create']);
$router->post('/tags/store', [$tagController, 'store']);
$router->get('/tags/edit', [$tagController, 'edit']);
$router->post('/tags/update', [$tagController, 'update']);
$router->get('/tags/delete', [$tagController, 'delete']);
$router->get('/clients', [$clientController, 'index']);
$router->get('/clients/create', [$clientController, 'create']);
$router->post('/clients/store', [$clientController, 'store']);
$router->get('/clients/edit', [$clientController, 'edit']);
$router->post('/clients/update', [$clientController, 'update']);
$router->post('/clients/bulk-action', [$clientController, 'bulkAction']);
$router->get('/clients/show', [$clientController, 'show']);
$router->get('/clients/delete', [$clientController, 'delete']);
$router->get('/contacts', [$contactController, 'index']);
$router->get('/contacts/create', [$contactController, 'create']);
$router->post('/contacts/store', [$contactController, 'store']);
$router->get('/contacts/edit', [$contactController, 'edit']);
$router->post('/contacts/update', [$contactController, 'update']);
$router->post('/contacts/bulk-action', [$contactController, 'bulkAction']);
$router->get('/contacts/show', [$contactController, 'show']);
$router->get('/contacts/delete', [$contactController, 'delete']);
$router->get('/custom-fields', [$customFieldController, 'index']);
$router->get('/custom-fields/create', [$customFieldController, 'create']);
$router->post('/custom-fields/store', [$customFieldController, 'store']);
$router->get('/custom-fields/edit', [$customFieldController, 'edit']);
$router->post('/custom-fields/update', [$customFieldController, 'update']);
$router->get('/custom-fields/delete', [$customFieldController, 'delete']);
$router->get('/exports', [$exportController, 'index']);
$router->post('/exports/download', [$exportController, 'download']);
$router->get('/exports/template/contacts', [$exportController, 'templateContacts']);
$router->get('/exports/template/clients', [$exportController, 'templateClients']);
$router->get('/imports', [$importController, 'index']);
$router->get('/imports/upload', [$importController, 'upload']);
$router->post('/imports/upload', [$importController, 'storeUpload']);
$router->get('/imports/preview', [$importController, 'preview']);
$router->get('/imports/errors', [$importController, 'errors']);
$router->post('/imports/process', [$importController, 'process']);
$router->get('/users', [$userController, 'index']);
$router->get('/users/create', [$userController, 'create']);
$router->post('/users/store', [$userController, 'store']);
$router->get('/users/edit', [$userController, 'edit']);
$router->post('/users/update', [$userController, 'update']);
$router->get('/users/delete', [$userController, 'delete']);
$router->get('/users/purge', [$userController, 'purge']);
$router->get('/help', [$helpController, 'index']);
$router->get('/help/{topic}', [$helpController, 'show']);
$router->get('/api-keys', [$apiKeyController, 'index']);
$router->post('/api-keys/store', [$apiKeyController, 'store']);
$router->post('/api-keys/rename', [$apiKeyController, 'rename']);
$router->post('/api-keys/revoke', [$apiKeyController, 'revoke']);
$router->post('/api-keys/enable', [$apiKeyController, 'enable']);
$router->post('/api-keys/sync-scopes', [$apiKeyController, 'syncScopes']);
$router->post('/api-keys/delete', [$apiKeyController, 'delete']);
$router->get('/api-logs', [$apiKeyController, 'logs']);
$router->get('/settings', [$settingsController, 'index']);
$router->post('/settings/update', [$settingsController, 'update']);
$router->post('/api/v1/contacts', [$contactApiController, 'create']);
$router->get('/api/v1/contacts', [$contactApiController, 'index']);
$router->get('/api/v1/contacts/{id}', [$contactApiController, 'show']);
$router->patch('/api/v1/contacts/{id}', [$contactApiController, 'update']);
$router->delete('/api/v1/contacts/{id}', [$contactApiController, 'destroy']);
$router->post('/api/v1/clients', [$clientApiController, 'create']);
$router->get('/api/v1/clients', [$clientApiController, 'index']);
$router->get('/api/v1/clients/{id}', [$clientApiController, 'show']);
$router->patch('/api/v1/clients/{id}', [$clientApiController, 'update']);
$router->delete('/api/v1/clients/{id}', [$clientApiController, 'destroy']);
$router->post('/api/v1/sectors', [$sectorApiController, 'create']);
$router->get('/api/v1/sectors', [$sectorApiController, 'index']);
$router->get('/api/v1/sectors/{id}', [$sectorApiController, 'show']);
$router->patch('/api/v1/sectors/{id}', [$sectorApiController, 'update']);
$router->delete('/api/v1/sectors/{id}', [$sectorApiController, 'destroy']);
$router->post('/api/v1/tags', [$tagApiController, 'create']);
$router->get('/api/v1/tags', [$tagApiController, 'index']);
$router->get('/api/v1/tags/{id}', [$tagApiController, 'show']);
$router->patch('/api/v1/tags/{id}', [$tagApiController, 'update']);
$router->delete('/api/v1/tags/{id}', [$tagApiController, 'destroy']);
$router->get('/ajax/global-search', [$ajaxController, 'globalSearch']);
$router->get('/ajax/clients/search', [$ajaxController, 'clientsSearch']);
$router->get('/ajax/clients/field', [$ajaxController, 'clientFieldValues']);
$router->get('/ajax/tags/search', [$ajaxController, 'tagsSearch']);
$router->get('/ajax/sectors/search', [$ajaxController, 'sectorsSearch']);
$router->get('/ajax/icons/search', [$ajaxController, 'iconsSearch']);
$router->get('/ajax/custom-field/values', [$ajaxController, 'customFieldValues']);

try {
    $isApiRequest = str_contains($_SERVER['REQUEST_URI'] ?? '/', '/api/v1/');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApiRequest && !Csrf::validate($_POST['_csrf_token'] ?? null)) {
        http_response_code(419);
        echo 'Invalid form token. Please go back and try again.';
        exit;
    }

    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (PDOException $exception) {
    logApplicationError('Database error: ' . $exception->getMessage());
    http_response_code(500);
    echo 'A database error occurred. Please try again later.';
} catch (Throwable $exception) {
    logApplicationError('Application error: ' . $exception->getMessage());
    http_response_code(500);
    echo 'An application error occurred. Please try again later.';
}
