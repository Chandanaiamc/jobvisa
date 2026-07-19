<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Audit\ApiAuditLogger;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Http\ApiRequestValidator;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter;
use JobVisa\App\Domain\Api\RateLimit\FileRateLimitStore;
use JobVisa\App\Domain\Api\RateLimit\RateLimitStoreInterface;
use JobVisa\App\Domain\Api\Webhooks\WebhookDispatcher;
use JobVisa\App\Domain\Api\Webhooks\WebhookRepository;
use JobVisa\App\Domain\Job\Services\PublicJobsService;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Domain\Job\Policies\JobPolicy;
use JobVisa\App\Domain\Job\Validators\JobValidator;
use JobVisa\App\Domain\Application\Services\ApplicationService;
use JobVisa\App\Domain\Application\Policies\ApplicationPolicy;
use JobVisa\App\Domain\Application\Validators\ApplicationValidator;
use JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService;
use JobVisa\App\Domain\InterviewScheduling\Policies\InterviewSchedulingPolicy;
use JobVisa\App\Domain\InterviewScheduling\Validators\InterviewSchedulingValidator;
use JobVisa\App\Domain\JobOffer\Services\JobOfferService;
use JobVisa\App\Domain\JobOffer\Policies\JobOfferPolicy;
use JobVisa\App\Domain\JobOffer\Validators\JobOfferValidator;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobOfferRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ScheduledInterviewRepositoryInterface;
use PDO;

/**
 * Enterprise API platform + developer portal bindings (Sprint 4.5 / 4.6).
 */
final class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(ApiRequestValidator::class, static fn (): ApiRequestValidator => new ApiRequestValidator());
        $this->container->singleton(PersonalAccessTokenRepository::class, static fn (): PersonalAccessTokenRepository => new PersonalAccessTokenRepository());
        $this->container->singleton(ApiAuditLogger::class, static fn (): ApiAuditLogger => new ApiAuditLogger());
        $this->container->singleton(WebhookRepository::class, static fn (): WebhookRepository => new WebhookRepository());
        $this->container->singleton(WebhookDispatcher::class, static function ($c): WebhookDispatcher {
            return new WebhookDispatcher($c->get(WebhookRepository::class));
        });
        $this->container->singleton(DeveloperPortalService::class, static fn (): DeveloperPortalService => new DeveloperPortalService());
        $this->container->singleton(PublicJobsService::class, static function ($c): PublicJobsService {
            return new PublicJobsService(
                $c->get(JobRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
            );
        });
        $this->container->singleton(JobPolicy::class, static fn (): JobPolicy => new JobPolicy());
        $this->container->singleton(JobValidator::class, static fn (): JobValidator => new JobValidator());
        $this->container->singleton(EmployerJobsService::class, static function ($c): EmployerJobsService {
            return new EmployerJobsService(
                $c->get(JobRepositoryInterface::class),
                $c->get(JobPolicy::class),
                $c->get(JobValidator::class),
            );
        });
        $this->container->singleton(ApplicationPolicy::class, static fn (): ApplicationPolicy => new ApplicationPolicy());
        $this->container->singleton(ApplicationValidator::class, static fn (): ApplicationValidator => new ApplicationValidator());
        $this->container->singleton(ApplicationService::class, static function ($c): ApplicationService {
            return new ApplicationService(
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(ApplicationPolicy::class),
                $c->get(ApplicationValidator::class),
                $c->get(PDO::class),
            );
        });
        $this->container->singleton(InterviewSchedulingPolicy::class, static fn (): InterviewSchedulingPolicy => new InterviewSchedulingPolicy());
        $this->container->singleton(InterviewSchedulingValidator::class, static fn (): InterviewSchedulingValidator => new InterviewSchedulingValidator());
        $this->container->singleton(InterviewSchedulingService::class, static function ($c): InterviewSchedulingService {
            return new InterviewSchedulingService(
                $c->get(ScheduledInterviewRepositoryInterface::class),
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(InterviewSchedulingPolicy::class),
                $c->get(InterviewSchedulingValidator::class),
                $c->get(PDO::class),
            );
        });
        $this->container->singleton(JobOfferPolicy::class, static fn (): JobOfferPolicy => new JobOfferPolicy());
        $this->container->singleton(JobOfferValidator::class, static fn (): JobOfferValidator => new JobOfferValidator());
        $this->container->singleton(JobOfferService::class, static function ($c): JobOfferService {
            return new JobOfferService(
                $c->get(JobOfferRepositoryInterface::class),
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(JobOfferPolicy::class),
                $c->get(JobOfferValidator::class),
                $c->get(PDO::class),
            );
        });

        $this->container->singleton(RateLimitStoreInterface::class, static function (): RateLimitStoreInterface {
            $driver = strtolower((string) config('api.rate_limit_driver', 'file'));
            // Redis reserved — fall back to file safely.
            return new FileRateLimitStore(base_path('storage/api/rate-limits'));
        });

        $this->container->singleton(ApiRateLimiter::class, static function ($c): ApiRateLimiter {
            return new ApiRateLimiter($c->get(RateLimitStoreInterface::class));
        });

        $this->container->singleton(PersonalAccessTokenService::class, static function ($c): PersonalAccessTokenService {
            return new PersonalAccessTokenService(
                $c->get(PersonalAccessTokenRepository::class),
                $c->get(UserRepository::class),
            );
        });
    }

    public function boot(): void
    {
        foreach (['storage/api', 'storage/api/rate-limits'] as $rel) {
            $dir = base_path($rel);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
            if (!is_file($deny)) {
                @file_put_contents($deny, "Require all denied\n");
            }
        }
    }
}
