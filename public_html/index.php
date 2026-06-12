<?php

session_start();

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
require_once __DIR__ . '/../app/Services/ExportService.php';
require_once __DIR__ . '/../app/Services/ImportService.php';
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
require_once __DIR__ . '/../app/Controllers/ApiKeyController.php';
require_once __DIR__ . '/../app/Controllers/ApiV1Controller.php';

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
$apiKeyController = new ApiKeyController();
$apiV1Controller  = new ApiV1Controller();

$router->get('/', function () {
    Auth::redirect(Auth::check() ? '/dashboard' : '/login');
});

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/login/verify', [$authController, 'showTwoFactor']);
$router->post('/login/verify', [$authController, 'verifyTwoFactor']);
$router->post('/login/resend-code', [$authController, 'resendTwoFactor']);
$router->get('/logout', [$authController, 'logout']);
$router->get('/create-admin', [$authController, 'createAdmin']);
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
$router->post('/clients/bulk-tags', [$clientController, 'bulkTags']);
$router->post('/clients/bulk-action', [$clientController, 'bulkAction']);
$router->get('/clients/show', [$clientController, 'show']);
$router->get('/clients/delete', [$clientController, 'delete']);
$router->get('/contacts', [$contactController, 'index']);
$router->get('/contacts/create', [$contactController, 'create']);
$router->post('/contacts/store', [$contactController, 'store']);
$router->get('/contacts/edit', [$contactController, 'edit']);
$router->post('/contacts/update', [$contactController, 'update']);
$router->post('/contacts/bulk-tags', [$contactController, 'bulkTags']);
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
$router->get('/exports/contacts', [$exportController, 'contactsForm']);
$router->post('/contacts/export-csv', [$exportController, 'contactsCsv']);
$router->get('/exports/contacts-xlsx', [$exportController, 'contactsXlsxForm']);
$router->post('/exports/contacts-xlsx', [$exportController, 'contactsXlsx']);
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
$router->get('/help', [$helpController, 'index']);
$router->get('/api-keys', [$apiKeyController, 'index']);
$router->post('/api-keys/store', [$apiKeyController, 'store']);
$router->get('/api-keys/revoke', [$apiKeyController, 'revoke']);
$router->get('/api-keys/delete', [$apiKeyController, 'delete']);
$router->post('/api/v1/contacts', [$apiV1Controller, 'contacts']);
$router->get('/ajax/global-search', [$ajaxController, 'globalSearch']);
$router->get('/ajax/clients/search', [$ajaxController, 'clientsSearch']);
$router->get('/ajax/tags/search', [$ajaxController, 'tagsSearch']);
$router->get('/ajax/sectors/search', [$ajaxController, 'sectorsSearch']);

$router->get('/db-test', function () {
    $message = 'Database connection is working';
    $success = true;

    try {
        Database::connect();
    } catch (Throwable $exception) {
        // Keep the user-facing error simple and safe.
        $message = 'Could not connect to the database. Check the connection settings.';
        $success = false;
    }

    View::render('dashboard/db-test', [
        'title' => 'Database test',
        'message' => $message,
        'success' => $success,
    ]);
});

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
