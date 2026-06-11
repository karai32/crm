<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->getPath($uri);
        $routes = $this->routes[$method] ?? [];
        $handler = $routes[$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 - Page not found';
            return;
        }

        $handler();
    }

    private function getPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        // Allows the app to work from /CRM/public/ in XAMPP.
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        // Also support URLs like /CRM/public/index.php/db-test.
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
