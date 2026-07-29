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

    public function get(string $path, callable $handler, array $policy): void
    {
        $this->add('GET', $path, $handler, $policy);
    }

    public function post(string $path, callable $handler, array $policy): void
    {
        $this->add('POST', $path, $handler, $policy);
    }

    public function patch(string $path, callable $handler, array $policy): void
    {
        $this->add('PATCH', $path, $handler, $policy);
    }

    public function delete(string $path, callable $handler, array $policy): void
    {
        $this->add('DELETE', $path, $handler, $policy);
    }

    private function add(string $method, string $path, callable $handler, array $policy): void
    {
        $route = [
            'handler' => $handler,
            'policy' => $this->normalizePolicy($policy),
        ];

        if (str_contains($path, '{')) {
            $this->patterns[$method][$path] = $route;
        } else {
            $this->routes[$method][$path] = $route;
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $path   = $this->getPath($uri);
        $routes = $this->routes[$method] ?? [];

        // Exact match
        if (isset($routes[$path])) {
            $this->invoke($routes[$path]);
            return;
        }

        // Pattern match for routes with {param} segments
        foreach ($this->patterns[$method] ?? [] as $routePath => $route) {
            $regex = $this->compilePattern($routePath);
            if (preg_match($regex, $path, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $_GET[$key] = $value;
                    }
                }
                $this->invoke($route);
                return;
            }
        }

        http_response_code(404);
        echo '404 - Page not found';
    }

    private function invoke(array $route): void
    {
        if (!$this->authorize($route['policy'])) {
            return;
        }

        ($route['handler'])();
    }

    private function authorize(array $policy): bool
    {
        $permission = $policy['permission'];
        $requiresLogin = in_array($policy['auth'], ['user', 'admin'], true) || $permission !== null;

        if ($requiresLogin && !Auth::ensureAuthenticated()) {
            return $this->deny($policy['response'], 401, 'Unauthenticated');
        }

        if ($policy['auth'] === 'admin' && !Auth::isAdmin()) {
            return $this->deny($policy['response'], 403, 'Forbidden');
        }

        if (is_callable($permission)) {
            $permission = $permission();

            if (!is_string($permission) || $permission === '') {
                return $this->deny($policy['response'], 403, 'Forbidden');
            }
        }

        if (is_string($permission) && !Auth::can($permission)) {
            return $this->deny($policy['response'], 403, 'Forbidden');
        }

        return true;
    }

    private function deny(string $response, int $status, string $message): bool
    {
        if ($response === 'json') {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            return false;
        }

        if ($status === 401) {
            Auth::redirect('/login');
        }

        http_response_code($status);
        echo 'Access denied';
        return false;
    }

    private function normalizePolicy(array $policy): array
    {
        $auth = $policy['auth'] ?? null;
        $response = $policy['response'] ?? 'html';
        $permission = $policy['permission'] ?? null;

        if (!in_array($auth, [null, 'public', 'user', 'admin'], true)) {
            throw new InvalidArgumentException('Unsupported route auth policy.');
        }
        if (!in_array($response, ['html', 'json'], true)) {
            throw new InvalidArgumentException('Unsupported route response policy.');
        }
        if ($permission !== null && !is_string($permission) && !is_callable($permission)) {
            throw new InvalidArgumentException('Route permission must be a string or callable.');
        }
        if ($auth === null && $permission === null) {
            throw new InvalidArgumentException('Every route must declare an auth or permission policy.');
        }

        return [
            'auth' => $auth,
            'response' => $response,
            'permission' => $permission,
        ];
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
