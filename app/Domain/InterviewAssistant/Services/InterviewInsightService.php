<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\Services;

use JobVisa\App\Domain\InterviewAssistant\DTO\InterviewGenerationContext;

/**
 * Deterministic strengths / weaknesses / interviewer recommendations.
 */
final class InterviewInsightService
{
    /**
     * @return array{strengths: list<string>, weaknesses: list<string>, recommendations: list<string>}
     */
    public function analyze(InterviewGenerationContext $ctx): array
    {
        $strengths = [];
        $weaknesses = [];
        $recs = [];

        $resume = (int) ($ctx->scores['resume_overall'] ?? 0);
        $match = (int) ($ctx->scores['match_overall'] ?? 0);
        $rank = (int) ($ctx->scores['ranking_overall'] ?? 0);
        $skills = (int) ($ctx->scores['skills_score'] ?? 0);
        $experience = (int) ($ctx->scores['experience_score'] ?? 0);
        $education = (int) ($ctx->scores['education_score'] ?? 0);
        $certs = (int) ($ctx->scores['certification_score'] ?? 0);

        if ($match >= 60) {
            $strengths[] = 'Strong job-match signal (' . $match . '/100) against current requirements.';
        }
        if ($rank >= 60) {
            $strengths[] = 'Competitive applicant ranking (' . $rank . '/100) among peers for this job.';
        }
        if ($resume >= 60) {
            $strengths[] = 'Solid resume intelligence readiness (' . $resume . '/100).';
        }
        if ($ctx->matchedSkills !== []) {
            $strengths[] = 'Documented skills aligned to the role: ' . implode(', ', array_slice($ctx->matchedSkills, 0, 5)) . '.';
        }
        if ($experience >= 60) {
            $strengths[] = 'Experience profile meets or approaches the role bar.';
        }
        if ($certs >= 50) {
            $strengths[] = 'Certifications support professional credibility for this hire.';
        }

        if ($ctx->missingSkills !== []) {
            $weaknesses[] = 'Potential skill gaps vs requirements: ' . implode(', ', array_slice($ctx->missingSkills, 0, 5)) . '.';
        }
        if ($match < 50) {
            $weaknesses[] = 'Overall match is below a comfortable bar (' . $match . '/100) — probe role fit carefully.';
        }
        if ($skills < 50) {
            $weaknesses[] = 'Skills dimension scores low (' . $skills . '/100) relative to peers.';
        }
        if ($experience < 45) {
            $weaknesses[] = 'Experience depth may be light for this posting.';
        }
        if ($education < 40) {
            $weaknesses[] = 'Education alignment is weak; confirm credential expectations.';
        }
        if ($resume < 45) {
            $weaknesses[] = 'Resume readiness is incomplete — ask for concrete examples beyond the CV.';
        }

        if ($strengths === []) {
            $strengths[] = 'Candidate applied with interest in ' . $ctx->jobTitle . '; validate motivation and transferable strengths live.';
        }
        if ($weaknesses === []) {
            $weaknesses[] = 'No major automated red flags; still validate cultural fit and communication live.';
        }

        $recs[] = 'Open with 2–3 technical questions tied to matched skills, then probe gaps.';
        if ($ctx->missingSkills !== []) {
            $recs[] = 'Spend interview time on how the candidate would ramp on: ' . implode(', ', array_slice($ctx->missingSkills, 0, 3)) . '.';
        }
        $recs[] = 'Use STAR behavioral prompts for pressure, teamwork, and feedback.';
        if ($rank >= 70 && $match >= 60) {
            $recs[] = 'Strong AI signals — focus on authenticity of examples and culture add.';
        } elseif ($rank < 50 || $match < 50) {
            $recs[] = 'Mixed AI signals — require concrete evidence before advancing.';
        } else {
            $recs[] = 'Balanced profile — scorecard overall ≥70 before recommending hire.';
        }
        $recs[] = 'Complete the interview scorecard immediately after the conversation.';

        return [
            'strengths' => array_values(array_unique($strengths)),
            'weaknesses' => array_values(array_unique($weaknesses)),
            'recommendations' => array_values(array_unique($recs)),
        ];
    }
}
