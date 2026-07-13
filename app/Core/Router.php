<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight HTTP router with exact and {param} route matching.
 */
final class Router
{
    /** @var array<string, array<string, callable|string>> */
    private array $routes = [];

    /**
     * @var array<string, list<array{uri: string, regex: string, names: list<string>, action: callable|string}>>
     */
    private array $dynamicRoutes = [];

    private string $basePath;

    /** @var callable|null */
    private $notFoundHandler = null;

    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $middlewareMap = [];

    /** @var callable(list<string>, callable(): mixed): mixed|null */
    private $middlewareRunner = null;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $uri, callable|string $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * @param  array<string, callable|string>  $routes
     */
    public function gets(array $routes): void
    {
        foreach ($routes as $uri => $action) {
            $this->get((string) $uri, $action);
        }
    }

    public function post(string $uri, callable|string $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * @param  array<string, array<string, list<string>>>  $map
     */
    public function setMiddlewareMap(array $map): void
    {
        $this->middlewareMap = $map;
    }

    /**
     * @param  callable(list<string>, callable(): mixed): mixed  $runner
     */
    public function setMiddlewareRunner(callable $runner): void
    {
        $this->middlewareRunner = $runner;
    }

    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = $this->normalizeUri($uri ?? $this->currentUri());

        $action = $this->routes[$method][$uri] ?? null;
        $params = [];
        $middlewareKey = $uri;

        if ($action === null) {
            $matched = $this->matchDynamic($method, $uri);

            if ($matched === null) {
                return $this->handleNotFound($uri);
            }

            $action = $matched['action'];
            $params = $matched['params'];
            $middlewareKey = $matched['uri'];
        }

        $middleware = $this->middlewareMap[$method][$middlewareKey] ?? [];
        $destination = fn (): mixed => $this->runAction($action, $params);

        if ($this->middlewareRunner !== null && $middleware !== []) {
            return ($this->middlewareRunner)($middleware, $destination);
        }

        return $destination();
    }

    /**
     * @return array<string, array<string, callable|string>>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    private function addRoute(string $method, string $uri, callable|string $action): void
    {
        $uri = $this->normalizeUri($uri);

        if (str_contains($uri, '{')) {
            $names = [];
            $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m) use (&$names): string {
                $names[] = $m[1];

                return '([^/]+)';
            }, $uri);

            $this->dynamicRoutes[$method][] = [
                'uri' => $uri,
                'regex' => '#^' . $regex . '$#',
                'names' => $names,
                'action' => $action,
            ];

            return;
        }

        $this->routes[$method][$uri] = $action;
    }

    /**
     * @return array{action: callable|string, params: array<string, string>, uri: string}|null
     */
    private function matchDynamic(string $method, string $uri): ?array
    {
        foreach ($this->dynamicRoutes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];

            foreach ($route['names'] as $index => $name) {
                $params[$name] = rawurldecode((string) ($matches[$index] ?? ''));
            }

            return [
                'action' => $route['action'],
                'params' => $params,
                'uri' => $route['uri'],
            ];
        }

        return null;
    }

    private function normalizeUri(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri === '' ? '/' : $uri;
    }

    private function currentUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath)) ?: '/';
        }

        $path = preg_replace('#/index\.php$#', '', $path) ?: '/';

        if (str_starts_with($path, '/public')) {
            $path = substr($path, strlen('/public')) ?: '/';
        }

        return $this->normalizeUri($path);
    }

    private function handleNotFound(string $uri): mixed
    {
        http_response_code(404);

        if ($this->notFoundHandler !== null) {
            return ($this->notFoundHandler)($uri);
        }

        (new View())->display('errors/404', [
            'title' => 'Page Not Found',
            'path' => $uri,
        ]);

        return null;
    }

    /**
     * @param  array<string, string>  $params
     */
    private function runAction(callable|string $action, array $params = []): mixed
    {
        if (is_callable($action) && !is_string($action)) {
            return $action(...array_values($params));
        }

        if (!is_string($action) || !str_contains($action, '@')) {
            throw new \InvalidArgumentException('Invalid route action.');
        }

        [$controllerName, $method] = explode('@', $action, 2);
        $class = str_starts_with($controllerName, 'App\\')
            ? $controllerName
            : 'App\\Controllers\\' . $controllerName;

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller [{$class}] not found.");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method [{$method}] not found on [{$class}].");
        }

        return $controller->{$method}(...array_values($params));
    }
}
