<?php

declare(strict_types=1);

namespace App\Core;

use JobVisa\App\Providers\RouteServiceProvider;

/**
 * Application kernel.
 *
 * Boots routing and dispatches the incoming HTTP request.
 */
final class App
{
    private string $basePath;

    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');

        if (isset($GLOBALS['jobvisa_container']) && function_exists('container')) {
            /** @var Router $router */
            $router = container(Router::class);
            $this->router = $router;
        } else {
            $this->router = new Router($this->resolveBasePath());
        }
    }

    /**
     * Absolute path to the project root.
     */
    public function basePath(string $path = ''): string
    {
        $path = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return $path === ''
            ? $this->basePath
            : $this->basePath . DIRECTORY_SEPARATOR . $path;
    }

    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Register routes via RouteServiceProvider and dispatch.
     */
    public function run(): void
    {
        if (isset($GLOBALS['jobvisa_container']) && function_exists('container')) {
            $provider = new RouteServiceProvider(container());
            $provider->loadRoutes();
        } else {
            $routesFile = $this->basePath('routes/web.php');

            if (is_file($routesFile)) {
                $router = $this->router;
                require $routesFile;
            }
        }

        $this->router->dispatch();
    }

    /**
     * Derive the URL path prefix from APP_URL (e.g. "/jobvisa").
     */
    private function resolveBasePath(): string
    {
        $url = (string) Config::get('app.url', '');
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }
}
