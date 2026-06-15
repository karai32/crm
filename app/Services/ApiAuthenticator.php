<?php

class ApiAuthenticator
{
    private ApiKeyRepository $apiKeys;

    public function __construct(?ApiKeyRepository $apiKeys = null)
    {
        $this->apiKeys = $apiKeys ?? new ApiKeyRepository();
    }

    public function authenticate(): ?array
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return null;
        }

        [$clientId, $secret] = $credentials;
        $apiKey = $this->apiKeys->findActiveByClientId($clientId);

        if ($apiKey === null) {
            return null;
        }

        $providedHash = hash('sha256', $secret);
        $storedHash = (string) ($apiKey['secret_hash'] ?? '');

        return $storedHash !== '' && hash_equals($storedHash, $providedHash)
            ? $apiKey
            : null;
    }

    private function credentials(): ?array
    {
        $username = $_SERVER['PHP_AUTH_USER'] ?? null;
        $password = $_SERVER['PHP_AUTH_PW'] ?? null;

        if (is_string($username) && is_string($password)) {
            return $this->validateCredentials($username, $password);
        }

        $authorization = $this->authorizationHeader();
        if ($authorization === null || !preg_match('/^Basic\s+(.+)$/i', trim($authorization), $matches)) {
            return null;
        }

        $decoded = base64_decode(trim($matches[1]), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        [$username, $password] = explode(':', $decoded, 2);

        return $this->validateCredentials($username, $password);
    }

    private function validateCredentials(string $clientId, string $secret): ?array
    {
        $clientId = trim($clientId);

        if ($clientId === '' || $secret === '') {
            return null;
        }

        return [$clientId, $secret];
    }

    private function authorizationHeader(): ?string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0 && is_string($value)) {
                    return $value;
                }
            }
        }

        return null;
    }
}
