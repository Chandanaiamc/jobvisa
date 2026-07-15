<?php

declare(strict_types=1);

/**
 * Ordered list of application service providers.
 */

use JobVisa\App\Providers\ApiServiceProvider;
use JobVisa\App\Providers\AppServiceProvider;
use JobVisa\App\Providers\AuthLifecycleServiceProvider;
use JobVisa\App\Providers\AuthServiceProvider;
use JobVisa\App\Providers\CacheServiceProvider;
use JobVisa\App\Providers\DatabaseServiceProvider;
use JobVisa\App\Providers\DeploymentServiceProvider;
use JobVisa\App\Providers\EmployerServiceProvider;
use JobVisa\App\Providers\FrontendServiceProvider;
use JobVisa\App\Providers\JobSeekerServiceProvider;
use JobVisa\App\Providers\LoggingServiceProvider;
use JobVisa\App\Providers\ObservabilityServiceProvider;
use JobVisa\App\Providers\ProductionServiceProvider;
use JobVisa\App\Providers\ReleaseServiceProvider;
use JobVisa\App\Providers\RepositoryServiceProvider;
use JobVisa\App\Providers\RouteServiceProvider;
use JobVisa\App\Providers\SecurityServiceProvider;
use JobVisa\App\Providers\SessionServiceProvider;
use JobVisa\App\Providers\TestingServiceProvider;
use JobVisa\App\Providers\ViewServiceProvider;

return [
    AppServiceProvider::class,
    LoggingServiceProvider::class,
    SessionServiceProvider::class,
    SecurityServiceProvider::class,
    ProductionServiceProvider::class,
    ObservabilityServiceProvider::class,
    CacheServiceProvider::class,
    DeploymentServiceProvider::class,
    DatabaseServiceProvider::class,
    RepositoryServiceProvider::class,
    AuthServiceProvider::class,
    AuthLifecycleServiceProvider::class,
    JobSeekerServiceProvider::class,
    EmployerServiceProvider::class,
    ApiServiceProvider::class,
    FrontendServiceProvider::class,
    TestingServiceProvider::class,
    ReleaseServiceProvider::class,
    ViewServiceProvider::class,
    RouteServiceProvider::class,
];
