<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use App\Core\Router;
use JobVisa\App\Http\MiddlewarePipeline;
use JobVisa\App\Routing\RouteRegistrar;

/**
 * Registers the router/registrar and loads enterprise route groups.
 */
final class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Router::class, static function (): Router {
            $url = (string) config('app.url', '');
            $path = parse_url($url, PHP_URL_PATH);
            $basePath = is_string($path) ? rtrim($path, '/') : '';

            return new Router($basePath);
        });

        $this->container->singleton(RouteRegistrar::class, static function ($container): RouteRegistrar {
            return new RouteRegistrar($container->get(Router::class));
        });

        $this->container->singleton(MiddlewarePipeline::class, static function (): MiddlewarePipeline {
            /** @var array{aliases?: array<string, class-string>} $config */
            $config = config('middleware', []);
            $aliases = $config['aliases'] ?? [];

            return new MiddlewarePipeline($aliases);
        });
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $this->container->get(MiddlewarePipeline::class);

        $router->setMiddlewareRunner(
            static function (array $middleware, callable $destination) use ($pipeline): mixed {
                return $pipeline->run($middleware, $destination);
            }
        );

        $router->setNotFoundHandler(static function (string $uri): mixed {
            if (str_contains($uri, '/api/') || str_starts_with($uri, '/api')) {
                if (!headers_sent()) {
                    http_response_code(404);
                    header('Content-Type: application/json; charset=utf-8');
                }
                $rid = \JobVisa\App\Domain\Observability\Services\RequestContext::currentId() ?? '';
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Endpoint not found.',
                        'details' => new \stdClass(),
                    ],
                    'request_id' => $rid,
                ], JSON_UNESCAPED_SLASHES);

                return null;
            }

            (new \App\Core\View())->display('errors/404', [
                'title' => 'Page Not Found',
                'path' => $uri,
            ]);

            return null;
        });
    }

    /**
     * Load all configured route group files onto the shared Router.
     */
    public function loadRoutes(): void
    {
        /** @var RouteRegistrar $registrar */
        $registrar = $this->container->get(RouteRegistrar::class);
        $registrar->loadConfiguredRoutes();
    }
}
