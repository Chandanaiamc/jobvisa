<?php

declare(strict_types=1);

/**
 * Frontend polish & accessibility (Sprint 4.8).
 */

return [
    'enabled' => filter_var(env('FRONTEND_A11Y_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'skip_link' => filter_var(env('FRONTEND_SKIP_LINK', 'true'), FILTER_VALIDATE_BOOLEAN),
    'focus_visible' => filter_var(env('FRONTEND_FOCUS_VISIBLE', 'true'), FILTER_VALIDATE_BOOLEAN),
    'reduced_motion' => filter_var(env('FRONTEND_REDUCED_MOTION', 'true'), FILTER_VALIDATE_BOOLEAN),
];
