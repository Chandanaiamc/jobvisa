<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\SkillGap\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchResultDTO;
use JobVisa\App\Domain\SkillGap\DTO\SkillGapAnalysisDTO;
use JobVisa\App\Domain\SkillGap\Support\SkillGapVersion;

/**
 * Deterministic skill-gap analysis — no external AI APIs.
 */
final class SkillGapAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function analyze(
        int $resumeId,
        int $userId,
        int $jobId,
        string $jobTitle,
        JobMatchResultDTO $match,
        array $context,
    ): SkillGapAnalysisDTO {
        $explanation = $match->explanation;
        $matched = $this->stringList($explanation['matched_requirements'] ?? []);
        $missingRequired = $this->stringList($explanation['missing_required_skills'] ?? []);
        $missingPreferred = $this->stringList($explanation['missing_preferred_skills'] ?? []);
        $missing = array_values(array_unique(array_merge($missingRequired, $missingPreferred)));

        $skillsScore = $match->skillsScore;
        $gapPercentage = max(0, min(100, 100 - $skillsScore));
        $readiness = max(0, min(100, (int) round(
            ($skillsScore * 0.55)
            + ($match->overallScore * 0.25)
            + ((int) ($context['resume_overall'] ?? 0) * 0.20)
        )));

        $strengths = $this->strengths($matched, $skillsScore, $match, $context);
        $weaknesses = $this->weaknesses($missingRequired, $missingPreferred, $gapPercentage, $match);
        $priority = $this->priorityOrder($missingRequired, $missingPreferred);
        $roadmap = $this->roadmap($priority);
        $certs = $this->certifications($priority, $jobTitle);
        $courses = $this->courses($priority);

        $analysis = [
            'comparison' => [
                'job_title' => $jobTitle,
                'resume_title' => (string) ($context['resume_title'] ?? ''),
                'matched_skills' => $matched,
                'missing_skills' => $missing,
                'missing_required' => $missingRequired,
                'missing_preferred' => $missingPreferred,
            ],
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'priority_learning_order' => $priority,
            'learning_roadmap' => $roadmap,
            'recommended_certifications' => $certs,
            'recommended_courses' => $courses,
            'explanation' => $this->explanation($jobTitle, $skillsScore, $gapPercentage, $readiness, count($matched), count($missing)),
            'readiness_label' => $this->readinessLabel($readiness),
            'gap_label' => $this->gapLabel($gapPercentage),
        ];

        return new SkillGapAnalysisDTO(
            0,
            $resumeId,
            $userId,
            $jobId,
            $jobTitle,
            $gapPercentage,
            $readiness,
            $skillsScore,
            count($matched),
            count($missing),
            $analysis,
            SkillGapVersion::CURRENT,
            '',
        );
    }

    /**
     * @param  list<string>  $matched
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function strengths(array $matched, int $skillsScore, JobMatchResultDTO $match, array $context): array
    {
        $out = [];
        if ($matched !== []) {
            $out[] = 'Strong overlap on: ' . implode(', ', array_slice($matched, 0, 5));
        }
        if ($skillsScore >= 70) {
            $out[] = 'Skill match is competitive for this role.';
        }
        if ($match->experienceScore >= 70) {
            $out[] = 'Experience level aligns well with the job.';
        }
        if ($match->certificationScore >= 60) {
            $out[] = 'Certifications support the target role.';
        }
        if ((int) ($context['resume_overall'] ?? 0) >= 70) {
            $out[] = 'Resume intelligence score supports readiness.';
        }
        if ($out === []) {
            $out[] = 'Foundation skills present — expand depth on missing requirements.';
        }

        return $out;
    }

    /**
     * @param  list<string>  $missingRequired
     * @param  list<string>  $missingPreferred
     * @return list<string>
     */
    private function weaknesses(array $missingRequired, array $missingPreferred, int $gap, JobMatchResultDTO $match): array
    {
        $out = [];
        if ($missingRequired !== []) {
            $out[] = 'Critical gaps: ' . implode(', ', array_slice($missingRequired, 0, 5));
        }
        if ($missingPreferred !== []) {
            $out[] = 'Preferred skills not yet evidenced: ' . implode(', ', array_slice($missingPreferred, 0, 4));
        }
        if ($gap >= 50) {
            $out[] = 'Skill gap is significant — prioritize a focused learning plan before applying.';
        }
        if ($match->educationScore < 50) {
            $out[] = 'Education signal is below typical expectations for this posting.';
        }
        if ($out === []) {
            $out[] = 'No major skill weaknesses detected for this comparison.';
        }

        return $out;
    }

    /**
     * @param  list<string>  $required
     * @param  list<string>  $preferred
     * @return list<array{skill: string, priority: string, reason: string}>
     */
    private function priorityOrder(array $required, array $preferred): array
    {
        $out = [];
        $i = 1;
        foreach ($required as $skill) {
            $out[] = [
                'skill' => $skill,
                'priority' => 'P' . min(3, $i),
                'reason' => 'Required for the target job',
            ];
            $i++;
            if (count($out) >= 8) {
                break;
            }
        }
        foreach ($preferred as $skill) {
            if (count($out) >= 10) {
                break;
            }
            $out[] = [
                'skill' => $skill,
                'priority' => 'P3',
                'reason' => 'Preferred differentiator',
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{skill: string, priority: string, reason: string}>  $priority
     * @return list<array{phase: string, focus: string, weeks: int, skills: list<string>}>
     */
    private function roadmap(array $priority): array
    {
        if ($priority === []) {
            return [[
                'phase' => 'Maintain',
                'focus' => 'Keep current skills sharp and document measurable outcomes',
                'weeks' => 2,
                'skills' => [],
            ]];
        }

        $chunks = array_chunk($priority, 3);
        $phases = [];
        $week = 1;
        foreach ($chunks as $idx => $chunk) {
            $skills = array_map(static fn (array $r): string => $r['skill'], $chunk);
            $phases[] = [
                'phase' => 'Phase ' . ($idx + 1),
                'focus' => 'Close gaps: ' . implode(', ', array_slice($skills, 0, 3)),
                'weeks' => 2 + $idx,
                'skills' => $skills,
            ];
            $week += 2 + $idx;
            if (count($phases) >= 4) {
                break;
            }
        }

        return $phases;
    }

    /**
     * @param  list<array{skill: string, priority: string, reason: string}>  $priority
     * @return list<array{name: string, maps_to: string, why: string}>
     */
    private function certifications(array $priority, string $jobTitle): array
    {
        $certs = [];
        $title = mb_strtolower($jobTitle);
        if (str_contains($title, 'nurs') || str_contains($title, 'health') || str_contains($title, 'medical')) {
            $certs[] = ['name' => 'BLS / ACLS refresher', 'maps_to' => 'Clinical readiness', 'why' => 'Common healthcare hiring signal'];
            $certs[] = ['name' => 'Specialty nursing credential', 'maps_to' => 'Domain credibility', 'why' => 'Strengthens clinical skill narrative'];
        }
        if (str_contains($title, 'develop') || str_contains($title, 'engineer') || str_contains($title, 'software')) {
            $certs[] = ['name' => 'Cloud practitioner (AWS / Azure)', 'maps_to' => 'Cloud skills', 'why' => 'Frequent tech job requirement'];
        }
        if (str_contains($title, 'project') || str_contains($title, 'manager')) {
            $certs[] = ['name' => 'PMP or CAPM', 'maps_to' => 'Project delivery', 'why' => 'Signals structured delivery capability'];
        }

        foreach (array_slice($priority, 0, 4) as $row) {
            $skill = $row['skill'];
            $certs[] = [
                'name' => 'Credential related to ' . $skill,
                'maps_to' => $skill,
                'why' => 'Directly addresses a listed gap',
            ];
            if (count($certs) >= 5) {
                break;
            }
        }

        if ($certs === []) {
            $certs[] = ['name' => 'Role-relevant professional certificate', 'maps_to' => 'General readiness', 'why' => 'Adds verifiable proof for employers'];
        }

        return array_slice($certs, 0, 5);
    }

    /**
     * @param  list<array{skill: string, priority: string, reason: string}>  $priority
     * @return list<array{title: string, provider: string, skill: string, level: string}>
     */
    private function courses(array $priority): array
    {
        $providers = ['Coursera', 'edX', 'Udemy', 'LinkedIn Learning', 'Google Career Certificates'];
        $courses = [];
        foreach (array_slice($priority, 0, 6) as $i => $row) {
            $skill = $row['skill'];
            $courses[] = [
                'title' => 'Practical ' . $skill . ' essentials',
                'provider' => $providers[$i % count($providers)],
                'skill' => $skill,
                'level' => $row['priority'] === 'P1' ? 'Priority' : 'Intermediate',
            ];
        }
        if ($courses === []) {
            $courses[] = [
                'title' => 'Role readiness fundamentals',
                'provider' => 'Coursera',
                'skill' => 'General',
                'level' => 'Foundational',
            ];
        }

        return $courses;
    }

    private function explanation(
        string $jobTitle,
        int $skillsScore,
        int $gap,
        int $readiness,
        int $matchedCount,
        int $missingCount,
    ): string {
        return sprintf(
            'Compared to %s, you match %d skill signal(s) with a skill score of %d/100. Gap is %d%% and career readiness is %d/100, with %d missing skill(s) prioritized for learning.',
            $jobTitle !== '' ? $jobTitle : 'the target job',
            $matchedCount,
            $skillsScore,
            $gap,
            $readiness,
            $missingCount
        );
    }

    private function readinessLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Ready to apply',
            $score >= 60 => 'Nearly ready',
            $score >= 40 => 'Needs focused upskilling',
            default => 'Significant preparation needed',
        };
    }

    private function gapLabel(int $gap): string
    {
        return match (true) {
            $gap <= 20 => 'Low gap',
            $gap <= 40 => 'Moderate gap',
            $gap <= 60 => 'High gap',
            default => 'Critical gap',
        };
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $s = trim((string) $item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }
}
