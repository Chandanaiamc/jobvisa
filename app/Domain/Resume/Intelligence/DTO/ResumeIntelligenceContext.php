<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Intelligence\DTO;

/**
 * Privacy-safe scoring context.
 *
 * Intentionally excludes protected / sensitive attributes:
 * date of birth, gender, nationality, marital status, religion, ethnicity,
 * disability, photo/avatar, NIC/passport, and reference email/phone.
 */
final class ResumeIntelligenceContext
{
    /**
     * @param  list<array<string, mixed>>  $education
     * @param  list<array<string, mixed>>  $experience
     * @param  list<array<string, mixed>>  $skills
     * @param  list<array<string, mixed>>  $languages
     * @param  list<array<string, mixed>>  $certifications
     * @param  list<array<string, mixed>>  $projects
     * @param  list<array<string, mixed>>  $achievements
     * @param  list<array<string, mixed>>  $publications
     * @param  list<array<string, mixed>>  $portfolio
     * @param  list<array<string, mixed>>  $references
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $resumeTitle,
        public readonly bool $hasCvFile,
        public readonly bool $hasDisplayName,
        public readonly bool $hasPhone,
        public readonly bool $hasEmail,
        public readonly bool $hasLocation,
        public readonly bool $hasHeadline,
        public readonly string $headline,
        public readonly string $summary,
        public readonly bool $hasCareerObjective,
        public readonly bool $hasCurrentRole,
        public readonly array $education,
        public readonly array $experience,
        public readonly array $skills,
        public readonly array $languages,
        public readonly array $certifications,
        public readonly array $projects,
        public readonly array $achievements,
        public readonly array $publications,
        public readonly array $portfolio,
        public readonly array $references,
    ) {
    }

    public function sectionUrl(string $section): string
    {
        $base = '/jobseeker/resumes/' . $this->resumeId;
        $map = [
            'overview' => $base,
            'personal' => $base . '/personal',
            'professional' => $base . '/professional',
            'education' => $base . '/education',
            'experience' => $base . '/experience',
            'skills' => $base . '/skills',
            'languages' => $base . '/languages',
            'certifications' => $base . '/certifications',
            'projects' => $base . '/projects',
            'achievements' => $base . '/achievements',
            'publications' => $base . '/publications',
            'portfolio' => $base . '/portfolio',
            'references' => $base . '/references',
            'intelligence' => $base . '/intelligence',
        ];

        return $map[$section] ?? $base;
    }

    public function summaryLength(): int
    {
        return mb_strlen(trim($this->summary));
    }

    public function headlineLength(): int
    {
        return mb_strlen(trim($this->headline));
    }

    /**
     * Detect measurable language in experience achievements/descriptions (digits/%).
     */
    public function experienceWithMeasurableAchievements(): int
    {
        $count = 0;
        foreach ($this->experience as $row) {
            $text = trim(
                (string) ($row['achievements'] ?? '') . ' ' .
                (string) ($row['responsibilities'] ?? '') . ' ' .
                (string) ($row['description'] ?? '')
            );
            if ($text !== '' && preg_match('/(\d|%|percent|increased|reduced|grew|saved|delivered)/i', $text)) {
                $count++;
            }
        }

        return $count;
    }

    public function completeExperienceCount(): int
    {
        $count = 0;
        foreach ($this->experience as $row) {
            $title = trim((string) ($row['job_title'] ?? ''));
            $company = trim((string) ($row['company_name'] ?? ''));
            $start = trim((string) ($row['start_date'] ?? ''));
            $body = trim((string) ($row['responsibilities'] ?? $row['description'] ?? ''));
            if ($title !== '' && $company !== '' && $start !== '' && mb_strlen($body) >= 40) {
                $count++;
            }
        }

        return $count;
    }

    public function publicFacingModuleCount(): int
    {
        $count = 0;
        foreach ([$this->projects, $this->achievements, $this->publications, $this->portfolio] as $rows) {
            foreach ($rows as $row) {
                $vis = (string) ($row['visibility'] ?? 'public');
                if (in_array($vis, ['public', 'employers'], true)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    public function referenceCountWithContactPermission(): int
    {
        $n = 0;
        foreach ($this->references as $row) {
            if (!empty($row['permission_to_contact']) && in_array((string) ($row['visibility'] ?? 'private'), ['public', 'employers'], true)) {
                $n++;
            }
        }

        return $n;
    }
}
