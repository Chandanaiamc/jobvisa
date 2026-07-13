<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicationAssistant\Services;

use JobVisa\App\Domain\ApplicationAssistant\DTO\ApplicationAnalysisDTO;
use JobVisa\App\Domain\ApplicationAssistant\Support\ApplicationAssistantVersion;
use JobVisa\App\Domain\JobMatching\DTO\JobMatchResultDTO;

/**
 * Builds application readiness analysis from match + intelligence + portfolio signals.
 */
final class ApplicationReadinessAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function analyze(
        int $userId,
        int $jobId,
        int $resumeId,
        string $jobTitle,
        string $resumeTitle,
        JobMatchResultDTO $match,
        array $context,
    ): ApplicationAnalysisDTO {
        $resumeOverall = (int) ($context['resume_overall'] ?? 0);
        $portfolioScore = $this->portfolioScore($context, $match);
        $skills = $match->skillsScore;
        $experience = $match->experienceScore;
        $education = $match->educationScore;
        $certification = $match->certificationScore;
        $matchOverall = $match->overallScore;

        // Readiness: match 45 + resume intel 25 + portfolio 15 + cert/edu blend already in match — add portfolio explicitly
        $readiness = (int) round(
            ($matchOverall * 0.45)
            + ($resumeOverall * 0.25)
            + ($skills * 0.10)
            + ($experience * 0.08)
            + ($portfolioScore * 0.12)
        );
        $readiness = max(0, min(100, $readiness));

        $explanation = $match->explanation;
        $matched = $this->stringList($explanation['matched_requirements'] ?? []);
        $missingSkills = $this->stringList($explanation['missing_required_skills'] ?? []);
        $missingPreferred = $this->stringList($explanation['missing_preferred_skills'] ?? []);
        $missingKeywords = $this->missingAtsKeywords($context, $matched, $missingSkills);

        $strengths = $this->strengths($match, $matched, $resumeOverall, $portfolioScore, $context);
        $weaknesses = $this->weaknesses($missingSkills, $missingKeywords, $experience, $education, $certification, $portfolioScore);
        $recommendations = $this->recommendations($missingSkills, $missingKeywords, $portfolioScore, $resumeId, $jobId);

        $analysis = [
            'comparison' => [
                'job_title' => $jobTitle,
                'resume_title' => $resumeTitle,
                'matched_requirements' => $matched,
                'missing_skills' => $missingSkills,
                'missing_preferred_skills' => $missingPreferred,
                'missing_ats_keywords' => $missingKeywords,
                'experience_min_years' => $context['experience_min_years'] ?? null,
                'resume_years' => $context['resume_years'] ?? null,
            ],
            'dimension_labels' => [
                'skills' => 'Skill match',
                'experience' => 'Experience match',
                'education' => 'Education match',
                'certification' => 'Certification match',
                'portfolio' => 'Portfolio / project match',
            ],
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'recommendations' => $recommendations,
            'readiness_label' => $this->readinessLabel($readiness),
            'integrations' => [
                'resume_builder' => '/jobseeker/resumes/' . $resumeId . '/ai-builder',
                'cover_letter' => '/jobseeker/resumes/' . $resumeId . '/cover-letters',
                'job_match' => '/jobseeker/resumes/' . $resumeId . '/jobs/' . $jobId . '/match',
            ],
        ];

        return new ApplicationAnalysisDTO(
            0,
            $userId,
            $jobId,
            $resumeId,
            $jobTitle,
            $resumeTitle,
            $readiness,
            $skills,
            $experience,
            $education,
            $certification,
            $portfolioScore,
            $matchOverall,
            $resumeOverall,
            $analysis,
            ApplicationAssistantVersion::CURRENT,
            date('Y-m-d H:i:s'),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function portfolioScore(array $context, JobMatchResultDTO $match): int
    {
        $projects = (int) ($context['project_count'] ?? 0);
        $portfolio = (int) ($context['portfolio_count'] ?? 0);
        $achievements = (int) ($context['achievement_count'] ?? 0);
        $score = 0;
        if ($projects >= 2) {
            $score += 40;
        } elseif ($projects === 1) {
            $score += 25;
        }
        if ($portfolio >= 1) {
            $score += 30;
        }
        if ($achievements >= 2) {
            $score += 20;
        } elseif ($achievements === 1) {
            $score += 10;
        }

        $hay = mb_strtolower((string) ($context['project_blob'] ?? ''));
        $hits = 0;
        foreach ($this->stringList($match->explanation['matched_requirements'] ?? []) as $req) {
            if ($req !== '' && str_contains($hay, mb_strtolower($req))) {
                $hits++;
            }
        }
        $score += min(10, $hits * 3);

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>  $matched
     * @param  list<string>  $missingSkills
     * @return list<string>
     */
    private function missingAtsKeywords(array $context, array $matched, array $missingSkills): array
    {
        $resumeText = mb_strtolower((string) ($context['resume_text_blob'] ?? ''));
        $keywords = $this->stringList($context['requirement_keywords'] ?? []);
        $missing = $missingSkills;
        foreach ($keywords as $kw) {
            if ($kw === '' || mb_strlen($kw) < 3) {
                continue;
            }
            if (!str_contains($resumeText, mb_strtolower($kw)) && !in_array($kw, $matched, true)) {
                $missing[] = $kw;
            }
        }

        return array_values(array_unique(array_slice($missing, 0, 12)));
    }

    /**
     * @param  list<string>  $matched
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function strengths(
        JobMatchResultDTO $match,
        array $matched,
        int $resumeOverall,
        int $portfolioScore,
        array $context,
    ): array {
        $out = [];
        if ($match->overallScore >= 60) {
            $out[] = 'Strong overall job-match signal (' . $match->overallScore . '/100).';
        }
        if ($match->skillsScore >= 65) {
            $out[] = 'Skills profile aligns well with the posting (' . $match->skillsScore . '/100).';
        }
        if ($matched !== []) {
            $out[] = 'Matched requirements include: ' . implode(', ', array_slice($matched, 0, 5)) . '.';
        }
        if ($resumeOverall >= 55) {
            $out[] = 'Resume intelligence readiness is solid (' . $resumeOverall . '/100).';
        }
        if ($portfolioScore >= 50) {
            $out[] = 'Portfolio/projects provide supporting evidence for this application.';
        }
        if ($match->experienceScore >= 60) {
            $out[] = 'Experience depth appears competitive for this role.';
        }
        if ($out === []) {
            $out[] = 'You have an active resume ready to refine against this job before applying.';
        }

        return $out;
    }

    /**
     * @param  list<string>  $missingSkills
     * @param  list<string>  $missingKeywords
     * @return list<string>
     */
    private function weaknesses(
        array $missingSkills,
        array $missingKeywords,
        int $experience,
        int $education,
        int $certification,
        int $portfolioScore,
    ): array {
        $out = [];
        if ($missingSkills !== []) {
            $out[] = 'Missing required skills: ' . implode(', ', array_slice($missingSkills, 0, 5)) . '.';
        }
        if ($missingKeywords !== []) {
            $out[] = 'ATS keyword gaps detected: ' . implode(', ', array_slice($missingKeywords, 0, 5)) . '.';
        }
        if ($experience < 50) {
            $out[] = 'Experience match is below a comfortable bar (' . $experience . '/100).';
        }
        if ($education < 45) {
            $out[] = 'Education alignment may need clarification for screeners.';
        }
        if ($certification < 40) {
            $out[] = 'Certifications are light relative to role expectations.';
        }
        if ($portfolioScore < 35) {
            $out[] = 'Portfolio/project evidence is limited for this application.';
        }
        if ($out === []) {
            $out[] = 'No major automated red flags — still verify examples before applying.';
        }

        return $out;
    }

    /**
     * @param  list<string>  $missingSkills
     * @param  list<string>  $missingKeywords
     * @return list<array{priority: string, action: string, link?: string}>
     */
    private function recommendations(
        array $missingSkills,
        array $missingKeywords,
        int $portfolioScore,
        int $resumeId,
        int $jobId,
    ): array {
        $recs = [];
        if ($missingSkills !== [] || $missingKeywords !== []) {
            $recs[] = [
                'priority' => 'high',
                'action' => 'Use AI Resume Builder to weave missing skills/keywords into summary and bullets.',
                'link' => '/jobseeker/resumes/' . $resumeId . '/ai-builder',
            ];
        }
        $recs[] = [
            'priority' => 'high',
            'action' => 'Generate a tailored cover letter for this job before applying.',
            'link' => '/jobseeker/resumes/' . $resumeId . '/cover-letters',
        ];
        if ($portfolioScore < 50) {
            $recs[] = [
                'priority' => 'medium',
                'action' => 'Add at least one project or portfolio item that mirrors the job requirements.',
                'link' => '/jobseeker/resumes/' . $resumeId . '/projects',
            ];
        }
        $recs[] = [
            'priority' => 'medium',
            'action' => 'Review the detailed job-match breakdown for this resume/job pair.',
            'link' => '/jobseeker/resumes/' . $resumeId . '/jobs/' . $jobId . '/match',
        ];
        $recs[] = [
            'priority' => 'low',
            'action' => 'Recalculate this analysis after improving your resume or cover letter.',
        ];

        return $recs;
    }

    private function readinessLabel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Ready to apply',
            $score >= 55 => 'Almost ready',
            $score >= 35 => 'Needs improvement',
            default => 'Not ready yet',
        };
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }
}
