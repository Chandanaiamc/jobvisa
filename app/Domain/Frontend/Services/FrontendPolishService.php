<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Frontend\Services;

use JobVisa\App\Domain\Frontend\Support\FrontendPolishVersion;

/**
 * Frontend polish / accessibility readiness.
 */
final class FrontendPolishService
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $assets = base_path('public/assets');

        return [
            'status' => 'ok',
            'version' => FrontendPolishVersion::CURRENT,
            'enabled' => (bool) config('frontend.enabled', true),
            'skip_link' => (bool) config('frontend.skip_link', true),
            'assets' => [
                'a11y_css' => is_file($assets . '/css/a11y.css'),
                'a11y_js' => is_file($assets . '/js/a11y.js'),
                'developers_css' => is_file($assets . '/css/developers.css'),
                'auth_css' => is_file($assets . '/css/auth.css'),
                'jobseeker_css' => is_file($assets . '/css/jobseeker.css'),
                'api_client_js' => is_file($assets . '/js/api-client.js'),
                'auth_api_js' => is_file($assets . '/js/auth-api.js'),
            ],
            'api_auth' => [
                'enabled' => (bool) config('frontend.api_auth.enabled', true),
                'access_cookie' => (string) config('frontend.api_auth.access_cookie', 'jobvisa_api_access'),
                'refresh_cookie' => (string) config('frontend.api_auth.refresh_cookie', 'jobvisa_api_refresh'),
            ],
            'wcag' => [
                'perceivable' => 'contrast_tokens_shared_focus',
                'operable' => 'skip_link_focus_visible_reduced_motion',
                'understandable' => 'existing_labels_errors',
                'robust' => 'landmarks_lang_main',
            ],
        ];
    }
}
