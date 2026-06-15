<?php

require_once __DIR__ . '/../app/Repositories/ApiKeyRepository.php';
require_once __DIR__ . '/../app/Services/ApiAuthenticator.php';

class FakeApiKeyRepository extends ApiKeyRepository
{
    public function findActiveByClientId(string $clientId): ?array
    {
        if ($clientId !== 'crm_test') {
            return null;
        }

        return [
            'id'          => 1,
            'client_id'   => $clientId,
            'secret_hash' => hash('sha256', 'correct-secret'),
            'is_active'   => 1,
        ];
    }
}

function assertAuthenticated(ApiAuthenticator $authenticator, string $message): void
{
    if ($authenticator->authenticate() === null) {
        throw new RuntimeException($message);
    }
}

function assertRejected(ApiAuthenticator $authenticator, string $message): void
{
    if ($authenticator->authenticate() !== null) {
        throw new RuntimeException($message);
    }
}

$authenticator = new ApiAuthenticator(new FakeApiKeyRepository());

$_SERVER = [
    'PHP_AUTH_USER' => 'crm_test',
    'PHP_AUTH_PW'   => 'correct-secret',
];
assertAuthenticated($authenticator, 'PHP_AUTH credentials were rejected.');

$_SERVER = [
    'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('crm_test:correct-secret'),
];
assertAuthenticated($authenticator, 'Authorization header credentials were rejected.');

$_SERVER = [
    'REDIRECT_HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('crm_test:correct-secret'),
];
assertAuthenticated($authenticator, 'Redirected Authorization header credentials were rejected.');

$_SERVER = [
    'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('crm_test:wrong-secret'),
];
assertRejected($authenticator, 'An invalid secret was accepted.');

$_SERVER = [];
assertRejected($authenticator, 'Missing credentials were accepted.');

echo "ApiAuthenticator tests passed.\n";
