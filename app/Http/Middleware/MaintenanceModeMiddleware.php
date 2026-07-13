<?php

declare(strict_types=1);

namespace JobVisa\App\Http\Middleware;

use JobVisa\App\Domain\Deployment\Services\MaintenanceModeManager;
use JobVisa\App\Security\SecurityHelper;

/**
 * Serves a maintenance page when APP_MAINTENANCE=true or deploy flag file is present.
 * Ops bypass via secret/IP.
 */
final class MaintenanceModeMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        $active = (bool) config('production.maintenance', false)
            || is_file(base_path('storage/framework/maintenance.json'));

        try {
            if (function_exists('container')) {
                $active = container(MaintenanceModeManager::class)->isActive();
            }
        } catch (\Throwable) {
            // fall back to env/file checks above
        }

        if (!$active) {
            return $next();
        }

        if ($this->isBypassed()) {
            return $next();
        }

        // Allow health probes during maintenance so load balancers can still check readiness intentionally.
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (preg_match('#/health/(live|ready)(/|\\?|$)#', $uri) === 1) {
            return $next();
        }

        http_response_code(503);
        header('Retry-After: 300');
        header('Content-Type: text/html; charset=UTF-8');

        $view = base_path('app/views/errors/503.php');
        if (is_file($view)) {
            $title = 'Maintenance';
            $appName = (string) config('app.name', 'JobVisa.lk');
            require $view;

            return null;
        }

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Maintenance</title></head><body><h1>Temporarily unavailable</h1><p>We are performing scheduled maintenance. Please try again shortly.</p></body></html>';

        return null;
    }

    private function isBypassed(): bool
    {
        $secret = (string) config('production.maintenance_secret', '');
        if ($secret !== '') {
            $provided = (string) ($_GET['maintenance_secret'] ?? $_SERVER['HTTP_X_MAINTENANCE_SECRET'] ?? '');
            if ($provided !== '' && hash_equals($secret, $provided)) {
                return true;
            }
        }

        $allowIp = trim((string) config('production.maintenance_allow_ip', ''));
        if ($allowIp !== '') {
            $client = SecurityHelper::clientIp();
            $allowed = array_map('trim', explode(',', $allowIp));
            if (in_array($client, $allowed, true)) {
                return true;
            }
        }

        return false;
    }
}
