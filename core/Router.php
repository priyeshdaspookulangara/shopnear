<?php
namespace Shopnear\Core;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $pathPattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pathPattern,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        // Detect language prefix e.g. /ml/ or /hi/ or /en/
        $lang = 'en';
        if (preg_match('#^/(en|ml|hi)(/.*)?$#', $path, $matches)) {
            $lang = $matches[1];
            $path = $matches[2] ?? '/';
            $path = rtrim($path, '/') ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }

            $regex = $this->convertPatternToRegex($route['pattern']);
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // Remove full match
                call_user_func_array($route['handler'], array_merge([$lang], $matches));
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => '404 Not Found', 'path' => $path]);
    }

    private function convertPatternToRegex(string $pattern): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
