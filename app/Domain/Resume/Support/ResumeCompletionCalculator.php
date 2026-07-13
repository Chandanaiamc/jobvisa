<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Support;

use JobVisa\App\Domain\Resume\DTO\ResumePersonalDTO;
use JobVisa\App\Domain\Resume\DTO\ResumeProfessionalDTO;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface as InfraResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePublicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use PDO;

/**
 * Reusable resume completion calculator (weights live here, not in controllers).
 */
final class ResumeCompletionCalculator
{
    public const WEIGHT_TITLE = 8;
    public const WEIGHT_PERSONAL = 15;
    public const WEIGHT_PROFESSIONAL = 12;
    public const WEIGHT_CV_FILE = 7;
    public const WEIGHT_EDUCATION = 9;
    public const WEIGHT_EXPERIENCE = 9;
    public const WEIGHT_SKILLS = 5;
    public const WEIGHT_LANGUAGES = 5;
    public const WEIGHT_CERTIFICATIONS = 5;
    public const WEIGHT_PROJECTS = 7;
    public const WEIGHT_ACHIEVEMENTS = 5;
    public const WEIGHT_PUBLICATIONS = 5;
    public const WEIGHT_PORTFOLIO = 4;
    public const WEIGHT_REFERENCES = 4;

    public function __construct(
        private readonly InfraResumeRepositoryInterface $infraResumes,
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly ResumeProfessionalRepositoryInterface $professional,
        private readonly ResumeSkillRepositoryInterface $resumeSkills,
        private readonly ResumeLanguageRepositoryInterface $resumeLanguages,
        private readonly ResumeCertificationRepositoryInterface $resumeCertifications,
        private readonly ResumeProjectRepositoryInterface $resumeProjects,
        private readonly ResumeAchievementRepositoryInterface $resumeAchievements,
        private readonly ResumePublicationRepositoryInterface $resumePublications,
        private readonly ResumePortfolioRepositoryInterface $resumePortfolios,
        private readonly ResumeReferenceRepositoryInterface $resumeReferences,
        private readonly PDO $pdo
    ) {
    }

    /**
     * @return array{score: int, sections: array<string, array{label: string, weight: int, complete: bool, earned: int}>}
     */
    public function evaluate(
        int $userId,
        int $resumeId,
        ?ResumePersonalDTO $personal = null,
        ?ResumeProfessionalDTO $professional = null
    ): array {
        $row = $this->infraResumes->findByIdForUser($resumeId, $userId)
            ?? $this->infraResumes->findRecordById($resumeId);

        $sections = [
            'title' => [
                'label' => 'Resume title',
                'weight' => self::WEIGHT_TITLE,
                'complete' => is_array($row) && trim((string) ($row['title'] ?? '')) !== '',
                'earned' => 0,
            ],
            'personal' => [
                'label' => 'Personal information',
                'weight' => self::WEIGHT_PERSONAL,
                'complete' => $this->personalComplete($userId, $personal),
                'earned' => 0,
            ],
            'professional' => [
                'label' => 'Professional summary',
                'weight' => self::WEIGHT_PROFESSIONAL,
                'complete' => $this->professionalComplete($resumeId, $professional),
                'earned' => 0,
            ],
            'cv' => [
                'label' => 'CV file',
                'weight' => self::WEIGHT_CV_FILE,
                'complete' => is_array($row) && !empty($row['file_path']),
                'earned' => 0,
            ],
            'education' => [
                'label' => 'Education',
                'weight' => self::WEIGHT_EDUCATION,
                'complete' => $this->countChild('education', $resumeId) > 0,
                'earned' => 0,
            ],
            'experience' => [
                'label' => 'Work experience',
                'weight' => self::WEIGHT_EXPERIENCE,
                'complete' => $this->countChild('work_experience', $resumeId) > 0,
                'earned' => 0,
            ],
            'skills' => [
                'label' => 'Skills',
                'weight' => self::WEIGHT_SKILLS,
                'complete' => $this->resumeSkills->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'languages' => [
                'label' => 'Languages',
                'weight' => self::WEIGHT_LANGUAGES,
                'complete' => $this->resumeLanguages->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'certifications' => [
                'label' => 'Certifications',
                'weight' => self::WEIGHT_CERTIFICATIONS,
                'complete' => $this->resumeCertifications->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'projects' => [
                'label' => 'Projects',
                'weight' => self::WEIGHT_PROJECTS,
                'complete' => $this->resumeProjects->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'achievements' => [
                'label' => 'Achievements',
                'weight' => self::WEIGHT_ACHIEVEMENTS,
                'complete' => $this->resumeAchievements->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'publications' => [
                'label' => 'Publications',
                'weight' => self::WEIGHT_PUBLICATIONS,
                'complete' => $this->resumePublications->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'portfolio' => [
                'label' => 'Portfolio',
                'weight' => self::WEIGHT_PORTFOLIO,
                'complete' => $this->resumePortfolios->countActive($resumeId) > 0,
                'earned' => 0,
            ],
            'references' => [
                'label' => 'References',
                'weight' => self::WEIGHT_REFERENCES,
                'complete' => $this->resumeReferences->countActive($resumeId) > 0,
                'earned' => 0,
            ],
        ];

        $score = 0;

        foreach ($sections as $key => $section) {
            $earned = $section['complete'] ? $section['weight'] : 0;
            $sections[$key]['earned'] = $earned;
            $score += $earned;
        }

        $score = max(0, min(100, $score));
        $this->infraResumes->updateCompleteness($resumeId, $score);

        return ['score' => $score, 'sections' => $sections];
    }

    private function personalComplete(int $userId, ?ResumePersonalDTO $personal): bool
    {
        if ($personal !== null) {
            return $personal->firstName !== ''
                && $personal->lastName !== ''
                && $personal->dateOfBirth !== null
                && $personal->gender !== null
                && $personal->nationalityCountryId !== null
                && $personal->phone !== null
                && $personal->headline !== null;
        }

        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            return false;
        }

        return trim((string) ($profile['first_name'] ?? '')) !== ''
            && trim((string) ($profile['last_name'] ?? '')) !== ''
            && !empty($profile['date_of_birth'])
            && !empty($profile['gender'])
            && !empty($profile['nationality_country_id'])
            && !empty($profile['user_phone'])
            && !empty($profile['headline']);
    }

    private function professionalComplete(int $resumeId, ?ResumeProfessionalDTO $professional): bool
    {
        if ($professional !== null) {
            return $professional->isComplete();
        }

        $row = $this->professional->findByResumeId($resumeId);
        $dto = ResumeProfessionalDTO::fromRow($resumeId, $row, null, false);

        return $dto->isComplete();
    }

    private function countChild(string $table, int $resumeId): int
    {
        if (!in_array($table, ['education', 'work_experience'], true) || $resumeId < 1) {
            return 0;
        }

        if ($table === 'education') {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `education` WHERE `resume_id` = ? AND `deleted_at` IS NULL'
            );
            $stmt->execute([$resumeId]);

            return (int) $stmt->fetchColumn();
        }

        if ($table === 'work_experience') {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `work_experience` WHERE `resume_id` = ? AND `deleted_at` IS NULL'
            );
            $stmt->execute([$resumeId]);

            return (int) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `resume_id` = ?");
        $stmt->execute([$resumeId]);

        return (int) $stmt->fetchColumn();
    }
}
