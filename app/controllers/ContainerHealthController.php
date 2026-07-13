<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Config\Config;
use JobVisa\App\Container\Container;

/**
 * Development health checks for the DI container.
 */
final class ContainerHealthController extends Controller
{
    /**
     * Temporary local/debug-only container health endpoint.
     */
    public function index(): void
    {
        if (!$this->isDevelopmentAccessAllowed()) {
            http_response_code(404);
            (new \App\Core\View())->display('errors/404', [
                'title' => 'Page Not Found',
                'path' => '/health/container',
            ]);

            return;
        }

        $container = container();
        $config = $container->get(Config::class);

        $configLoaded = $config instanceof Config
            && $config->has('app.name')
            && $config->has('database.host');

        $first = $container->get(Config::class);
        $second = $container->get(Config::class);
        $singletonPassed = $first === $second
            && $container->has(Container::class)
            && $container->has(Config::class);

        $this->render('health/container', [
            'title' => 'Container Health',
            'containerRunning' => $container instanceof Container,
            'configLoaded' => $configLoaded,
            'singletonPassed' => $singletonPassed,
        ]);
    }

    private function isDevelopmentAccessAllowed(): bool
    {
        $env = (string) config('app.env', 'production');
        $debug = (bool) config('app.debug', false);

        return $env === 'local' || $debug === true;
    }
}
