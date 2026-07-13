<?php

declare(strict_types=1);

namespace JobVisa\App\Auth;

/**
 * Maps role slugs to post-login dashboard paths.
 */
final class DashboardRedirector
{
    /**
     * @return array{path: string, url: string, role: string|null}
     */
    public function forUser(?array $user): array
    {
        $role = null;

        if (is_array($user)) {
            $role = isset($user['role']) && is_string($user['role']) ? $user['role'] : null;

            if (($role === null || $role === '') && isset($user['role_slug']) && is_string($user['role_slug'])) {
                $role = $user['role_slug'];
            }
        }

        /** @var array<string, string> $map */
        $map = config('auth.dashboards', []);
        $default = (string) ($map['default'] ?? '/');
        $path = $default;

        if ($role !== null && isset($map[$role])) {
            $path = (string) $map[$role];
        }

        return [
            'path' => $path,
            'url' => url($path),
            'role' => $role,
        ];
    }
}
