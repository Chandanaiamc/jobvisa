<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\Services;

use JobVisa\App\Domain\JobMatching\DTO\JobMatchContext;
use JobVisa\App\Domain\JobMatching\DTO\JobRequirementSignals;
use JobVisa\App\Domain\JobMatching\DTO\MatchRecommendationDTO;

/**
 * Builds explainable match output and improvement recommendations.
 */
final class JobMatchExplanationService
{
    /**
     * @param  array<string, array<string, mixed>>  $parts
     * @return array{explanation: array<string, mixed>, recommendations: list<MatchRecommendationDTO>}
     */
    public function build(
        JobMatchContext $context,
        JobRequirementSignals $signals,
        array $parts,
        int $overall,
    ): array {
        $skills = $parts['skills'] ?? [];
        $language = $parts['language'] ?? [];
        $certification = $parts['certification'] ?? [];

        $matchedRequirements = array_values(array_unique(array_merge(
            $skills['matched'] ?? [],
            $language['matched'] ?? [],
            $certification['matched'] ?? [],
        )));

        $missingRequired = array_values(array_unique(array_merge(
            $skills['missing_required'] ?? [],
            $language['missing'] ?? [],
            $certification['missing'] ?? [],
        )));

        $missingPreferred = array_values($skills['missing_preferred'] ?? []);

        $strengths = [];
        $gaps = [];
        $reasons = [];

        foreach (['skills', 'experience', 'education', 'language', 'certification', 'location'] as $key) {
            $row = $parts[$key]['breakdown'] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $score = (int) ($row['score'] ?? 0);
            $label = (string) ($row['label'] ?? $key);
            $explain = (string) ($row['explain'] ?? '');
            $reasons[] = $label . ': ' . $explain . ' (score ' . $score . '/100, weight ' . (int) ($row['weight'] ?? 0) . ').';
            if ($score >= 75) {
                $strengths[] = $label . ' — ' . $explain;
            } elseif ($score < 55) {
                $gaps[] = $label . ' — ' . $explain;
            }
        }

        $recs = $this->recommendations($context, $missingRequired, $missingPreferred, $parts);

        return [
            'explanation' => [
                'job_title' => $context->jobTitle,
                'overall_score' => $overall,
                'matched_requirements' => $matchedRequirements,
                'missing_required_skills' => $skills['missing_required'] ?? [],
                'missing_preferred_skills' => $missingPreferred,
                'missing_required' => $missingRequired,
                'strengths' => $strengths,
                'gaps' => $gaps,
                'reasons' => $reasons,
                'signals' => [
                    'required_skills' => $signals->requiredSkills,
                    'preferred_skills' => $signals->preferredSkills,
                    'required_languages' => $signals->requiredLanguages,
                    'preferred_languages' => $signals->preferredLanguages,
                    'required_certifications' => $signals->requiredCertifications,
                    'preferred_certifications' => $signals->preferredCertifications,
                    'mentions_remote' => $signals->mentionsRemote,
                    'inferred_min_experience' => $signals->inferredMinExperience,
                    'inferred_education' => $signals->inferredEducation,
                ],
            ],
            'recommendations' => $recs,
        ];
    }

    /**
     * @param  list<string>  $missingRequired
     * @param  list<string>  $missingPreferred
     * @param  array<string, array<string, mixed>>  $parts
     * @return list<MatchRecommendationDTO>
     */
    private function recommendations(
        JobMatchContext $context,
        array $missingRequired,
        array $missingPreferred,
        array $parts,
    ): array {
        $base = '/jobseeker/resumes/' . $context->resumeId;
        $recs = [];

        if ($missingRequired !== []) {
            $sample = array_slice($missingRequired, 0, 5);
            $recs[] = new MatchRecommendationDTO(
                'MATCH_SKILLS_REQUIRED',
                'Add missing required skills',
                'This job expects skills such as: ' . implode(', ', $sample) . '. Add them only if you have genuine experience.',
                'high',
                'skills',
                min(20, count($missingRequired) * 3),
                $base . '/skills',
            );
        }

        if ($missingPreferred !== []) {
            $sample = array_slice($missingPreferred, 0, 4);
            $recs[] = new MatchRecommendationDTO(
                'MATCH_SKILLS_PREFERRED',
                'Cover preferred skills',
                'Preferred signals include: ' . implode(', ', $sample) . '.',
                'medium',
                'skills',
                min(10, count($missingPreferred) * 2),
                $base . '/skills',
            );
        }

        $expScore = (int) (($parts['experience']['score'] ?? 100));
        if ($expScore < 70) {
            $recs[] = new MatchRecommendationDTO(
                'MATCH_EXPERIENCE',
                'Strengthen experience evidence',
                'Update years of experience and detailed work history so employers can verify your tenure.',
                'high',
                'experience',
                12,
                $base . '/experience',
            );
        }

        $eduScore = (int) (($parts['education']['score'] ?? 100));
        if ($eduScore < 70) {
            $recs[] = new MatchRecommendationDTO(
                'MATCH_EDUCATION',
                'Complete education details',
                'Add or clarify qualifications that meet the job education expectation.',
                'medium',
                'education',
                8,
                $base . '/education',
            );
        }

        $locScore = (int) (($parts['location']['score'] ?? 100));
        if ($locScore < 60) {
            $recs[] = new MatchRecommendationDTO(
                'MATCH_LOCATION',
                'Align location preferences',
                'Add this job country to preferred destinations or enable open-to-relocate / open-to-remote when accurate.',
                'medium',
                'professional',
                6,
                $base . '/professional',
            );
        }

        $langMissing = $parts['language']['missing'] ?? [];
        if (is_array($langMissing) && $langMissing !== []) {
            $recs[] = new MatchRecommendationDTO(
                'MATCH_LANGUAGE',
                'Add required languages',
                'Job language signals include: ' . implode(', ', array_slice($langMissing, 0, 4)) . '.',
                'medium',
                'languages',
                6,
                $base . '/languages',
            );
        }

        $certMissing = $parts['certification']['missing'] ?? [];
        if (is_array($certMissing) && $certMissing !== []) {
            $recs[] = new MatchRecommendationDTO(
                'MATCH_CERT',
                'Add relevant certifications',
                'Consider listing: ' . implode(', ', array_slice($certMissing, 0, 4)) . ' if you hold them.',
                'medium',
                'certifications',
                6,
                $base . '/certifications',
            );
        }

        return array_slice($recs, 0, 8);
    }
}
