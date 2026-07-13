<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\DTO;

/**
 * One ranked applicant for a job.
 */
final class RankedApplicantDTO
{
    /**
     * @param  array<string, array{label: string, weight: int, earned: int, score: int, explain: string}>  $breakdown
     * @param  array<string, mixed>  $explanation
     */
    public function __construct(
        public readonly int $jobId,
        public readonly int $applicationId,
        public readonly ?int $resumeId,
        public readonly int $applicantUserId,
        public readonly string $applicantName,
        public readonly string $applicantEmail,
        public readonly string $applicationStatus,
        public readonly string $appliedAt,
        public readonly int $rankPosition,
        public readonly int $overallScore,
        public readonly int $resumeScore,
        public readonly int $jobMatchScore,
        public readonly int $skillsScore,
        public readonly int $experienceScore,
        public readonly int $educationScore,
        public readonly int $certificationScore,
        public readonly int $portfolioScore,
        public readonly int $referencesScore,
        public readonly array $breakdown,
        public readonly array $explanation,
        public readonly string $rulesVersion,
        public readonly string $calculatedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'application_id' => $this->applicationId,
            'resume_id' => $this->resumeId,
            'applicant_user_id' => $this->applicantUserId,
            'applicant_name' => $this->applicantName,
            'applicant_email' => $this->applicantEmail,
            'application_status' => $this->applicationStatus,
            'applied_at' => $this->appliedAt,
            'rank_position' => $this->rankPosition,
            'overall_score' => $this->overallScore,
            'resume_score' => $this->resumeScore,
            'job_match_score' => $this->jobMatchScore,
            'skills_score' => $this->skillsScore,
            'experience_score' => $this->experienceScore,
            'education_score' => $this->educationScore,
            'certification_score' => $this->certificationScore,
            'portfolio_score' => $this->portfolioScore,
            'references_score' => $this->referencesScore,
            'score_breakdown' => $this->breakdown,
            'explanation' => $this->explanation,
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistPayload(): array
    {
        return [
            'resume_id' => $this->resumeId,
            'applicant_user_id' => $this->applicantUserId,
            'rank_position' => $this->rankPosition,
            'overall_score' => $this->overallScore,
            'resume_score' => $this->resumeScore,
            'job_match_score' => $this->jobMatchScore,
            'skills_score' => $this->skillsScore,
            'experience_score' => $this->experienceScore,
            'education_score' => $this->educationScore,
            'certification_score' => $this->certificationScore,
            'portfolio_score' => $this->portfolioScore,
            'references_score' => $this->referencesScore,
            'score_breakdown' => $this->breakdown,
            'explanation_json' => $this->explanation,
            'application_status' => $this->applicationStatus,
            'rules_version' => $this->rulesVersion,
            'calculated_at' => $this->calculatedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        $breakdown = self::json($row['score_breakdown'] ?? null);
        $explanation = self::json($row['explanation_json'] ?? null);

        return new self(
            jobId: (int) ($row['job_id'] ?? 0),
            applicationId: (int) ($row['application_id'] ?? 0),
            resumeId: isset($row['resume_id']) && $row['resume_id'] !== null ? (int) $row['resume_id'] : null,
            applicantUserId: (int) ($row['applicant_user_id'] ?? 0),
            applicantName: (string) ($row['applicant_name'] ?? $explanation['applicant_name'] ?? 'Applicant'),
            applicantEmail: (string) ($row['applicant_email'] ?? $explanation['applicant_email'] ?? ''),
            applicationStatus: (string) ($row['application_status'] ?? $explanation['application_status'] ?? ''),
            appliedAt: (string) ($row['applied_at'] ?? $explanation['applied_at'] ?? ''),
            rankPosition: (int) ($row['rank_position'] ?? 0),
            overallScore: self::clamp((int) ($row['overall_score'] ?? 0)),
            resumeScore: self::clamp((int) ($row['resume_score'] ?? 0)),
            jobMatchScore: self::clamp((int) ($row['job_match_score'] ?? 0)),
            skillsScore: self::clamp((int) ($row['skills_score'] ?? 0)),
            experienceScore: self::clamp((int) ($row['experience_score'] ?? 0)),
            educationScore: self::clamp((int) ($row['education_score'] ?? 0)),
            certificationScore: self::clamp((int) ($row['certification_score'] ?? 0)),
            portfolioScore: self::clamp((int) ($row['portfolio_score'] ?? 0)),
            referencesScore: self::clamp((int) ($row['references_score'] ?? 0)),
            breakdown: $breakdown,
            explanation: $explanation,
            rulesVersion: (string) ($row['rules_version'] ?? ''),
            calculatedAt: (string) ($row['calculated_at'] ?? ''),
        );
    }

    public function withRank(int $position): self
    {
        return new self(
            jobId: $this->jobId,
            applicationId: $this->applicationId,
            resumeId: $this->resumeId,
            applicantUserId: $this->applicantUserId,
            applicantName: $this->applicantName,
            applicantEmail: $this->applicantEmail,
            applicationStatus: $this->applicationStatus,
            appliedAt: $this->appliedAt,
            rankPosition: $position,
            overallScore: $this->overallScore,
            resumeScore: $this->resumeScore,
            jobMatchScore: $this->jobMatchScore,
            skillsScore: $this->skillsScore,
            experienceScore: $this->experienceScore,
            educationScore: $this->educationScore,
            certificationScore: $this->certificationScore,
            portfolioScore: $this->portfolioScore,
            referencesScore: $this->referencesScore,
            breakdown: $this->breakdown,
            explanation: $this->explanation,
            rulesVersion: $this->rulesVersion,
            calculatedAt: $this->calculatedAt,
        );
    }

    private static function clamp(int $v): int
    {
        return max(0, min(100, $v));
    }

    /**
     * @return array<string, mixed>
     */
    private static function json(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
