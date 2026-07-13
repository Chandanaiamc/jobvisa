<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\Services;

use JobVisa\App\Domain\ApplicantRanking\DTO\RankedApplicantDTO;
use JobVisa\App\Domain\ApplicantRanking\Support\RankingRulesVersion;
use JobVisa\App\Domain\JobMatching\Services\JobMatchContextFactory;
use JobVisa\App\Domain\JobMatching\Services\JobMatchScoringService;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePortfolioRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeReferenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;

/**
 * Deterministic applicant ranking (weights sum to 100).
 * Reuses intelligence + job-match scores; no external AI APIs.
 */
final class ApplicantRankingScoringService
{
    public const W_RESUME = 20;
    public const W_MATCH = 30;
    public const W_SKILLS = 12;
    public const W_EXPERIENCE = 10;
    public const W_EDUCATION = 8;
    public const W_CERTIFICATION = 7;
    public const W_PORTFOLIO = 7;
    public const W_REFERENCES = 6;

    public function __construct(
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly JobMatchContextFactory $matchContexts,
        private readonly JobMatchScoringService $matchScoring,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly ResumePortfolioRepositoryInterface $portfolio,
        private readonly ResumeReferenceRepositoryInterface $references,
    ) {
    }

    /**
     * @param  array<string, mixed>  $application  detailed application row
     */
    public function scoreApplicant(array $application, int $jobId, bool $ensureMatch = true): RankedApplicantDTO
    {
        $applicationId = (int) ($application['id'] ?? 0);
        $userId = (int) ($application['user_id'] ?? 0);
        $resumeId = isset($application['resume_id']) && $application['resume_id'] !== null
            ? (int) $application['resume_id']
            : null;

        $resumeScore = 0;
        $matchOverall = 0;
        $skillsScore = 0;
        $experienceScore = 0;
        $educationScore = 0;
        $certScore = 0;
        $portfolioScore = 0;
        $referencesScore = 0;

        $reasons = [];
        $strengths = [];
        $gaps = [];

        if ($resumeId !== null && $resumeId > 0) {
            $intel = $this->intelligence->findLatestByResumeId($resumeId);
            $resumeScore = max(0, min(100, (int) ($intel['overall_score'] ?? 0)));
            $reasons[] = 'Resume intelligence overall score: ' . $resumeScore . '/100.';
            if ($resumeScore >= 70) {
                $strengths[] = 'Strong resume intelligence score';
            } elseif ($resumeScore < 40) {
                $gaps[] = 'Resume intelligence score needs improvement';
            }

            $matchRow = $this->matches->findByResumeAndJob($resumeId, $jobId);
            if ($matchRow === null && $ensureMatch) {
                try {
                    $ctx = $this->matchContexts->build($resumeId, $userId, $jobId);
                    $dto = $this->matchScoring->score($ctx);
                    $this->matches->upsert($resumeId, $jobId, $dto->toPersistPayload());
                    $matchOverall = $dto->overallScore;
                    $skillsScore = $dto->skillsScore;
                    $experienceScore = $dto->experienceScore;
                    $educationScore = $dto->educationScore;
                    $certScore = $dto->certificationScore;
                } catch (\Throwable) {
                    // Fall through to local heuristics
                }
            } elseif ($matchRow !== null) {
                $matchOverall = max(0, min(100, (int) ($matchRow['overall_score'] ?? 0)));
                $skillsScore = max(0, min(100, (int) ($matchRow['skills_score'] ?? 0)));
                $experienceScore = max(0, min(100, (int) ($matchRow['experience_score'] ?? 0)));
                $educationScore = max(0, min(100, (int) ($matchRow['education_score'] ?? 0)));
                $certScore = max(0, min(100, (int) ($matchRow['certification_score'] ?? 0)));
            }

            $reasons[] = 'Job match score: ' . $matchOverall . '/100.';

            if ($skillsScore === 0) {
                $skillsScore = $this->heuristicSkills($resumeId);
            }
            if ($certScore === 0) {
                $certScore = $this->heuristicCerts($resumeId);
            }
            $portfolioScore = $this->heuristicPortfolio($resumeId);
            $referencesScore = $this->heuristicReferences($resumeId);
        } else {
            $reasons[] = 'No resume attached to application; scores defaulted low.';
            $gaps[] = 'Missing resume on application';
        }

        $breakdown = [
            'resume' => $this->cat('Resume score', self::W_RESUME, $resumeScore, 'From resume intelligence overall.'),
            'job_match' => $this->cat('AI job match', self::W_MATCH, $matchOverall, 'From resume↔job match engine.'),
            'skills' => $this->cat('Skills', self::W_SKILLS, $skillsScore, 'Match skills category / resume skills depth.'),
            'experience' => $this->cat('Experience', self::W_EXPERIENCE, $experienceScore, 'Match experience category.'),
            'education' => $this->cat('Education', self::W_EDUCATION, $educationScore, 'Match education category.'),
            'certifications' => $this->cat('Certifications', self::W_CERTIFICATION, $certScore, 'Match certs / resume certifications.'),
            'portfolio' => $this->cat('Portfolio', self::W_PORTFOLIO, $portfolioScore, 'Public/employer-visible portfolio items.'),
            'references' => $this->cat('References', self::W_REFERENCES, $referencesScore, 'References with contact permission.'),
        ];

        $overall = 0;
        foreach ($breakdown as $row) {
            $overall += (int) $row['earned'];
        }
        $overall = max(0, min(100, $overall));

        if ($portfolioScore >= 70) {
            $strengths[] = 'Portfolio evidence present';
        }
        if ($referencesScore >= 70) {
            $strengths[] = 'References available';
        }
        if ($matchOverall < 40 && $resumeId) {
            $gaps[] = 'Low job-match alignment';
        }

        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.v');
        $name = trim((string) ($application['applicant_name'] ?? $application['full_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($application['email'] ?? 'Applicant');
        }

        return new RankedApplicantDTO(
            jobId: $jobId,
            applicationId: $applicationId,
            resumeId: $resumeId,
            applicantUserId: $userId,
            applicantName: $name,
            applicantEmail: (string) ($application['email'] ?? ''),
            applicationStatus: (string) ($application['status'] ?? 'submitted'),
            appliedAt: (string) ($application['applied_at'] ?? ''),
            rankPosition: 0,
            overallScore: $overall,
            resumeScore: $resumeScore,
            jobMatchScore: $matchOverall,
            skillsScore: $skillsScore,
            experienceScore: $experienceScore,
            educationScore: $educationScore,
            certificationScore: $certScore,
            portfolioScore: $portfolioScore,
            referencesScore: $referencesScore,
            breakdown: $breakdown,
            explanation: [
                'applicant_name' => $name,
                'applicant_email' => (string) ($application['email'] ?? ''),
                'application_status' => (string) ($application['status'] ?? ''),
                'applied_at' => (string) ($application['applied_at'] ?? ''),
                'reasons' => $reasons,
                'strengths' => $strengths,
                'gaps' => $gaps,
            ],
            rulesVersion: RankingRulesVersion::CURRENT,
            calculatedAt: $now,
        );
    }

    private function heuristicSkills(int $resumeId): int
    {
        $rows = $this->skills->listByResumeId($resumeId);
        $n = count($rows);
        if ($n === 0) {
            return 0;
        }
        if ($n >= 8) {
            return 90;
        }
        if ($n >= 5) {
            return 75;
        }
        if ($n >= 3) {
            return 55;
        }

        return 35;
    }

    private function heuristicCerts(int $resumeId): int
    {
        $n = count($this->certifications->listByResumeId($resumeId));
        if ($n === 0) {
            return 20;
        }
        if ($n >= 3) {
            return 95;
        }
        if ($n === 2) {
            return 80;
        }

        return 65;
    }

    private function heuristicPortfolio(int $resumeId): int
    {
        $page = $this->portfolio->listByResumeId($resumeId, [], 1, 20);
        $items = $page['items'] ?? (is_array($page) && array_is_list($page) ? $page : []);
        if (!is_array($items)) {
            $items = [];
        }
        $visible = 0;
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            $vis = (string) ($row['visibility'] ?? 'public');
            if (in_array($vis, ['public', 'employers'], true) && ($row['deleted_at'] ?? null) === null) {
                $visible++;
            }
        }
        if ($visible === 0) {
            return 15;
        }
        if ($visible >= 3) {
            return 95;
        }
        if ($visible === 2) {
            return 80;
        }

        return 60;
    }

    private function heuristicReferences(int $resumeId): int
    {
        $page = $this->references->listByResumeId($resumeId, [], 1, 20);
        $items = $page['items'] ?? (is_array($page) && array_is_list($page) ? $page : []);
        if (!is_array($items)) {
            $items = [];
        }
        $ok = 0;
        foreach ($items as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (!empty($row['permission_to_contact'])) {
                $ok++;
            }
        }
        if ($ok === 0) {
            return count($items) > 0 ? 40 : 10;
        }
        if ($ok >= 2) {
            return 95;
        }

        return 70;
    }

    /**
     * @return array{label: string, weight: int, earned: int, score: int, explain: string}
     */
    private function cat(string $label, int $weight, int $score, string $explain): array
    {
        $score = max(0, min(100, $score));

        return [
            'label' => $label,
            'weight' => $weight,
            'earned' => (int) round(($score / 100) * $weight),
            'score' => $score,
            'explain' => $explain,
        ];
    }
}
