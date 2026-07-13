<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Auth\LoginAttemptService;
use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Auth\RegistrationService;
use JobVisa\App\Domain\Security\Services\PasswordPolicy;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Auth\RememberMeService;
use JobVisa\App\Auth\SessionManager as AuthSessionManager;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Http\Middleware\AuthMiddleware;
use JobVisa\App\Http\Middleware\AuthenticateMiddleware;
use JobVisa\App\Http\Middleware\CsrfMiddleware;
use JobVisa\App\Http\Middleware\GuestMiddleware;
use JobVisa\App\Http\Middleware\RedirectIfAuthenticatedMiddleware;
use JobVisa\App\Http\Middleware\RememberMeMiddleware;
use JobVisa\App\Http\Middleware\RequireAuthWebMiddleware;
use JobVisa\App\Http\Middleware\StartSessionMiddleware;
use JobVisa\App\Http\Middleware\VerifiedEmailMiddleware;
use JobVisa\App\Mail\AuthMailer;
use JobVisa\App\Security\RateLimiter;
use JobVisa\App\Security\SessionManager as HttpSessionManager;

/**
 * Registers authentication services, HTTP auth helpers, and middleware.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(PasswordHasher::class, static fn (): PasswordHasher => new PasswordHasher());
        $this->container->singleton(UserRepository::class, static fn (): UserRepository => new UserRepository());
        $this->container->singleton(LoginAttemptService::class, static fn (): LoginAttemptService => new LoginAttemptService());
        $this->container->singleton(DashboardRedirector::class, static fn (): DashboardRedirector => new DashboardRedirector());
        $this->container->singleton(AuthMailer::class, static fn (): AuthMailer => new AuthMailer());
        $this->container->singleton(RateLimiter::class, static fn (): RateLimiter => new RateLimiter());

        $this->container->singleton(RememberMeService::class, static function ($container): RememberMeService {
            return new RememberMeService($container->get(UserRepository::class));
        });

        $this->container->singleton(RememberMeCookie::class, static function ($container): RememberMeCookie {
            return new RememberMeCookie($container->get(RememberMeService::class));
        });

        $this->container->singleton(AuthSessionManager::class, static function ($container): AuthSessionManager {
            return new AuthSessionManager($container->get(HttpSessionManager::class));
        });

        $this->container->singleton(AuthManager::class, static function ($container): AuthManager {
            return new AuthManager(
                $container->get(UserRepository::class),
                $container->get(PasswordHasher::class),
                $container->get(AuthSessionManager::class),
                $container->get(LoginAttemptService::class),
                $container->get(RememberMeService::class)
            );
        });

        $this->container->singleton(EmailVerificationService::class, static function ($container): EmailVerificationService {
            return new EmailVerificationService(
                $container->get(UserRepository::class),
                $container->get(AuthMailer::class),
                $container->get(RateLimiter::class)
            );
        });

        $this->container->singleton(PasswordResetService::class, static function ($container): PasswordResetService {
            return new PasswordResetService(
                $container->get(UserRepository::class),
                $container->get(PasswordHasher::class),
                $container->get(AuthMailer::class),
                $container->get(RateLimiter::class),
                $container->get(PasswordPolicy::class),
            );
        });

        $this->container->singleton(RegistrationService::class, static function ($container): RegistrationService {
            return new RegistrationService(
                $container->get(UserRepository::class),
                $container->get(PasswordHasher::class),
                $container->get(EmailVerificationService::class),
                $container->get(AuthManager::class),
                $container->get(PasswordPolicy::class),
            );
        });

        $this->container->singleton(StartSessionMiddleware::class, static fn (): StartSessionMiddleware => new StartSessionMiddleware());
        $this->container->singleton(CsrfMiddleware::class, static fn (): CsrfMiddleware => new CsrfMiddleware());

        $this->container->singleton(RememberMeMiddleware::class, static function ($container): RememberMeMiddleware {
            return new RememberMeMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class)
            );
        });

        $this->container->singleton(AuthenticateMiddleware::class, static function ($container): AuthenticateMiddleware {
            return new AuthenticateMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class)
            );
        });

        $this->container->singleton(AuthMiddleware::class, static function ($container): AuthMiddleware {
            return new AuthMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class)
            );
        });

        $this->container->singleton(GuestMiddleware::class, static function ($container): GuestMiddleware {
            return new GuestMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class)
            );
        });

        $this->container->singleton(RedirectIfAuthenticatedMiddleware::class, static function ($container): RedirectIfAuthenticatedMiddleware {
            return new RedirectIfAuthenticatedMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class),
                $container->get(DashboardRedirector::class)
            );
        });

        $this->container->singleton(RequireAuthWebMiddleware::class, static function ($container): RequireAuthWebMiddleware {
            return new RequireAuthWebMiddleware(
                $container->get(AuthManager::class),
                $container->get(RememberMeCookie::class)
            );
        });

        $this->container->singleton(VerifiedEmailMiddleware::class, static function ($container): VerifiedEmailMiddleware {
            return new VerifiedEmailMiddleware($container->get(AuthManager::class));
        });
    }

    public function boot(): void
    {
        // Routes are declared in routes/auth.php and loaded by RouteServiceProvider.
    }
}
