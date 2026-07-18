<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Auth\RememberMeService;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Auth\Services\AuthLifecycleService;
use JobVisa\App\Domain\Frontend\Auth\ApiAuthTokenCookie;
use JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService;
use JobVisa\App\Domain\Frontend\Services\FrontendPolishService;

/**
 * Frontend polish & API-auth bridge bindings.
 */
final class FrontendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(FrontendPolishService::class, static fn (): FrontendPolishService => new FrontendPolishService());
        $this->container->singleton(ApiAuthTokenCookie::class, static fn (): ApiAuthTokenCookie => new ApiAuthTokenCookie());
        $this->container->singleton(FrontendApiAuthService::class, static function ($c): FrontendApiAuthService {
            return new FrontendApiAuthService(
                $c->get(AuthLifecycleService::class),
                $c->get(AuthManager::class),
                $c->get(UserRepository::class),
                $c->get(ApiAuthTokenCookie::class),
                $c->get(PersonalAccessTokenService::class),
                $c->get(PersonalAccessTokenRepository::class),
                $c->get(DashboardRedirector::class),
                $c->get(RememberMeService::class),
                $c->get(RememberMeCookie::class),
            );
        });
    }

    public function boot(): void
    {
    }
}
