<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\ApplicationAssistant\Policies\ApplicationAssistantPolicy;
use JobVisa\App\Domain\ApplicationAssistant\Services\ApplicationAssistantPdfExporter;
use JobVisa\App\Domain\ApplicationAssistant\Services\ApplicationAssistantService;
use JobVisa\App\Domain\ApplicationAssistant\Services\ApplicationReadinessAnalyzer;
use JobVisa\App\Domain\ApplicationAssistant\Validators\ApplicationAssistantValidator;
use JobVisa\App\Domain\OfferEvaluation\Policies\OfferEvaluationPolicy;
use JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationAnalyzer;
use JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationPdfExporter;
use JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService;
use JobVisa\App\Domain\OfferEvaluation\Validators\OfferEvaluationValidator;
use JobVisa\App\Domain\JobSearchCopilot\Policies\JobSearchCopilotPolicy;
use JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotAnalyzer;
use JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotPdfExporter;
use JobVisa\App\Domain\JobSearchCopilot\Services\JobSearchCopilotService;
use JobVisa\App\Domain\JobSearchCopilot\Validators\JobSearchCopilotValidator;
use JobVisa\App\Domain\MockInterview\Policies\MockInterviewPolicy;
use JobVisa\App\Domain\MockInterview\Services\MockInterviewAnalyzer;
use JobVisa\App\Domain\MockInterview\Services\MockInterviewPdfExporter;
use JobVisa\App\Domain\MockInterview\Services\MockInterviewService;
use JobVisa\App\Domain\MockInterview\Validators\MockInterviewValidator;
use JobVisa\App\Domain\PortfolioBuilder\Policies\PortfolioBuilderPolicy;
use JobVisa\App\Domain\PortfolioBuilder\Services\PortfolioBuilderAnalyzer;
use JobVisa\App\Domain\PortfolioBuilder\Services\PortfolioBuilderPdfExporter;
use JobVisa\App\Domain\PortfolioBuilder\Services\PortfolioBuilderService;
use JobVisa\App\Domain\PortfolioBuilder\Validators\PortfolioBuilderValidator;
use JobVisa\App\Domain\LearningPath\Policies\LearningPathPolicy;
use JobVisa\App\Domain\LearningPath\Services\LearningPathAnalyzer;
use JobVisa\App\Domain\LearningPath\Services\LearningPathPdfExporter;
use JobVisa\App\Domain\LearningPath\Services\LearningPathService;
use JobVisa\App\Domain\LearningPath\Validators\LearningPathValidator;
use JobVisa\App\Domain\SkillGap\Policies\SkillGapPolicy;
use JobVisa\App\Domain\SkillGap\Services\SkillGapAnalyzer;
use JobVisa\App\Domain\SkillGap\Services\SkillGapPdfExporter;
use JobVisa\App\Domain\SkillGap\Services\SkillGapService;
use JobVisa\App\Domain\SkillGap\Validators\SkillGapValidator;
use JobVisa\App\Domain\SalaryIntelligence\Policies\SalaryIntelligencePolicy;
use JobVisa\App\Domain\SalaryIntelligence\Services\SalaryIntelligencePdfExporter;
use JobVisa\App\Domain\SalaryIntelligence\Services\SalaryIntelligencePredictor;
use JobVisa\App\Domain\SalaryIntelligence\Services\SalaryIntelligenceService;
use JobVisa\App\Domain\SalaryIntelligence\Validators\SalaryIntelligenceValidator;
use JobVisa\App\Domain\CoverLetter\Policies\CoverLetterPolicy;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterDocxExporter;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterGenerator;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterPdfExporter;
use JobVisa\App\Domain\CoverLetter\Services\CoverLetterService;
use JobVisa\App\Domain\CoverLetter\Validators\CoverLetterValidator;
use JobVisa\App\Domain\CareerCoach\Policies\CareerCoachPolicy;
use JobVisa\App\Domain\CareerCoach\Services\CareerCoachGenerator;
use JobVisa\App\Domain\CareerCoach\Services\CareerCoachService;
use JobVisa\App\Domain\CareerCoach\Validators\CareerCoachValidator;
use JobVisa\App\Domain\ResumeBuilder\Policies\ResumeBuilderPolicy;
use JobVisa\App\Domain\ResumeBuilder\Services\ResumeBuilderGenerator;
use JobVisa\App\Domain\ResumeBuilder\Services\ResumeBuilderService;
use JobVisa\App\Domain\ResumeBuilder\Validators\ResumeBuilderValidator;
use JobVisa\App\Domain\Resume\Factories\ResumeFactory;
use JobVisa\App\Domain\Resume\Intelligence\Policies\ResumeIntelligencePolicy;
use JobVisa\App\Domain\Resume\Intelligence\Rules\AchievementsRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\CertificationsRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ContactReadinessRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\EducationStrengthRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ExperienceStrengthRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\LanguagesRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\PortfolioRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ProfessionalSummaryRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ProfileQualityRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ProjectsRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\PublicationsRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\ReferencesRule;
use JobVisa\App\Domain\Resume\Intelligence\Rules\SkillsQualityRule;
use JobVisa\App\Domain\Resume\Intelligence\Services\AtsReadinessCalculator;
use JobVisa\App\Domain\Resume\Intelligence\Services\EmployerReadinessCalculator;
use JobVisa\App\Domain\Resume\Intelligence\Services\KeywordMatchingService;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceCalculator;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceContextFactory;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceRecommendationService;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceService;
use JobVisa\App\Domain\Resume\Intelligence\Services\SkillGapAnalysisService;
use JobVisa\App\Domain\JobMatching\Policies\JobMatchPolicy;
use JobVisa\App\Domain\JobMatching\Services\JobMatchContextFactory;
use JobVisa\App\Domain\JobMatching\Services\JobMatchExplanationService;
use JobVisa\App\Domain\JobMatching\Services\JobMatchScoringService;
use JobVisa\App\Domain\JobMatching\Services\JobMatchService;
use JobVisa\App\Domain\JobMatching\Services\JobRequirementExtractor;
use JobVisa\App\Domain\JobMatching\Validators\JobMatchValidator;
use JobVisa\App\Domain\Resume\Policies\ResumeAchievementPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeCertificationPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeEducationPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeExperiencePolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeLanguagePolicy;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;
use JobVisa\App\Domain\Resume\Policies\ResumePortfolioPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeProjectPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumePublicationPolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeReferencePolicy;
use JobVisa\App\Domain\Resume\Policies\ResumeSkillPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface as DomainResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Services\ResumeAchievementService;
use JobVisa\App\Domain\Resume\Services\ResumeCertificationService;
use JobVisa\App\Domain\Resume\Services\ResumeEducationService;
use JobVisa\App\Domain\Resume\Services\ResumeExperienceService;
use JobVisa\App\Domain\Resume\Services\ResumeLanguageService;
use JobVisa\App\Domain\Resume\Services\ResumePersonalService;
use JobVisa\App\Domain\Resume\Services\ResumePortfolioService;
use JobVisa\App\Domain\Resume\Services\ResumeProfessionalService;
use JobVisa\App\Domain\Resume\Services\ResumeProjectService;
use JobVisa\App\Domain\Resume\Services\ResumePublicationService;
use JobVisa\App\Domain\Resume\Services\ResumeReferenceService;
use JobVisa\App\Domain\Resume\Services\ResumeService;
use JobVisa\App\Domain\Resume\Services\ResumeSkillService;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeAchievementValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeCertificationValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeEducationValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeExperienceValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeLanguageValidator;
use JobVisa\App\Domain\Resume\Validators\ResumePersonalValidator;
use JobVisa\App\Domain\Resume\Validators\ResumePortfolioValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeProfessionalValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeProjectValidator;
use JobVisa\App\Domain\Resume\Validators\ResumePublicationValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeReferenceValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeSkillValidator;
use JobVisa\App\Domain\Resume\Validators\ResumeValidator;
use JobVisa\App\JobSeeker\CvService;
use JobVisa\App\JobSeeker\EducationService;
use JobVisa\App\JobSeeker\ExperienceService;
use JobVisa\App\JobSeeker\LanguageService;
use JobVisa\App\JobSeeker\ProfileAccess;
use JobVisa\App\JobSeeker\ProfileCompletenessService;
use JobVisa\App\JobSeeker\ProfileService;
use JobVisa\App\JobSeeker\SkillService;
use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ApplicationAssistantAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ApplicationAssistantHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\OfferEvaluationAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\OfferEvaluationHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobSearchCopilotHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobSearchCopilotPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\MockInterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\PortfolioBuilderPlanRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LearningPathHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\LearningPathRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapAnalysisRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillGapHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligenceHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SalaryIntelligencePredictionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CoverLetterHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CoverLetterVersionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\AiResumeBuilderHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\AiResumeVersionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CareerCoachHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CareerCoachSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceHistoryRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePersonalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePublicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;
use JobVisa\App\Repositories\EducationRepository;
use JobVisa\App\Repositories\LanguageCatalogRepository;
use JobVisa\App\Repositories\LocationRepository;
use JobVisa\App\Repositories\ResumeAchievementRepository;
use JobVisa\App\Repositories\ResumeCertificationRepository;
use JobVisa\App\Repositories\ApplicationAssistantAnalysisRepository;
use JobVisa\App\Repositories\ApplicationAssistantHistoryRepository;
use JobVisa\App\Repositories\OfferEvaluationAnalysisRepository;
use JobVisa\App\Repositories\OfferEvaluationHistoryRepository;
use JobVisa\App\Repositories\JobSearchCopilotHistoryRepository;
use JobVisa\App\Repositories\JobSearchCopilotPlanRepository;
use JobVisa\App\Repositories\MockInterviewHistoryRepository;
use JobVisa\App\Repositories\MockInterviewSessionRepository;
use JobVisa\App\Repositories\PortfolioBuilderHistoryRepository;
use JobVisa\App\Repositories\PortfolioBuilderPlanRepository;
use JobVisa\App\Repositories\LearningPathHistoryRepository;
use JobVisa\App\Repositories\LearningPathRepository;
use JobVisa\App\Repositories\SkillGapAnalysisRepository;
use JobVisa\App\Repositories\SkillGapHistoryRepository;
use JobVisa\App\Repositories\SalaryIntelligenceHistoryRepository;
use JobVisa\App\Repositories\SalaryIntelligencePredictionRepository;
use JobVisa\App\Repositories\SalaryMarketSampleRepository;
use JobVisa\App\Repositories\CoverLetterHistoryRepository;
use JobVisa\App\Repositories\CoverLetterVersionRepository;
use JobVisa\App\Repositories\AiResumeBuilderHistoryRepository;
use JobVisa\App\Repositories\AiResumeVersionRepository;
use JobVisa\App\Repositories\CareerCoachHistoryRepository;
use JobVisa\App\Repositories\CareerCoachSessionRepository;
use JobVisa\App\Repositories\ResumeIntelligenceHistoryRepository;
use JobVisa\App\Repositories\ResumeIntelligenceRepository;
use JobVisa\App\Repositories\ResumeJobMatchRepository;
use JobVisa\App\Repositories\ResumeLanguageRepository;
use JobVisa\App\Repositories\ResumePersonalRepository;
use JobVisa\App\Repositories\ResumePortfolioRepository;
use JobVisa\App\Repositories\ResumeProfessionalRepository;
use JobVisa\App\Repositories\ResumeProjectRepository;
use JobVisa\App\Repositories\ResumePublicationRepository;
use JobVisa\App\Repositories\ResumeReferenceRepository;
use JobVisa\App\Repositories\ResumeRepository;
use JobVisa\App\Repositories\ResumeSkillRepository;
use JobVisa\App\Repositories\SkillCatalogRepository;
use JobVisa\App\Repositories\UserLanguageRepository;
use JobVisa\App\Repositories\UserProfileRepository;
use JobVisa\App\Repositories\UserSkillRepository;
use JobVisa\App\Repositories\WorkExperienceRepository;
use JobVisa\App\Support\FileStorage;
use PDO;

/**
 * Job seeker profile + resume foundation bindings (Sprint 2C / 2D.1).
 * Does not alter authentication services.
 */
final class JobSeekerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(FileStorage::class, static fn (): FileStorage => new FileStorage());
        $this->container->singleton(ProfileAccess::class, static fn (): ProfileAccess => new ProfileAccess());
        $this->container->singleton(ResumeFactory::class, static fn (): ResumeFactory => new ResumeFactory());
        $this->container->singleton(ResumeValidator::class, static fn (): ResumeValidator => new ResumeValidator());
        $this->container->singleton(ResumePersonalValidator::class, static fn (): ResumePersonalValidator => new ResumePersonalValidator());
        $this->container->singleton(ResumeProfessionalValidator::class, static fn (): ResumeProfessionalValidator => new ResumeProfessionalValidator());
        $this->container->singleton(ResumeEducationValidator::class, static fn (): ResumeEducationValidator => new ResumeEducationValidator());
        $this->container->singleton(ResumeExperienceValidator::class, static fn (): ResumeExperienceValidator => new ResumeExperienceValidator());
        $this->container->singleton(ResumeSkillValidator::class, static fn (): ResumeSkillValidator => new ResumeSkillValidator());
        $this->container->singleton(ResumeLanguageValidator::class, static fn (): ResumeLanguageValidator => new ResumeLanguageValidator());
        $this->container->singleton(ResumeCertificationValidator::class, static fn (): ResumeCertificationValidator => new ResumeCertificationValidator());
        $this->container->singleton(ResumeProjectValidator::class, static fn (): ResumeProjectValidator => new ResumeProjectValidator());
        $this->container->singleton(ResumeAchievementValidator::class, static fn (): ResumeAchievementValidator => new ResumeAchievementValidator());
        $this->container->singleton(ResumePublicationValidator::class, static fn (): ResumePublicationValidator => new ResumePublicationValidator());
        $this->container->singleton(ResumePortfolioValidator::class, static fn (): ResumePortfolioValidator => new ResumePortfolioValidator());
        $this->container->singleton(ResumeReferenceValidator::class, static fn (): ResumeReferenceValidator => new ResumeReferenceValidator());
        $this->container->singleton(ResumePolicy::class, static fn (): ResumePolicy => new ResumePolicy());
        $this->container->singleton(ResumeEducationPolicy::class, static function ($c): ResumeEducationPolicy {
            return new ResumeEducationPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeExperiencePolicy::class, static function ($c): ResumeExperiencePolicy {
            return new ResumeExperiencePolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeSkillPolicy::class, static function ($c): ResumeSkillPolicy {
            return new ResumeSkillPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeLanguagePolicy::class, static function ($c): ResumeLanguagePolicy {
            return new ResumeLanguagePolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeCertificationPolicy::class, static function ($c): ResumeCertificationPolicy {
            return new ResumeCertificationPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeProjectPolicy::class, static function ($c): ResumeProjectPolicy {
            return new ResumeProjectPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeAchievementPolicy::class, static function ($c): ResumeAchievementPolicy {
            return new ResumeAchievementPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumePublicationPolicy::class, static function ($c): ResumePublicationPolicy {
            return new ResumePublicationPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumePortfolioPolicy::class, static function ($c): ResumePortfolioPolicy {
            return new ResumePortfolioPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeReferencePolicy::class, static function ($c): ResumeReferencePolicy {
            return new ResumeReferencePolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeIntelligencePolicy::class, static function ($c): ResumeIntelligencePolicy {
            return new ResumeIntelligencePolicy($c->get(ResumePolicy::class));
        });

        $this->container->singleton(UserProfileRepository::class, static fn ($c) => new UserProfileRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeRepository::class, static function ($c): ResumeRepository {
            return new ResumeRepository($c->get(PDO::class), $c->get(ResumeFactory::class));
        });
        $this->container->singleton(ResumePersonalRepository::class, static fn ($c) => new ResumePersonalRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeProfessionalRepository::class, static fn ($c) => new ResumeProfessionalRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeSkillRepository::class, static fn ($c) => new ResumeSkillRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeLanguageRepository::class, static fn ($c) => new ResumeLanguageRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeCertificationRepository::class, static fn ($c) => new ResumeCertificationRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeProjectRepository::class, static fn ($c) => new ResumeProjectRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeAchievementRepository::class, static fn ($c) => new ResumeAchievementRepository($c->get(PDO::class)));
        $this->container->singleton(ResumePublicationRepository::class, static fn ($c) => new ResumePublicationRepository($c->get(PDO::class)));
        $this->container->singleton(ResumePortfolioRepository::class, static fn ($c) => new ResumePortfolioRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeReferenceRepository::class, static fn ($c) => new ResumeReferenceRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeIntelligenceRepository::class, static fn ($c) => new ResumeIntelligenceRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeIntelligenceHistoryRepository::class, static fn ($c) => new ResumeIntelligenceHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(ResumeJobMatchRepository::class, static fn ($c) => new ResumeJobMatchRepository($c->get(PDO::class)));
        $this->container->singleton(EducationRepository::class, static fn ($c) => new EducationRepository($c->get(PDO::class)));
        $this->container->singleton(WorkExperienceRepository::class, static fn ($c) => new WorkExperienceRepository($c->get(PDO::class)));
        $this->container->singleton(SkillCatalogRepository::class, static fn ($c) => new SkillCatalogRepository(
            $c->get(PDO::class),
            $c->get(\JobVisa\App\Cache\CacheInterface::class)
        ));
        $this->container->singleton(UserSkillRepository::class, static fn ($c) => new UserSkillRepository($c->get(PDO::class)));
        $this->container->singleton(LanguageCatalogRepository::class, static fn ($c) => new LanguageCatalogRepository(
            $c->get(PDO::class),
            $c->get(\JobVisa\App\Cache\CacheInterface::class)
        ));
        $this->container->singleton(UserLanguageRepository::class, static fn ($c) => new UserLanguageRepository($c->get(PDO::class)));
        $this->container->singleton(LocationRepository::class, static fn ($c) => new LocationRepository(
            $c->get(PDO::class),
            $c->get(\JobVisa\App\Cache\CacheInterface::class)
        ));

        $this->container->singleton(UserProfileRepositoryInterface::class, static fn ($c) => $c->get(UserProfileRepository::class));
        $this->container->singleton(ResumeRepositoryInterface::class, static fn ($c) => $c->get(ResumeRepository::class));
        $this->container->singleton(DomainResumeRepositoryInterface::class, static fn ($c) => $c->get(ResumeRepository::class));
        $this->container->singleton(ResumePersonalRepositoryInterface::class, static fn ($c) => $c->get(ResumePersonalRepository::class));
        $this->container->singleton(ResumeProfessionalRepositoryInterface::class, static fn ($c) => $c->get(ResumeProfessionalRepository::class));
        $this->container->singleton(ResumeSkillRepositoryInterface::class, static fn ($c) => $c->get(ResumeSkillRepository::class));
        $this->container->singleton(ResumeLanguageRepositoryInterface::class, static fn ($c) => $c->get(ResumeLanguageRepository::class));
        $this->container->singleton(ResumeCertificationRepositoryInterface::class, static fn ($c) => $c->get(ResumeCertificationRepository::class));
        $this->container->singleton(ResumeProjectRepositoryInterface::class, static fn ($c) => $c->get(ResumeProjectRepository::class));
        $this->container->singleton(ResumeAchievementRepositoryInterface::class, static fn ($c) => $c->get(ResumeAchievementRepository::class));
        $this->container->singleton(ResumePublicationRepositoryInterface::class, static fn ($c) => $c->get(ResumePublicationRepository::class));
        $this->container->singleton(ResumePortfolioRepositoryInterface::class, static fn ($c) => $c->get(ResumePortfolioRepository::class));
        $this->container->singleton(ResumeReferenceRepositoryInterface::class, static fn ($c) => $c->get(ResumeReferenceRepository::class));
        $this->container->singleton(ResumeIntelligenceRepositoryInterface::class, static fn ($c) => $c->get(ResumeIntelligenceRepository::class));
        $this->container->singleton(ResumeIntelligenceHistoryRepositoryInterface::class, static fn ($c) => $c->get(ResumeIntelligenceHistoryRepository::class));
        $this->container->singleton(ResumeJobMatchRepositoryInterface::class, static fn ($c) => $c->get(ResumeJobMatchRepository::class));
        $this->container->singleton(EducationRepositoryInterface::class, static fn ($c) => $c->get(EducationRepository::class));
        $this->container->singleton(WorkExperienceRepositoryInterface::class, static fn ($c) => $c->get(WorkExperienceRepository::class));
        $this->container->singleton(SkillCatalogRepositoryInterface::class, static fn ($c) => $c->get(SkillCatalogRepository::class));
        $this->container->singleton(UserSkillRepositoryInterface::class, static fn ($c) => $c->get(UserSkillRepository::class));
        $this->container->singleton(LanguageCatalogRepositoryInterface::class, static fn ($c) => $c->get(LanguageCatalogRepository::class));
        $this->container->singleton(UserLanguageRepositoryInterface::class, static fn ($c) => $c->get(UserLanguageRepository::class));
        $this->container->singleton(LocationRepositoryInterface::class, static fn ($c) => $c->get(LocationRepository::class));

        $this->container->singleton(ResumeCompletionCalculator::class, static function ($c): ResumeCompletionCalculator {
            return new ResumeCompletionCalculator(
                $c->get(ResumeRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeLanguageRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(ResumePublicationRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeReferenceRepositoryInterface::class),
                $c->get(PDO::class)
            );
        });

        $this->container->singleton(ResumeService::class, static function ($c): ResumeService {
            return new ResumeService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeFactory::class),
                $c->get(ResumeValidator::class),
                $c->get(ResumePolicy::class),
                $c->get(PDO::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(ResumePersonalService::class, static function ($c): ResumePersonalService {
            return new ResumePersonalService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumePersonalRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumePersonalValidator::class),
                $c->get(ResumePolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumeProfessionalService::class, static function ($c): ResumeProfessionalService {
            return new ResumeProfessionalService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(ResumeProfessionalValidator::class),
                $c->get(ResumePolicy::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(ResumeEducationService::class, static function ($c): ResumeEducationService {
            return new ResumeEducationService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumeEducationValidator::class),
                $c->get(ResumeEducationPolicy::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(ResumeExperienceService::class, static function ($c): ResumeExperienceService {
            return new ResumeExperienceService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(ResumeExperienceValidator::class),
                $c->get(ResumeExperiencePolicy::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(ResumeSkillService::class, static function ($c): ResumeSkillService {
            return new ResumeSkillService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(ResumeSkillValidator::class),
                $c->get(ResumeSkillPolicy::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(ResumeLanguageService::class, static function ($c): ResumeLanguageService {
            return new ResumeLanguageService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeLanguageRepositoryInterface::class),
                $c->get(LanguageCatalogRepositoryInterface::class),
                $c->get(ResumeLanguageValidator::class),
                $c->get(ResumeLanguagePolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumeCertificationService::class, static function ($c): ResumeCertificationService {
            return new ResumeCertificationService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumeCertificationValidator::class),
                $c->get(ResumeCertificationPolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumeProjectService::class, static function ($c): ResumeProjectService {
            return new ResumeProjectService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumeProjectValidator::class),
                $c->get(ResumeProjectPolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumeAchievementService::class, static function ($c): ResumeAchievementService {
            return new ResumeAchievementService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumeAchievementValidator::class),
                $c->get(ResumeAchievementPolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumePublicationService::class, static function ($c): ResumePublicationService {
            return new ResumePublicationService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumePublicationRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumePublicationValidator::class),
                $c->get(ResumePublicationPolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumePortfolioService::class, static function ($c): ResumePortfolioService {
            return new ResumePortfolioService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumePortfolioValidator::class),
                $c->get(ResumePortfolioPolicy::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(FileStorage::class)
            );
        });

        $this->container->singleton(ResumeReferenceService::class, static function ($c): ResumeReferenceService {
            return new ResumeReferenceService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeReferenceRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ResumeReferenceValidator::class),
                $c->get(ResumeReferencePolicy::class),
                $c->get(ResumeCompletionCalculator::class)
            );
        });

        $this->container->singleton(AtsReadinessCalculator::class, static fn (): AtsReadinessCalculator => new AtsReadinessCalculator());
        $this->container->singleton(EmployerReadinessCalculator::class, static fn (): EmployerReadinessCalculator => new EmployerReadinessCalculator());
        $this->container->singleton(KeywordMatchingService::class, static fn (): KeywordMatchingService => new KeywordMatchingService());
        $this->container->singleton(SkillGapAnalysisService::class, static fn (): SkillGapAnalysisService => new SkillGapAnalysisService());
        $this->container->singleton(ResumeIntelligenceRecommendationService::class, static fn (): ResumeIntelligenceRecommendationService => new ResumeIntelligenceRecommendationService());

        $this->container->singleton(ResumeIntelligenceContextFactory::class, static function ($c): ResumeIntelligenceContextFactory {
            return new ResumeIntelligenceContextFactory(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeLanguageRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(ResumePublicationRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeReferenceRepositoryInterface::class),
            );
        });

        $this->container->singleton(ResumeIntelligenceCalculator::class, static function ($c): ResumeIntelligenceCalculator {
            return new ResumeIntelligenceCalculator(
                [
                    new ProfileQualityRule(),
                    new ProfessionalSummaryRule(),
                    new EducationStrengthRule(),
                    new ExperienceStrengthRule(),
                    new SkillsQualityRule(),
                    new LanguagesRule(),
                    new CertificationsRule(),
                    new ProjectsRule(),
                    new AchievementsRule(),
                    new PublicationsRule(),
                    new PortfolioRule(),
                    new ReferencesRule(),
                    new ContactReadinessRule(),
                ],
                $c->get(AtsReadinessCalculator::class),
                $c->get(EmployerReadinessCalculator::class),
                $c->get(KeywordMatchingService::class),
                $c->get(SkillGapAnalysisService::class),
                $c->get(ResumeIntelligenceRecommendationService::class),
            );
        });

        $this->container->singleton(ResumeIntelligenceService::class, static function ($c): ResumeIntelligenceService {
            return new ResumeIntelligenceService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeIntelligenceHistoryRepositoryInterface::class),
                $c->get(ResumeIntelligenceContextFactory::class),
                $c->get(ResumeIntelligenceCalculator::class),
                $c->get(ResumeIntelligencePolicy::class),
                $c->get(ResumeCompletionCalculator::class),
            );
        });

        $this->container->singleton(JobMatchPolicy::class, static function ($c): JobMatchPolicy {
            return new JobMatchPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(JobMatchValidator::class, static fn (): JobMatchValidator => new JobMatchValidator());
        $this->container->singleton(JobRequirementExtractor::class, static fn (): JobRequirementExtractor => new JobRequirementExtractor());
        $this->container->singleton(JobMatchExplanationService::class, static fn (): JobMatchExplanationService => new JobMatchExplanationService());
        $this->container->singleton(JobMatchScoringService::class, static function ($c): JobMatchScoringService {
            return new JobMatchScoringService(
                $c->get(JobRequirementExtractor::class),
                $c->get(JobMatchExplanationService::class),
            );
        });
        $this->container->singleton(JobMatchContextFactory::class, static function ($c): JobMatchContextFactory {
            return new JobMatchContextFactory(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeLanguageRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumePersonalRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(LanguageCatalogRepositoryInterface::class),
            );
        });
        $this->container->singleton(JobMatchService::class, static function ($c): JobMatchService {
            return new JobMatchService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(JobMatchContextFactory::class),
                $c->get(JobMatchScoringService::class),
                $c->get(JobMatchPolicy::class),
                $c->get(JobMatchValidator::class),
                $c->get(ResumeCompletionCalculator::class),
            );
        });

        $this->container->singleton(CareerCoachSessionRepository::class, static fn ($c) => new CareerCoachSessionRepository($c->get(PDO::class)));
        $this->container->singleton(CareerCoachHistoryRepository::class, static fn ($c) => new CareerCoachHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            CareerCoachSessionRepositoryInterface::class,
            static fn ($c) => $c->get(CareerCoachSessionRepository::class)
        );
        $this->container->singleton(
            CareerCoachHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(CareerCoachHistoryRepository::class)
        );
        $this->container->singleton(CareerCoachPolicy::class, static function ($c): CareerCoachPolicy {
            return new CareerCoachPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(CareerCoachValidator::class, static fn (): CareerCoachValidator => new CareerCoachValidator());
        $this->container->singleton(CareerCoachGenerator::class, static fn (): CareerCoachGenerator => new CareerCoachGenerator());
        $this->container->singleton(CareerCoachService::class, static function ($c): CareerCoachService {
            return new CareerCoachService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(CareerCoachHistoryRepositoryInterface::class),
                $c->get(CareerCoachGenerator::class),
                $c->get(CareerCoachPolicy::class),
                $c->get(CareerCoachValidator::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
            );
        });

        $this->container->singleton(AiResumeVersionRepository::class, static fn ($c) => new AiResumeVersionRepository($c->get(PDO::class)));
        $this->container->singleton(AiResumeBuilderHistoryRepository::class, static fn ($c) => new AiResumeBuilderHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            AiResumeVersionRepositoryInterface::class,
            static fn ($c) => $c->get(AiResumeVersionRepository::class)
        );
        $this->container->singleton(
            AiResumeBuilderHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(AiResumeBuilderHistoryRepository::class)
        );
        $this->container->singleton(ResumeBuilderPolicy::class, static function ($c): ResumeBuilderPolicy {
            return new ResumeBuilderPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(ResumeBuilderValidator::class, static fn (): ResumeBuilderValidator => new ResumeBuilderValidator());
        $this->container->singleton(ResumeBuilderGenerator::class, static fn (): ResumeBuilderGenerator => new ResumeBuilderGenerator());
        $this->container->singleton(ResumeBuilderService::class, static function ($c): ResumeBuilderService {
            return new ResumeBuilderService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(AiResumeVersionRepositoryInterface::class),
                $c->get(AiResumeBuilderHistoryRepositoryInterface::class),
                $c->get(ResumeBuilderGenerator::class),
                $c->get(ResumeBuilderPolicy::class),
                $c->get(ResumeBuilderValidator::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
            );
        });

        $this->container->singleton(CoverLetterVersionRepository::class, static fn ($c) => new CoverLetterVersionRepository($c->get(PDO::class)));
        $this->container->singleton(CoverLetterHistoryRepository::class, static fn ($c) => new CoverLetterHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            CoverLetterVersionRepositoryInterface::class,
            static fn ($c) => $c->get(CoverLetterVersionRepository::class)
        );
        $this->container->singleton(
            CoverLetterHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(CoverLetterHistoryRepository::class)
        );
        $this->container->singleton(CoverLetterPolicy::class, static function ($c): CoverLetterPolicy {
            return new CoverLetterPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(CoverLetterValidator::class, static fn (): CoverLetterValidator => new CoverLetterValidator());
        $this->container->singleton(CoverLetterGenerator::class, static fn (): CoverLetterGenerator => new CoverLetterGenerator());
        $this->container->singleton(CoverLetterPdfExporter::class, static fn (): CoverLetterPdfExporter => new CoverLetterPdfExporter());
        $this->container->singleton(CoverLetterDocxExporter::class, static fn (): CoverLetterDocxExporter => new CoverLetterDocxExporter());
        $this->container->singleton(CoverLetterService::class, static function ($c): CoverLetterService {
            return new CoverLetterService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(CoverLetterVersionRepositoryInterface::class),
                $c->get(CoverLetterHistoryRepositoryInterface::class),
                $c->get(CoverLetterGenerator::class),
                $c->get(CoverLetterPdfExporter::class),
                $c->get(CoverLetterDocxExporter::class),
                $c->get(CoverLetterPolicy::class),
                $c->get(CoverLetterValidator::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(UserProfileRepositoryInterface::class),
            );
        });

        $this->container->singleton(ApplicationAssistantAnalysisRepository::class, static fn ($c) => new ApplicationAssistantAnalysisRepository($c->get(PDO::class)));
        $this->container->singleton(ApplicationAssistantHistoryRepository::class, static fn ($c) => new ApplicationAssistantHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            ApplicationAssistantAnalysisRepositoryInterface::class,
            static fn ($c) => $c->get(ApplicationAssistantAnalysisRepository::class)
        );
        $this->container->singleton(
            ApplicationAssistantHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(ApplicationAssistantHistoryRepository::class)
        );
        $this->container->singleton(ApplicationAssistantPolicy::class, static fn (): ApplicationAssistantPolicy => new ApplicationAssistantPolicy());
        $this->container->singleton(ApplicationAssistantValidator::class, static fn (): ApplicationAssistantValidator => new ApplicationAssistantValidator());
        $this->container->singleton(ApplicationReadinessAnalyzer::class, static fn (): ApplicationReadinessAnalyzer => new ApplicationReadinessAnalyzer());
        $this->container->singleton(ApplicationAssistantPdfExporter::class, static function ($c): ApplicationAssistantPdfExporter {
            return new ApplicationAssistantPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(ApplicationAssistantService::class, static function ($c): ApplicationAssistantService {
            return new ApplicationAssistantService(
                $c->get(JobRepositoryInterface::class),
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(ApplicationAssistantAnalysisRepositoryInterface::class),
                $c->get(ApplicationAssistantHistoryRepositoryInterface::class),
                $c->get(ApplicationReadinessAnalyzer::class),
                $c->get(ApplicationAssistantPdfExporter::class),
                $c->get(JobMatchContextFactory::class),
                $c->get(JobMatchScoringService::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeAchievementRepositoryInterface::class),
                $c->get(ApplicationAssistantPolicy::class),
                $c->get(ApplicationAssistantValidator::class),
            );
        });

        $this->container->singleton(SalaryIntelligencePredictionRepository::class, static fn ($c) => new SalaryIntelligencePredictionRepository($c->get(PDO::class)));
        $this->container->singleton(SalaryIntelligenceHistoryRepository::class, static fn ($c) => new SalaryIntelligenceHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(SalaryMarketSampleRepository::class, static fn ($c) => new SalaryMarketSampleRepository($c->get(PDO::class)));
        $this->container->singleton(
            SalaryIntelligencePredictionRepositoryInterface::class,
            static fn ($c) => $c->get(SalaryIntelligencePredictionRepository::class)
        );
        $this->container->singleton(
            SalaryIntelligenceHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(SalaryIntelligenceHistoryRepository::class)
        );
        $this->container->singleton(SalaryIntelligencePolicy::class, static function ($c): SalaryIntelligencePolicy {
            return new SalaryIntelligencePolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(SalaryIntelligenceValidator::class, static fn (): SalaryIntelligenceValidator => new SalaryIntelligenceValidator());
        $this->container->singleton(SalaryIntelligencePredictor::class, static fn (): SalaryIntelligencePredictor => new SalaryIntelligencePredictor());
        $this->container->singleton(SalaryIntelligencePdfExporter::class, static function ($c): SalaryIntelligencePdfExporter {
            return new SalaryIntelligencePdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(SalaryIntelligenceService::class, static function ($c): SalaryIntelligenceService {
            return new SalaryIntelligenceService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(SalaryIntelligencePredictionRepositoryInterface::class),
                $c->get(SalaryIntelligenceHistoryRepositoryInterface::class),
                $c->get(SalaryIntelligencePredictor::class),
                $c->get(SalaryIntelligencePdfExporter::class),
                $c->get(SalaryMarketSampleRepository::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumePersonalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(ResumeCertificationRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(SalaryIntelligencePolicy::class),
                $c->get(SalaryIntelligenceValidator::class),
            );
        });

        $this->container->singleton(SkillGapAnalysisRepository::class, static fn ($c) => new SkillGapAnalysisRepository($c->get(PDO::class)));
        $this->container->singleton(SkillGapHistoryRepository::class, static fn ($c) => new SkillGapHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            SkillGapAnalysisRepositoryInterface::class,
            static fn ($c) => $c->get(SkillGapAnalysisRepository::class)
        );
        $this->container->singleton(
            SkillGapHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(SkillGapHistoryRepository::class)
        );
        $this->container->singleton(SkillGapPolicy::class, static function ($c): SkillGapPolicy {
            return new SkillGapPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(SkillGapValidator::class, static fn (): SkillGapValidator => new SkillGapValidator());
        $this->container->singleton(SkillGapAnalyzer::class, static fn (): SkillGapAnalyzer => new SkillGapAnalyzer());
        $this->container->singleton(SkillGapPdfExporter::class, static function ($c): SkillGapPdfExporter {
            return new SkillGapPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(SkillGapService::class, static function ($c): SkillGapService {
            return new SkillGapService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(SkillGapHistoryRepositoryInterface::class),
                $c->get(SkillGapAnalyzer::class),
                $c->get(SkillGapPdfExporter::class),
                $c->get(JobMatchContextFactory::class),
                $c->get(JobMatchScoringService::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(SkillGapPolicy::class),
                $c->get(SkillGapValidator::class),
            );
        });

        $this->container->singleton(LearningPathRepository::class, static fn ($c) => new LearningPathRepository($c->get(PDO::class)));
        $this->container->singleton(LearningPathHistoryRepository::class, static fn ($c) => new LearningPathHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            LearningPathRepositoryInterface::class,
            static fn ($c) => $c->get(LearningPathRepository::class)
        );
        $this->container->singleton(
            LearningPathHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(LearningPathHistoryRepository::class)
        );
        $this->container->singleton(LearningPathPolicy::class, static function ($c): LearningPathPolicy {
            return new LearningPathPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(LearningPathValidator::class, static fn (): LearningPathValidator => new LearningPathValidator());
        $this->container->singleton(LearningPathAnalyzer::class, static fn (): LearningPathAnalyzer => new LearningPathAnalyzer());
        $this->container->singleton(LearningPathPdfExporter::class, static function ($c): LearningPathPdfExporter {
            return new LearningPathPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(LearningPathService::class, static function ($c): LearningPathService {
            return new LearningPathService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(LearningPathRepositoryInterface::class),
                $c->get(LearningPathHistoryRepositoryInterface::class),
                $c->get(LearningPathAnalyzer::class),
                $c->get(LearningPathPdfExporter::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(SalaryIntelligencePredictionRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(LearningPathPolicy::class),
                $c->get(LearningPathValidator::class),
            );
        });

        $this->container->singleton(PortfolioBuilderPlanRepository::class, static fn ($c) => new PortfolioBuilderPlanRepository($c->get(PDO::class)));
        $this->container->singleton(PortfolioBuilderHistoryRepository::class, static fn ($c) => new PortfolioBuilderHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            PortfolioBuilderPlanRepositoryInterface::class,
            static fn ($c) => $c->get(PortfolioBuilderPlanRepository::class)
        );
        $this->container->singleton(
            PortfolioBuilderHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(PortfolioBuilderHistoryRepository::class)
        );
        $this->container->singleton(PortfolioBuilderPolicy::class, static function ($c): PortfolioBuilderPolicy {
            return new PortfolioBuilderPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(PortfolioBuilderValidator::class, static fn (): PortfolioBuilderValidator => new PortfolioBuilderValidator());
        $this->container->singleton(PortfolioBuilderAnalyzer::class, static fn (): PortfolioBuilderAnalyzer => new PortfolioBuilderAnalyzer());
        $this->container->singleton(PortfolioBuilderPdfExporter::class, static function ($c): PortfolioBuilderPdfExporter {
            return new PortfolioBuilderPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(PortfolioBuilderService::class, static function ($c): PortfolioBuilderService {
            return new PortfolioBuilderService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(PortfolioBuilderPlanRepositoryInterface::class),
                $c->get(PortfolioBuilderHistoryRepositoryInterface::class),
                $c->get(PortfolioBuilderAnalyzer::class),
                $c->get(PortfolioBuilderPdfExporter::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(LearningPathRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeProjectRepositoryInterface::class),
                $c->get(ResumePortfolioRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(PortfolioBuilderPolicy::class),
                $c->get(PortfolioBuilderValidator::class),
            );
        });

        $this->container->singleton(MockInterviewSessionRepository::class, static fn ($c) => new MockInterviewSessionRepository($c->get(PDO::class)));
        $this->container->singleton(MockInterviewHistoryRepository::class, static fn ($c) => new MockInterviewHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            MockInterviewSessionRepositoryInterface::class,
            static fn ($c) => $c->get(MockInterviewSessionRepository::class)
        );
        $this->container->singleton(
            MockInterviewHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(MockInterviewHistoryRepository::class)
        );
        $this->container->singleton(MockInterviewPolicy::class, static function ($c): MockInterviewPolicy {
            return new MockInterviewPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(MockInterviewValidator::class, static fn (): MockInterviewValidator => new MockInterviewValidator());
        $this->container->singleton(MockInterviewAnalyzer::class, static fn (): MockInterviewAnalyzer => new MockInterviewAnalyzer());
        $this->container->singleton(MockInterviewPdfExporter::class, static function ($c): MockInterviewPdfExporter {
            return new MockInterviewPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(MockInterviewService::class, static function ($c): MockInterviewService {
            return new MockInterviewService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(MockInterviewSessionRepositoryInterface::class),
                $c->get(MockInterviewHistoryRepositoryInterface::class),
                $c->get(MockInterviewAnalyzer::class),
                $c->get(MockInterviewPdfExporter::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(PortfolioBuilderPlanRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(MockInterviewPolicy::class),
                $c->get(MockInterviewValidator::class),
            );
        });

        $this->container->singleton(JobSearchCopilotPlanRepository::class, static fn ($c) => new JobSearchCopilotPlanRepository($c->get(PDO::class)));
        $this->container->singleton(JobSearchCopilotHistoryRepository::class, static fn ($c) => new JobSearchCopilotHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            JobSearchCopilotPlanRepositoryInterface::class,
            static fn ($c) => $c->get(JobSearchCopilotPlanRepository::class)
        );
        $this->container->singleton(
            JobSearchCopilotHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(JobSearchCopilotHistoryRepository::class)
        );
        $this->container->singleton(JobSearchCopilotPolicy::class, static function ($c): JobSearchCopilotPolicy {
            return new JobSearchCopilotPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(JobSearchCopilotValidator::class, static fn (): JobSearchCopilotValidator => new JobSearchCopilotValidator());
        $this->container->singleton(JobSearchCopilotAnalyzer::class, static fn (): JobSearchCopilotAnalyzer => new JobSearchCopilotAnalyzer());
        $this->container->singleton(JobSearchCopilotPdfExporter::class, static function ($c): JobSearchCopilotPdfExporter {
            return new JobSearchCopilotPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(JobSearchCopilotService::class, static function ($c): JobSearchCopilotService {
            return new JobSearchCopilotService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(JobSearchCopilotPlanRepositoryInterface::class),
                $c->get(JobSearchCopilotHistoryRepositoryInterface::class),
                $c->get(JobSearchCopilotAnalyzer::class),
                $c->get(JobSearchCopilotPdfExporter::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(ResumeJobMatchRepositoryInterface::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(SalaryIntelligencePredictionRepositoryInterface::class),
                $c->get(PortfolioBuilderPlanRepositoryInterface::class),
                $c->get(MockInterviewSessionRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeSkillRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(JobSearchCopilotPolicy::class),
                $c->get(JobSearchCopilotValidator::class),
            );
        });

        $this->container->singleton(OfferEvaluationAnalysisRepository::class, static fn ($c) => new OfferEvaluationAnalysisRepository($c->get(PDO::class)));
        $this->container->singleton(OfferEvaluationHistoryRepository::class, static fn ($c) => new OfferEvaluationHistoryRepository($c->get(PDO::class)));
        $this->container->singleton(
            OfferEvaluationAnalysisRepositoryInterface::class,
            static fn ($c) => $c->get(OfferEvaluationAnalysisRepository::class)
        );
        $this->container->singleton(
            OfferEvaluationHistoryRepositoryInterface::class,
            static fn ($c) => $c->get(OfferEvaluationHistoryRepository::class)
        );
        $this->container->singleton(OfferEvaluationPolicy::class, static function ($c): OfferEvaluationPolicy {
            return new OfferEvaluationPolicy($c->get(ResumePolicy::class));
        });
        $this->container->singleton(OfferEvaluationValidator::class, static fn (): OfferEvaluationValidator => new OfferEvaluationValidator());
        $this->container->singleton(OfferEvaluationAnalyzer::class, static fn (): OfferEvaluationAnalyzer => new OfferEvaluationAnalyzer());
        $this->container->singleton(OfferEvaluationPdfExporter::class, static function ($c): OfferEvaluationPdfExporter {
            return new OfferEvaluationPdfExporter($c->get(CoverLetterPdfExporter::class));
        });
        $this->container->singleton(OfferEvaluationService::class, static function ($c): OfferEvaluationService {
            return new OfferEvaluationService(
                $c->get(DomainResumeRepositoryInterface::class),
                $c->get(OfferEvaluationAnalysisRepositoryInterface::class),
                $c->get(OfferEvaluationHistoryRepositoryInterface::class),
                $c->get(OfferEvaluationAnalyzer::class),
                $c->get(OfferEvaluationPdfExporter::class),
                $c->get(JobRepositoryInterface::class),
                $c->get(SalaryIntelligencePredictionRepositoryInterface::class),
                $c->get(SkillGapAnalysisRepositoryInterface::class),
                $c->get(PortfolioBuilderPlanRepositoryInterface::class),
                $c->get(MockInterviewSessionRepositoryInterface::class),
                $c->get(JobSearchCopilotPlanRepositoryInterface::class),
                $c->get(CareerCoachSessionRepositoryInterface::class),
                $c->get(ResumeIntelligenceRepositoryInterface::class),
                $c->get(ResumeProfessionalRepositoryInterface::class),
                $c->get(ResumeCompletionCalculator::class),
                $c->get(OfferEvaluationPolicy::class),
                $c->get(OfferEvaluationValidator::class),
            );
        });

        $this->container->singleton(ProfileCompletenessService::class, static function ($c): ProfileCompletenessService {
            return new ProfileCompletenessService(
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(EducationRepositoryInterface::class),
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(UserSkillRepositoryInterface::class),
                $c->get(UserLanguageRepositoryInterface::class)
            );
        });

        $this->container->singleton(ProfileService::class, static function ($c): ProfileService {
            return new ProfileService(
                $c->get(UserProfileRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(LocationRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(FileStorage::class),
                $c->get(ProfileAccess::class)
            );
        });

        $this->container->singleton(EducationService::class, static function ($c): EducationService {
            return new EducationService(
                $c->get(EducationRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(ProfileAccess::class)
            );
        });

        $this->container->singleton(ExperienceService::class, static function ($c): ExperienceService {
            return new ExperienceService(
                $c->get(WorkExperienceRepositoryInterface::class),
                $c->get(ResumeRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(ProfileAccess::class)
            );
        });

        $this->container->singleton(SkillService::class, static function ($c): SkillService {
            return new SkillService(
                $c->get(UserSkillRepositoryInterface::class),
                $c->get(SkillCatalogRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(ProfileAccess::class)
            );
        });

        $this->container->singleton(LanguageService::class, static function ($c): LanguageService {
            return new LanguageService(
                $c->get(UserLanguageRepositoryInterface::class),
                $c->get(LanguageCatalogRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(ProfileAccess::class)
            );
        });

        $this->container->singleton(CvService::class, static function ($c): CvService {
            return new CvService(
                $c->get(ResumeRepositoryInterface::class),
                $c->get(ProfileCompletenessService::class),
                $c->get(FileStorage::class),
                $c->get(ProfileAccess::class)
            );
        });
    }

    public function boot(): void
    {
    }
}
