<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Professional headline & summary section for a resume.
 */
final class ResumeProfessionalDTO
{
    public function __construct(
        public readonly int $resumeId,
        public readonly ?string $headline,
        public readonly ?string $summary,
        public readonly ?string $careerObjective,
        public readonly ?string $yearsOfExperience,
        public readonly ?string $currentJobTitle,
        public readonly ?string $currentCompany,
        public readonly ?string $industry,
        public readonly ?string $currentSalary,
        public readonly ?string $expectedSalary,
        public readonly ?string $preferredCurrency,
        public readonly ?string $noticePeriod,
        public readonly ?string $employmentStatus,
        public readonly bool $openToRelocate,
        public readonly bool $openToRemote,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @param  array<string, mixed>|null  $profileFallback
     */
    public static function fromRow(int $resumeId, ?array $row, ?array $profileFallback, bool $canEdit): self
    {
        $row ??= [];
        $profileFallback ??= [];

        $headline = self::nullStr($row['headline'] ?? null) ?? self::nullStr($profileFallback['headline'] ?? null);
        $summary = self::nullStr($row['summary'] ?? null) ?? self::nullStr($profileFallback['summary'] ?? null);
        $expected = isset($row['expected_salary']) && $row['expected_salary'] !== null && $row['expected_salary'] !== ''
            ? (string) $row['expected_salary']
            : (isset($profileFallback['expected_salary']) && $profileFallback['expected_salary'] !== null
                ? (string) $profileFallback['expected_salary']
                : null);

        return new self(
            resumeId: $resumeId,
            headline: $headline,
            summary: $summary,
            careerObjective: self::nullStr($row['career_objective'] ?? null),
            yearsOfExperience: isset($row['years_of_experience']) && $row['years_of_experience'] !== null
                ? (string) $row['years_of_experience']
                : null,
            currentJobTitle: self::nullStr($row['current_job_title'] ?? null),
            currentCompany: self::nullStr($row['current_company'] ?? null),
            industry: self::nullStr($row['industry'] ?? null),
            currentSalary: isset($row['current_salary']) && $row['current_salary'] !== null
                ? (string) $row['current_salary']
                : null,
            expectedSalary: $expected,
            preferredCurrency: self::nullStr($row['preferred_currency'] ?? null) ?? 'LKR',
            noticePeriod: self::nullStr($row['notice_period'] ?? null),
            employmentStatus: self::nullStr($row['employment_status'] ?? null),
            openToRelocate: !empty($row['open_to_relocate']),
            openToRemote: !empty($row['open_to_remote']),
            canEdit: $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'headline' => $this->headline ?? '',
            'summary' => $this->summary ?? '',
            'career_objective' => $this->careerObjective ?? '',
            'years_of_experience' => $this->yearsOfExperience ?? '',
            'current_job_title' => $this->currentJobTitle ?? '',
            'current_company' => $this->currentCompany ?? '',
            'industry' => $this->industry ?? '',
            'current_salary' => $this->currentSalary ?? '',
            'expected_salary' => $this->expectedSalary ?? '',
            'preferred_currency' => $this->preferredCurrency ?? 'LKR',
            'notice_period' => $this->noticePeriod ?? '',
            'employment_status' => $this->employmentStatus ?? '',
            'open_to_relocate' => $this->openToRelocate ? '1' : '',
            'open_to_remote' => $this->openToRemote ? '1' : '',
        ];
    }

    public function isComplete(): bool
    {
        return $this->headline !== null
            && $this->summary !== null
            && mb_strlen($this->summary) >= 40
            && $this->employmentStatus !== null
            && $this->expectedSalary !== null
            && $this->expectedSalary !== ''
            && (
                ($this->yearsOfExperience !== null && $this->yearsOfExperience !== '')
                || ($this->currentJobTitle !== null && $this->currentJobTitle !== '')
            );
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
