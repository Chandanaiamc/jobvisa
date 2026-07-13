<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\ApplicantRanking\Policies\ApplicantRankingPolicy;
use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingScoringService;
use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingService;
use JobVisa\App\Domain\ApplicantRanking\Validators\ApplicantRankingValidator;
use JobVisa\App\Domain\EmployerDashboard\Policies\EmployerDashboardPolicy;
use JobVisa\App\Domain\EmployerDashboard\Services\EmployerAiDashboardService;
use JobVisa\App\Domain\JobMatching\Services\JobMatchContextFactory;
use JobVisa\App\Domain\JobMatching\Services\JobMatchScoringService;
use JobVisa\App\Domain\InterviewAssistant\Policies\InterviewAssistantPolicy;
use JobVisa\App\Domain\InterviewAssistant\Services\InterviewAssistantService;
use JobVisa\App\Domain\InterviewAssistant\Services\InterviewInsightService;
use JobVisa\App\Domain\InterviewAssistant\Services\InterviewQuestionGenerator;
use JobVisa\App\Domain\RecruiterAssistant\Policies\RecruiterAssistantPolicy;
use JobVisa\App\Domain\RecruiterAssistant\Services\NaturalLanguageQueryParser;
use JobVisa\App\Domain\RecruiterAssistant\Services\RecruiterAssistantService;
use JobVisa\App\Domain\RecruiterAssistant\Services\RecruiterSuggestionService;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\InterviewScorecardRepositoryInterface;
use JobVisa\App\Repositories\Contracts\InterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobApplicantRankingRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\RecruiterCandidateSearchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\RecruiterSearchHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use JobVisa\App\Repositories\InterviewScorecardRepository;
use JobVisa\App\Repositories\InterviewSessionRepository;
use JobVisa\App\Repositories\JobApplicantRankingRepository;
use JobVisa\App\Repositories\RecruiterCandidateSearchRepository;
use JobVisa\App\Repositories\RecruiterSearchHistoryRepository;
use PDO;

/**
 * Employer portal services (Sprint 2F.4+).
 */
final class EmployerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(JobApplicantRankingRepository::class, static function ($c): JobApplicantRankingRepository {
            return new JobApplicantRankingRepository($c->get(PDO::class));
        });
        $this->container->singleton(
            JobApplicantRankingRepositoryInterface::class,
            static fn ($c) => $c->get(JobApplicantRankingRepository::class)
        );

        $this->container->singleton(ApplicantRankingPolicy::class, static fn (): ApplicantRankingPolicy => new ApplicantRankingPolicy());
        $this->container->singleton(ApplicantRankingValidator::class, static fn (): ApplicantRankingValidator => new ApplicantRankingValidator());

        $this->container->singleton(ApplicantRankingScoringService::class, static function ($c): ApplicantRankingScoringService {
            return new ApplicantRankingScoringService(
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(JobMatchContextFactory::class),
                $c->get(JobMatchScoringService::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeReferenceRepositoryInterface::class),
            );
        });

        $this->container->singleton(ApplicantRankingService::class, static function ($c): ApplicantRankingService {
            return new ApplicantRankingService(
                $c->get(JobRepositoryInterface::class),
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(JobApplicantRankingRepositoryInterface::class),
                $c->get(ApplicantRankingScoringService::class),
                $c->get(ApplicantRankingPolicy::class),
                $c->get(ApplicantRankingValidator::class),
            );
        });

        $this->container->singleton(EmployerDashboardPolicy::class, static fn (): EmployerDashboardPolicy => new EmployerDashboardPolicy());
        $this->container->singleton(EmployerAiDashboardService::class, static function ($c): EmployerAiDashboardService {
            return new EmployerAiDashboardService(
                $c->get(JobRepositoryInterface::class),
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(JobApplicantRankingRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(ApplicantRankingService::class),
                $c->get(EmployerDashboardPolicy::class),
            );
        });

        $this->container->singleton(RecruiterSearchHistoryRepository::class, static function ($c): RecruiterSearchHistoryRepository {
            return new RecruiterSearchHistoryRepository($c->get(PDO::class));
        });
        $this->container->singleton(
            RecruiterSearchHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(RecruiterSearchHistoryRepository::class)
        );
        $this->container->singleton(RecruiterCandidateSearchRepository::class, static function ($c): RecruiterCandidateSearchRepository {
            return new RecruiterCandidateSearchRepository($c->get(PDO::class));
        });
        $this->container->singleton(
            RecruiterCandidateSearchRepositoryInterface::class,
            static fn ($c) => $c->get(RecruiterCandidateSearchRepository::class)
        );

        $this->container->singleton(RecruiterAssistantPolicy::class, static fn (): RecruiterAssistantPolicy => new RecruiterAssistantPolicy());
        $this->container->singleton(NaturalLanguageQueryParser::class, static fn (): NaturalLanguageQueryParser => new NaturalLanguageQueryParser());
        $this->container->singleton(RecruiterSuggestionService::class, static fn (): RecruiterSuggestionService => new RecruiterSuggestionService());
        $this->container->singleton(RecruiterAssistantService::class, static function ($c): RecruiterAssistantService {
            return new RecruiterAssistantService(
                $c->get(JobRepositoryInterface::class),
                $c->get(RecruiterCandidateSearchRepositoryInterface::class),
                $c->get(RecruiterSearchHistoryRepositoryInterface::class),
                $c->get(NaturalLanguageQueryParser::class),
                $c->get(RecruiterSuggestionService::class),
                $c->get(RecruiterAssistantPolicy::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(EmployerAiDashboardService::class),
            );
        });

        $this->container->singleton(InterviewSessionRepository::class, static function ($c): InterviewSessionRepository {
            return new InterviewSessionRepository($c->get(PDO::class));
        });
        $this->container->singleton(
            InterviewSessionRepositoryInterface::class,
            static fn ($c) => $c->get(InterviewSessionRepository::class)
        );
        $this->container->singleton(InterviewScorecardRepository::class, static function ($c): InterviewScorecardRepository {
            return new InterviewScorecardRepository($c->get(PDO::class));
        });
        $this->container->singleton(
            InterviewScorecardRepositoryInterface::class,
            static fn ($c) => $c->get(InterviewScorecardRepository::class)
        );

        $this->container->singleton(InterviewAssistantPolicy::class, static fn (): InterviewAssistantPolicy => new InterviewAssistantPolicy());
        $this->container->singleton(InterviewQuestionGenerator::class, static fn (): InterviewQuestionGenerator => new InterviewQuestionGenerator());
        $this->container->singleton(InterviewInsightService::class, static fn (): InterviewInsightService => new InterviewInsightService());
        $this->container->singleton(InterviewAssistantService::class, static function ($c): InterviewAssistantService {
            return new InterviewAssistantService(
                $c->get(JobRepositoryInterface::class),
                $c->get(ApplicationRepositoryInterface::class),
                $c->get(InterviewSessionRepositoryInterface::class),
                $c->get(InterviewScorecardRepositoryInterface::class),
                $c->get(JobApplicantRankingRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(InterviewQuestionGenerator::class),
                $c->get(InterviewInsightService::class),
                $c->get(InterviewAssistantPolicy::class),
            );
        });
    }

    public function boot(): void
    {
    }
}
