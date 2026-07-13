<?php

declare(strict_types=1);

namespace JobVisa\App\Routing;

use App\Core\Router;
use RuntimeException;

/**
 * Enterprise route registrar wrapping the existing App\Core\Router.
 *
 * Supports named groups, prefixes, and middleware tags (tags stored for
 * future middleware pipeline integration — Router dispatch remains unchanged).
 */
final class RouteRegistrar
{
    private string $prefix = '';

    /** @var list<string> */
    private array $middleware = [];

    private ?string $currentGroup = null;

    /**
     * @var array<string, array{prefix: string, middleware: list<string>, file: string}>
     */
    private array $groupMeta = [];

    /**
     * Middleware tags keyed by METHOD then URI (foundation metadata only).
     *
     * @var array<string, array<string, list<string>>>
     */
    private array $routeMiddleware = [];

    public function __construct(
        private readonly Router $router
    ) {
    }

    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Register a named route group.
     *
     * @param  callable(self): void  $callback
     * @param  array{prefix?: string, middleware?: list<string>}  $options
     */
    public function group(string $name, callable $callback, array $options = []): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->middleware;
        $previousGroup = $this->currentGroup;

        $groupPrefix = (string) ($options['prefix'] ?? '');
        $groupMiddleware = $options['middleware'] ?? [];

        $this->currentGroup = $name;
        $this->prefix = $this->joinPrefix($previousPrefix, $groupPrefix);
        $this->middleware = array_values(array_unique([...$previousMiddleware, ...$groupMiddleware]));

        $this->groupMeta[$name] = [
            'prefix' => $this->prefix,
            'middleware' => $this->middleware,
            'file' => '',
        ];

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->middleware = $previousMiddleware;
        $this->currentGroup = $previousGroup;
    }

    public function get(string $uri, callable|string $action): void
    {
        $this->register('GET', $uri, $action);
    }

    public function post(string $uri, callable|string $action): void
    {
        $this->register('POST', $uri, $action);
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

    /**
     * Load all groups defined in config/routing.php in configured order.
     */
    public function loadConfiguredRoutes(?string $configPath = null): void
    {
        $configPath ??= base_path('config/routing.php');

        if (!is_file($configPath)) {
            throw new RuntimeException('Routing configuration file is missing.');
        }

        /** @var array{groups?: array<string, array<string, mixed>>, load_order?: list<string>} $config */
        $config = require $configPath;

        $groups = $config['groups'] ?? [];
        $order = $config['load_order'] ?? array_keys($groups);

        foreach ($order as $groupName) {
            if (!isset($groups[$groupName])) {
                continue;
            }

            $this->loadGroup($groupName, $groups[$groupName]);
        }
    }

    /**
     * @param  array{file?: string, prefix?: string, middleware?: list<string>}  $definition
     */
    public function loadGroup(string $name, array $definition): void
    {
        $file = (string) ($definition['file'] ?? '');
        $path = $file !== '' && !str_contains($file, DIRECTORY_SEPARATOR) && !str_starts_with($file, '/')
            ? base_path($file)
            : ($file !== '' ? $file : '');

        if ($path === '' || !is_file($path)) {
            return;
        }

        $prefix = (string) ($definition['prefix'] ?? '');
        $middleware = $definition['middleware'] ?? [];

        $this->group($name, function (self $router) use ($path): void {
            require $path;
        }, [
            'prefix' => $prefix,
            'middleware' => $middleware,
        ]);

        $this->groupMeta[$name]['file'] = $path;
    }

    /**
     * @return array<string, array{prefix: string, middleware: list<string>, file: string}>
     */
    public function groupMeta(): array
    {
        return $this->groupMeta;
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public function routeMiddlewareMap(): array
    {
        return $this->routeMiddleware;
    }

    private function register(string $method, string $uri, callable|string $action): void
    {
        $fullUri = $this->applyPrefix($uri);

        if ($method === 'GET') {
            $this->router->get($fullUri, $action);
        } elseif ($method === 'POST') {
            $this->router->post($fullUri, $action);
        } else {
            throw new RuntimeException('Unsupported HTTP method for RouteRegistrar.');
        }

        if ($this->middleware !== []) {
            $normalized = $this->normalizeUri($fullUri);
            $this->routeMiddleware[$method][$normalized] = $this->middleware;
            $this->router->setMiddlewareMap($this->routeMiddleware);
        }
    }

    private function applyPrefix(string $uri): string
    {
        if ($this->prefix === '' || $this->prefix === '/') {
            return $uri;
        }

        if ($uri === '/' || $uri === '') {
            return $this->prefix;
        }

        return rtrim($this->prefix, '/') . '/' . ltrim($uri, '/');
    }

    private function joinPrefix(string $base, string $extra): string
    {
        if ($extra === '' || $extra === '/') {
            return $base;
        }

        if ($base === '' || $base === '/') {
            return '/' . trim($extra, '/');
        }

        return rtrim($base, '/') . '/' . trim($extra, '/');
    }

    private function normalizeUri(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        return $uri === '' ? '/' : $uri;
    }
}
