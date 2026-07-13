<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Services;

use JobVisa\App\Domain\CoverLetter\Support\CoverLetterRulesVersion;

/**
 * Deterministic multi-style cover letter generator (no external AI).
 */
final class CoverLetterGenerator
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *   body_text: string,
     *   highlights: array<string, mixed>,
     *   context: array<string, mixed>,
     *   ats_score: int
     * }
     */
    public function generate(array $context): array
    {
        $style = (string) ($context['style'] ?? CoverLetterRulesVersion::STYLE_PROFESSIONAL);
        $tone = isset($context['tone']) && is_string($context['tone']) ? $context['tone'] : null;
        $candidate = (string) ($context['candidate_name'] ?? 'Applicant');
        $jobTitle = (string) ($context['job_title'] ?? 'the open role');
        $company = (string) ($context['company_hint'] ?? 'your organisation');
        $requirements = trim((string) ($context['job_requirements'] ?? ''));
        $skills = $this->stringList($context['matched_skills'] ?? []);
        $achievements = $this->stringList($context['achievements'] ?? []);
        $coachFocus = (string) ($context['coach_focus'] ?? '');
        $scores = is_array($context['scores'] ?? null) ? $context['scores'] : [];

        $skillLine = $skills !== []
            ? implode(', ', array_slice($skills, 0, 5))
            : 'the core competencies listed on my resume';
        $achievementLine = $achievements !== []
            ? $achievements[0]
            : 'delivering measurable outcomes aligned to team goals';

        $opening = $this->opening($style, $candidate, $jobTitle, $company, $tone);
        $matchPara = $this->matchParagraph($style, $jobTitle, $skillLine, $requirements, $scores);
        $achievePara = $this->achievementParagraph($style, $achievementLine, $coachFocus);
        $close = $this->closing($style, $jobTitle, $candidate, $tone);

        $body = implode("\n\n", array_filter([$opening, $matchPara, $achievePara, $close]));
        $highlights = [
            'matched_skills' => array_slice($skills, 0, 8),
            'achievements' => array_slice($achievements, 0, 5),
            'style' => $style,
            'tone' => $tone,
            'job_title' => $jobTitle,
        ];
        $ctx = [
            'scores' => $scores,
            'coach_focus' => $coachFocus,
            'match_overall' => (int) ($scores['match_overall'] ?? 0),
            'resume_overall' => (int) ($scores['resume_overall'] ?? 0),
            'requirements_excerpt' => mb_substr($requirements, 0, 240),
        ];
        $ats = $this->atsScore($body, $skills, $requirements);

        return [
            'body_text' => $body,
            'highlights' => $highlights,
            'context' => $ctx,
            'ats_score' => $ats,
        ];
    }

    private function opening(string $style, string $name, string $job, string $company, ?string $tone): string
    {
        $toneBit = $tone ? ' with a ' . $tone . ' tone' : '';

        return match ($style) {
            CoverLetterRulesVersion::STYLE_EXECUTIVE => "Dear Hiring Manager,\n\nI am writing to express my interest in the {$job} opportunity at {$company}. As {$name}, I bring executive-level clarity{$toneBit} and a track record of aligning people, process and outcomes.",
            CoverLetterRulesVersion::STYLE_GRADUATE => "Dear Hiring Team,\n\nI am excited to apply for the {$job} role at {$company}. I am {$name}, an emerging professional eager to contribute fresh energy{$toneBit} while learning quickly from experienced mentors.",
            CoverLetterRulesVersion::STYLE_TECHNICAL => "Dear Hiring Manager,\n\nI am applying for the {$job} position at {$company}. I am {$name}, and my background is grounded in practical, measurable technical delivery{$toneBit}.",
            CoverLetterRulesVersion::STYLE_CREATIVE => "Dear Hiring Team,\n\nStories of impact start with purpose — mine leads to the {$job} role at {$company}. I am {$name}, and I craft clear narratives{$toneBit} that connect skills to real results.",
            default => "Dear Hiring Manager,\n\nI am writing to apply for the {$job} position at {$company}. My name is {$name}, and I would welcome the opportunity to contribute professionally{$toneBit}.",
        };
    }

    /**
     * @param  array<string, mixed>  $scores
     */
    private function matchParagraph(
        string $style,
        string $job,
        string $skillLine,
        string $requirements,
        array $scores,
    ): string {
        $match = (int) ($scores['match_overall'] ?? 0);
        $resume = (int) ($scores['resume_overall'] ?? 0);
        $reqHint = $requirements !== ''
            ? ' Your requirements around ' . mb_substr(preg_replace('/\s+/u', ' ', $requirements) ?? $requirements, 0, 120) . ' align closely with my experience.'
            : ' My resume and job-match profile indicate strong alignment with this vacancy.';

        $scoreHint = $match > 0
            ? " Current AI job-match signals score {$match}/100 with a resume readiness of {$resume}/100."
            : " Resume intelligence currently scores {$resume}/100 for readiness.";

        return match ($style) {
            CoverLetterRulesVersion::STYLE_TECHNICAL => "Technically, I map directly to {$job} through {$skillLine}.{$reqHint}{$scoreHint} I focus on ATS-readable evidence: tools, methods and outcomes rather than vague claims.",
            CoverLetterRulesVersion::STYLE_EXECUTIVE => "Strategically, I see {$job} as a leverage point for operational excellence. Matching strengths include {$skillLine}.{$reqHint}{$scoreHint}",
            CoverLetterRulesVersion::STYLE_GRADUATE => "I have prepared for {$job} by building foundations in {$skillLine}.{$reqHint}{$scoreHint} I am ready to translate coursework and early experience into reliable day-one contribution.",
            CoverLetterRulesVersion::STYLE_CREATIVE => "What connects me to {$job} is a blend of craft and clarity — especially {$skillLine}.{$reqHint}{$scoreHint}",
            default => "I am a strong fit for {$job} because of demonstrated strengths in {$skillLine}.{$reqHint}{$scoreHint}",
        };
    }

    private function achievementParagraph(string $style, string $achievement, string $coachFocus): string
    {
        $coach = $coachFocus !== ''
            ? ' Career coaching also highlights a near-term focus on: ' . rtrim($coachFocus, '.') . '.'
            : '';

        return match ($style) {
            CoverLetterRulesVersion::STYLE_EXECUTIVE => "A representative achievement: {$achievement}. I emphasize governance, stakeholder trust and scalable execution.{$coach}",
            CoverLetterRulesVersion::STYLE_GRADUATE => "A recent highlight I can discuss further: {$achievement}. I learn fast, document carefully and ask precise questions.{$coach}",
            CoverLetterRulesVersion::STYLE_TECHNICAL => "Evidence of delivery: {$achievement}. I prefer covering letters and resumes that stay keyword-honest and interview-verifiable.{$coach}",
            CoverLetterRulesVersion::STYLE_CREATIVE => "One achievement that frames my story: {$achievement}. I keep the narrative human while remaining ATS-friendly.{$coach}",
            default => "Notably, {$achievement}. I am prepared to expand on matching skills and achievements in an interview.{$coach}",
        };
    }

    private function closing(string $style, string $job, string $name, ?string $tone): string
    {
        $toneClose = $tone ? " Thank you for considering a {$tone} presentation of my candidacy." : '';

        return match ($style) {
            CoverLetterRulesVersion::STYLE_EXECUTIVE => "I would welcome a conversation about how I can advance the {$job} mandate.{$toneClose}\n\nSincerely,\n{$name}",
            CoverLetterRulesVersion::STYLE_GRADUATE => "Thank you for reviewing my application for {$job}. I am available for interviews at your earliest convenience.{$toneClose}\n\nKind regards,\n{$name}",
            CoverLetterRulesVersion::STYLE_CREATIVE => "I would love to continue this conversation and show how my work speaks for the {$job} brief.{$toneClose}\n\nWith appreciation,\n{$name}",
            default => "Thank you for your time and consideration regarding the {$job} role. I look forward to the possibility of contributing to your team.{$toneClose}\n\nSincerely,\n{$name}",
        };
    }

    /**
     * @param  list<string>  $skills
     */
    private function atsScore(string $body, array $skills, string $requirements): int
    {
        $score = 40;
        $len = mb_strlen($body);
        if ($len >= 900) {
            $score += 20;
        } elseif ($len >= 500) {
            $score += 12;
        } elseif ($len >= 250) {
            $score += 6;
        }

        $hay = mb_strtolower($body);
        $hits = 0;
        foreach (array_slice($skills, 0, 8) as $skill) {
            if ($skill !== '' && str_contains($hay, mb_strtolower($skill))) {
                $hits++;
            }
        }
        $score += min(25, $hits * 4);

        if ($requirements !== '') {
            $parts = preg_split('/[,;\n]+/u', $requirements) ?: [];
            $reqHits = 0;
            foreach (array_slice($parts, 0, 6) as $p) {
                $p = trim($p);
                if (mb_strlen($p) >= 4 && str_contains($hay, mb_strtolower($p))) {
                    $reqHits++;
                }
            }
            $score += min(15, $reqHits * 3);
        }

        return max(0, min(100, $score));
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
