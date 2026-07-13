<?php

declare(strict_types=1);

/**
 * Developer portal & SDK foundation (Sprint 4.6).
 */

return [
    'enabled' => filter_var(env('DEV_PORTAL_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'sdk_enabled' => filter_var(env('DEV_SDK_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'base_path' => '/developers',
    'show_try_it' => filter_var(env('DEV_PORTAL_TRY_IT', 'true'), FILTER_VALIDATE_BOOLEAN),
    'default_api_base' => (string) env('DEV_PORTAL_API_BASE', ''),
];
