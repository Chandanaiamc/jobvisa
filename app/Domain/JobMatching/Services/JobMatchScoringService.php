<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchContext;
use JobVisa\App\Domain\JobMatching\DTO\JobMatchResultDTO;
use JobVisa\App\Domain\JobMatching\DTO\JobRequirementSignals;
use JobVisa\App\Domain\JobMatching\Support\EducationLevelNormalizer;
use JobVisa\App\Domain\JobMatching\Support\MatchRulesVersion;

/**
 * Deterministic weighted match scoring (weights sum to 100).
 */
final class JobMatchScoringService
{
    public const WEIGHT_SKILLS = 35;
    public const WEIGHT_EXPERIENCE = 20;
    public const WEIGHT_EDUCATION = 15;
    public const WEIGHT_LANGUAGE = 10;
    public const WEIGHT_CERTIFICATION = 10;
    public const WEIGHT_LOCATION = 10;

    public function __construct(
        private readonly JobRequirementExtractor $extractor,
        private readonly JobMatchExplanationService $explanations,
    ) {
    }

    public function score(JobMatchContext $context): JobMatchResultDTO
    {
        $signals = $this->extractor->extract($context);

        $skills = $this->scoreSkills($context, $signals);
        $experience = $this->scoreExperience($context, $signals);
        $education = $this->scoreEducation($context, $signals);
        $language = $this->scoreLanguages($context, $signals);
        $certification = $this->scoreCertifications($context, $signals);
        $location = $this->scoreLocation($context, $signals);

        $breakdown = [
            'skills' => $skills['breakdown'],
            'experience' => $experience['breakdown'],
            'education' => $education['breakdown'],
            'language' => $language['breakdown'],
            'certification' => $certification['breakdown'],
            'location' => $location['breakdown'],
        ];

        $overall = 0;
        foreach ($breakdown as $row) {
            $overall += (int) $row['earned'];
        }
        $overall = max(0, min(100, $overall));

        $pack = $this->explanations->build(
            $context,
            $signals,
            [
                'skills' => $skills,
                'experience' => $experience,
                'education' => $education,
                'language' => $language,
                'certification' => $certification,
                'location' => $location,
            ],
            $overall,
        );

        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s.v');

        return new JobMatchResultDTO(
            resumeId: $context->resumeId,
            jobId: $context->jobId,
            jobTitle: $context->jobTitle,
            overallScore: $overall,
            skillsScore: (int) $skills['score'],
            experienceScore: (int) $experience['score'],
            educationScore: (int) $education['score'],
            languageScore: (int) $language['score'],
            certificationScore: (int) $certification['score'],
            locationScore: (int) $location['score'],
            breakdown: $breakdown,
            explanation: $pack['explanation'],
            recommendations: $pack['recommendations'],
            rulesVersion: MatchRulesVersion::CURRENT,
            calculatedAt: $now,
        );
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>, matched: list<string>, missing_required: list<string>, missing_preferred: list<string>}
     */
    private function scoreSkills(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        $owned = [];
        foreach ($context->resumeSkills as $row) {
            $name = mb_strtolower(trim($row['name']));
            if ($name !== '') {
                $owned[$name] = $name;
            }
        }

        $required = $signals->requiredSkills;
        $preferred = $signals->preferredSkills;
        $targets = array_values(array_unique([...$required, ...$preferred]));

        if ($targets === []) {
            $score = 70; // neutral when job has no extractable skills
            return [
                'score' => $score,
                'breakdown' => $this->category('Skills match', self::WEIGHT_SKILLS, $score, 'No structured skill requirements extracted; neutral score applied.'),
                'matched' => [],
                'missing_required' => [],
                'missing_preferred' => [],
            ];
        }

        $matched = [];
        $missingReq = [];
        $missingPref = [];
        foreach ($required as $skill) {
            if ($this->hasTerm($skill, $owned)) {
                $matched[] = $skill;
            } else {
                $missingReq[] = $skill;
            }
        }
        foreach ($preferred as $skill) {
            if ($this->hasTerm($skill, $owned)) {
                $matched[] = $skill;
            } else {
                $missingPref[] = $skill;
            }
        }
        $matched = array_values(array_unique($matched));

        $reqTotal = max(1, count($required));
        $reqHit = count($required) - count($missingReq);
        $prefTotal = count($preferred);
        $prefHit = $prefTotal - count($missingPref);

        $score = (int) round(($reqHit / $reqTotal) * 80);
        if ($prefTotal > 0) {
            $score += (int) round(($prefHit / $prefTotal) * 20);
        } else {
            $score += 20;
        }
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'breakdown' => $this->category(
                'Skills match',
                self::WEIGHT_SKILLS,
                $score,
                sprintf('Matched %d of %d required and %d of %d preferred skill signals.', $reqHit, count($required), max(0, $prefHit), $prefTotal)
            ),
            'matched' => $matched,
            'missing_required' => $missingReq,
            'missing_preferred' => $missingPref,
        ];
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>}
     */
    private function scoreExperience(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        $required = $signals->inferredMinExperience ?? $context->experienceMinYears;
        $have = $context->resumeYearsExperience;

        if ($required === null || $required < 1) {
            $score = 100;
            $explain = 'No minimum experience stated on the job.';
        } elseif ($have === null) {
            $score = 40;
            $explain = 'Job asks for experience but resume years are not set.';
        } elseif ($have >= $required) {
            $score = 100;
            $explain = sprintf('Resume shows %d years; job asks for %d+.', $have, $required);
        } else {
            $ratio = $have / max(1, $required);
            $score = (int) round(max(0, min(95, $ratio * 100)));
            $explain = sprintf('Resume shows %d years; job asks for %d+.', $have, $required);
        }

        return [
            'score' => $score,
            'breakdown' => $this->category('Experience match', self::WEIGHT_EXPERIENCE, $score, $explain),
        ];
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>}
     */
    private function scoreEducation(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        $needed = EducationLevelNormalizer::rank($signals->inferredEducation ?? $context->educationLevel);
        $best = 0;
        foreach ($context->resumeEducation as $row) {
            $best = max(
                $best,
                EducationLevelNormalizer::rank($row['qualification_type'] ?? ''),
                EducationLevelNormalizer::rank($row['degree'] ?? '')
            );
        }

        if ($needed < 1) {
            $score = 100;
            $explain = 'No education level requirement detected.';
        } elseif ($best < 1) {
            $score = 30;
            $explain = 'Job expects education but resume education is empty.';
        } elseif ($best >= $needed) {
            $score = 100;
            $explain = sprintf(
                'Resume education (%s) meets or exceeds job expectation (%s).',
                EducationLevelNormalizer::label($best),
                EducationLevelNormalizer::label($needed)
            );
        } else {
            $score = (int) round(($best / $needed) * 85);
            $explain = sprintf(
                'Resume education (%s) is below job expectation (%s).',
                EducationLevelNormalizer::label($best),
                EducationLevelNormalizer::label($needed)
            );
        }

        return [
            'score' => $score,
            'breakdown' => $this->category('Education match', self::WEIGHT_EDUCATION, $score, $explain),
        ];
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>, matched: list<string>, missing: list<string>}
     */
    private function scoreLanguages(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        $targets = array_values(array_unique([...$signals->requiredLanguages, ...$signals->preferredLanguages]));
        $owned = [];
        foreach ($context->resumeLanguages as $row) {
            $n = mb_strtolower(trim($row['name']));
            if ($n !== '') {
                $owned[$n] = $n;
            }
        }

        if ($targets === []) {
            return [
                'score' => 100,
                'breakdown' => $this->category('Language match', self::WEIGHT_LANGUAGE, 100, 'No language requirements detected.'),
                'matched' => [],
                'missing' => [],
            ];
        }

        $matched = [];
        $missing = [];
        foreach ($targets as $lang) {
            if ($this->hasTerm($lang, $owned)) {
                $matched[] = $lang;
            } else {
                $missing[] = $lang;
            }
        }
        $score = (int) round((count($matched) / count($targets)) * 100);

        return [
            'score' => $score,
            'breakdown' => $this->category(
                'Language match',
                self::WEIGHT_LANGUAGE,
                $score,
                sprintf('Matched %d of %d language signals.', count($matched), count($targets))
            ),
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>, matched: list<string>, missing: list<string>}
     */
    private function scoreCertifications(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        $targets = array_values(array_unique([...$signals->requiredCertifications, ...$signals->preferredCertifications]));
        $ownedBlob = mb_strtolower(implode(' ', $context->resumeCertifications));

        if ($targets === []) {
            return [
                'score' => 100,
                'breakdown' => $this->category('Certification match', self::WEIGHT_CERTIFICATION, 100, 'No certification requirements detected.'),
                'matched' => [],
                'missing' => [],
            ];
        }

        $matched = [];
        $missing = [];
        foreach ($targets as $cert) {
            if (str_contains($ownedBlob, mb_strtolower($cert))) {
                $matched[] = $cert;
            } else {
                $missing[] = $cert;
            }
        }
        $score = (int) round((count($matched) / count($targets)) * 100);

        return [
            'score' => $score,
            'breakdown' => $this->category(
                'Certification match',
                self::WEIGHT_CERTIFICATION,
                $score,
                sprintf('Matched %d of %d certification signals.', count($matched), count($targets))
            ),
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{score: int, breakdown: array<string, mixed>}
     */
    private function scoreLocation(JobMatchContext $context, JobRequirementSignals $signals): array
    {
        if ($signals->mentionsRemote && $context->openToRemote) {
            return [
                'score' => 100,
                'breakdown' => $this->category('Location / preference', self::WEIGHT_LOCATION, 100, 'Remote-friendly job aligns with open-to-remote preference.'),
            ];
        }

        $jobCountry = $context->jobCountryId;
        $preferred = $context->preferredCountryIds;
        $profile = $context->profileCountryId;

        if ($jobCountry > 0 && in_array($jobCountry, $preferred, true)) {
            $score = 100;
            $explain = 'Job country is in your preferred countries.';
        } elseif ($jobCountry > 0 && $profile !== null && $profile === $jobCountry) {
            $score = 90;
            $explain = 'Job country matches your profile location.';
        } elseif ($context->openToRelocate) {
            $score = 70;
            $explain = 'You are open to relocate; location treated as compatible.';
        } elseif ($signals->mentionsRemote) {
            $score = 60;
            $explain = 'Job mentions remote work but open-to-remote is not set.';
        } elseif ($jobCountry < 1) {
            $score = 80;
            $explain = 'Job location not fully specified.';
        } else {
            $score = 35;
            $explain = sprintf(
                'Job is in %s; not in preferred countries and relocate preference is off.',
                $context->jobCountryName !== '' ? $context->jobCountryName : 'another country'
            );
        }

        return [
            'score' => $score,
            'breakdown' => $this->category('Location / preference', self::WEIGHT_LOCATION, $score, $explain),
        ];
    }

    /**
     * @param  array<string, string>  $owned
     */
    private function hasTerm(string $needle, array $owned): bool
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }
        if (isset($owned[$needle])) {
            return true;
        }
        foreach ($owned as $term) {
            if (str_contains($term, $needle) || str_contains($needle, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{label: string, weight: int, earned: int, score: int, explain: string}
     */
    private function category(string $label, int $weight, int $score, string $explain): array
    {
        $score = max(0, min(100, $score));
        $earned = (int) round(($score / 100) * $weight);

        return [
            'label' => $label,
            'weight' => $weight,
            'earned' => $earned,
            'score' => $score,
            'explain' => $explain,
        ];
    }
}
