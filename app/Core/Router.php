<?php

class Router
{
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    private array $patterns = [
        'GET'    => [],
        'POST'   => [],
        'PATCH'  => [],
        'DELETE' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        if (str_contains($path, '{')) {
            $this->patterns['GET'][$path] = $handler;
        } else {
            $this->routes['GET'][$path] = $handler;
        }
    }

    public function post(string $path, callable $handler): void
    {
        if (str_contains($path, '{')) {
            $this->patterns['POST'][$path] = $handler;
        } else {
            $this->routes['POST'][$path] = $handler;
        }
    }

    public function patch(string $path, callable $handler): void
    {
        if (str_contains($path, '{')) {
            $this->patterns['PATCH'][$path] = $handler;
        } else {
            $this->routes['PATCH'][$path] = $handler;
        }
    }

    public function delete(string $path, callable $handler): void
    {
        if (str_contains($path, '{')) {
            $this->patterns['DELETE'][$path] = $handler;
        } else {
            $this->routes['DELETE'][$path] = $handler;
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $path   = $this->getPath($uri);
        $routes = $this->routes[$method] ?? [];

        // Exact match
        if (isset($routes[$path])) {
            ($routes[$path])();
            return;
        }

        // Pattern match for routes with {param} segments
        foreach ($this->patterns[$method] ?? [] as $routePath => $handler) {
            $regex = $this->compilePattern($routePath);
            if (preg_match($regex, $path, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $_GET[$key] = $value;
                    }
                }
                $handler();
                return;
            }
        }

        http_response_code(404);
        echo '404 - Page not found';
    }

    private function compilePattern(string $path): string
    {
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    private function getPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        // Allows the app to work from /CRM/public/ in XAMPP.
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        // Also support URLs that include index.php before the route path.
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }
}
