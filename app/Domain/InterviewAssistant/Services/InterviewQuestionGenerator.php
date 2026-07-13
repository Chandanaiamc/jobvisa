<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\Services;

use JobVisa\App\Domain\InterviewAssistant\DTO\InterviewGenerationContext;

/**
 * Deterministic technical + behavioral question generator (no external AI).
 */
final class InterviewQuestionGenerator
{
    /**
     * @return array{technical: list<array{id: string, prompt: string, focus: string, difficulty: string}>, behavioral: list<array{id: string, prompt: string, focus: string, difficulty: string}>}
     */
    public function generate(InterviewGenerationContext $ctx): array
    {
        $technical = [];
        $n = 1;

        foreach (array_slice($ctx->matchedSkills, 0, 4) as $skill) {
            $technical[] = $this->q(
                't' . $n++,
                'Walk me through a recent situation where you applied ' . $skill . ' to deliver a measurable outcome relevant to ' . $ctx->jobTitle . '.',
                $skill,
                'medium'
            );
        }

        foreach (array_slice($ctx->missingSkills, 0, 3) as $skill) {
            $technical[] = $this->q(
                't' . $n++,
                'This role expects familiarity with ' . $skill . '. How would you close that gap in the first 90 days, and what related experience can you transfer?',
                $skill . ' gap',
                'hard'
            );
        }

        foreach (array_slice($ctx->requirementKeywords, 0, 3) as $kw) {
            if ($this->alreadyCovers($technical, $kw)) {
                continue;
            }
            $technical[] = $this->q(
                't' . $n++,
                'The job requirements highlight "' . $kw . '". Describe your hands-on approach and how you would validate quality on the job.',
                $kw,
                'medium'
            );
        }

        if ($ctx->experienceMinYears !== null && $ctx->experienceMinYears > 0) {
            $technical[] = $this->q(
                't' . $n++,
                'This role asks for about ' . $ctx->experienceMinYears . '+ years of experience. Summarize the most relevant stretch of your career and the hardest technical problem you solved there.',
                'experience depth',
                'medium'
            );
        }

        if ($technical === []) {
            $technical[] = $this->q(
                't1',
                'Describe the core technical responsibilities you expect in the ' . $ctx->jobTitle . ' role and how your background prepares you for them.',
                'role fundamentals',
                'easy'
            );
        }

        $behavioral = $this->behavioralSet($ctx);

        return [
            'technical' => array_slice($technical, 0, 8),
            'behavioral' => array_slice($behavioral, 0, 6),
        ];
    }

    /**
     * @return list<array{id: string, prompt: string, focus: string, difficulty: string}>
     */
    private function behavioralSet(InterviewGenerationContext $ctx): array
    {
        $role = $ctx->jobTitle;
        $items = [
            $this->q(
                'b1',
                'Tell me about a time you handled a stressful patient/client or stakeholder situation. What did you do, and what was the result? (STAR)',
                'pressure & composure',
                'medium'
            ),
            $this->q(
                'b2',
                'Describe a conflict with a teammate or supervisor and how you resolved it while protecting quality of work for ' . $role . '.',
                'collaboration',
                'medium'
            ),
            $this->q(
                'b3',
                'Give an example of when you had incomplete information and still needed to make a decision. How did you proceed?',
                'judgment',
                'hard'
            ),
            $this->q(
                'b4',
                'What motivates you about relocating or working in this job context, and how do you adapt to new protocols or cultures?',
                'adaptability',
                'easy'
            ),
            $this->q(
                'b5',
                'Share a time you received critical feedback. How did you respond, and what changed afterward?',
                'growth mindset',
                'medium'
            ),
        ];

        $ranking = (int) ($ctx->scores['ranking_overall'] ?? 0);
        if ($ranking >= 70) {
            $items[] = $this->q(
                'b6',
                'Your profile ranks strongly for this role. Where do you still want coaching in the first six months, and how should we support you?',
                'onboarding expectations',
                'easy'
            );
        } else {
            $items[] = $this->q(
                'b6',
                'Where do you see the biggest stretch between your current readiness and this ' . $role . ' role, and how will you close it?',
                'self-awareness',
                'medium'
            );
        }

        return $items;
    }

    /**
     * @return array{id: string, prompt: string, focus: string, difficulty: string}
     */
    private function q(string $id, string $prompt, string $focus, string $difficulty): array
    {
        return [
            'id' => $id,
            'prompt' => $prompt,
            'focus' => $focus,
            'difficulty' => $difficulty,
        ];
    }

    /**
     * @param  list<array{id: string, prompt: string, focus: string, difficulty: string}>  $questions
     */
    private function alreadyCovers(array $questions, string $kw): bool
    {
        $needle = mb_strtolower($kw);
        foreach ($questions as $q) {
            if (str_contains(mb_strtolower($q['focus']), $needle) || str_contains(mb_strtolower($q['prompt']), $needle)) {
                return true;
            }
        }

        return false;
    }
}
