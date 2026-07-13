<?php

declare(strict_types=1);

/**
 * Enterprise routing groups and load order.
 *
 * Existing public URLs stay on the web/health groups without alteration.
 */

return [
    'groups' => [
        'web' => [
            'file' => 'routes/web.php',
            'prefix' => '',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'web'],
        ],
        'health' => [
            'file' => 'routes/health.php',
            'prefix' => '',
            'middleware' => ['https', 'security.headers', 'observability', 'timing', 'web'],
        ],
        'ops' => [
            'file' => 'routes/ops.php',
            'prefix' => '',
            'middleware' => ['security.headers', 'observability', 'timing'],
        ],
        'auth' => [
            'file' => 'routes/auth.php',
            'prefix' => '',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing'],
        ],
        'jobseeker' => [
            'file' => 'routes/jobseeker.php',
            'prefix' => '',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'web', 'remember', 'auth.web', 'verified', 'jobseeker', 'csrf'],
        ],
        'employer' => [
            'file' => 'routes/employer.php',
            'prefix' => '',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'web', 'remember', 'auth.web', 'verified', 'employer', 'csrf'],
        ],
        'admin' => [
            'file' => 'routes/admin.php',
            'prefix' => '/admin',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'web', 'remember', 'auth.web', 'verified', 'admin'],
        ],
        'developers' => [
            'file' => 'routes/developers.php',
            'prefix' => '',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'web'],
        ],
        'api' => [
            'file' => 'routes/api.php',
            'prefix' => '/api',
            'middleware' => ['https', 'maintenance', 'security.headers', 'observability', 'timing', 'api'],
        ],
    ],

    'load_order' => [
        'ops',
        'web',
        'health',
        'auth',
        'developers',
        'jobseeker',
        'employer',
        'admin',
        'api',
    ],
];
