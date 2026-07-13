<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\MockInterview\Services;

use JobVisa\App\Domain\MockInterview\DTO\MockInterviewSessionDTO;
use JobVisa\App\Domain\MockInterview\Support\MockInterviewVersion;

/**
 * Deterministic mock interview generation + answer analysis — no external AI APIs.
 */
final class MockInterviewAnalyzer
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(int $resumeId, int $userId, array $context): MockInterviewSessionDTO
    {
        $jobTitle = trim((string) ($context['job_title'] ?? 'Target Role'));
        $level = $this->careerLevel((float) ($context['years'] ?? 0), $jobTitle);
        $skills = $this->stringList($context['skills'] ?? []);
        $missing = $this->stringList($context['missing_skills'] ?? []);
        $questions = $this->buildQuestions($jobTitle, $level, $skills, $missing, $context);

        $session = [
            'headline' => 'Mock interview for ' . $jobTitle,
            'career_level' => $level,
            'questions' => $questions,
            'answers' => [],
            'analysis' => null,
            'report' => null,
            'signals' => [
                'gap_percentage' => (int) ($context['gap_percentage'] ?? 0),
                'resume_overall' => (int) ($context['resume_overall'] ?? 0),
                'portfolio_strength' => (int) ($context['portfolio_strength'] ?? 0),
            ],
        ];

        return new MockInterviewSessionDTO(
            0,
            $resumeId,
            $userId,
            isset($context['job_id']) && (int) $context['job_id'] > 0 ? (int) $context['job_id'] : null,
            $jobTitle,
            $level,
            MockInterviewVersion::STATUS_GENERATED,
            0,
            0,
            0,
            0,
            0,
            $session,
            MockInterviewVersion::CURRENT,
            '',
        );
    }

    /**
     * @param  array<string, mixed>  $sessionJson
     * @param  array<string, string>  $answers
     * @return array{session_json: array<string, mixed>, overall: int, communication: int, technical: int, confidence: int, star: int}
     */
    public function analyze(array $sessionJson, array $answers, array $context = []): array
    {
        $questions = is_array($sessionJson['questions'] ?? null) ? $sessionJson['questions'] : [];
        $perQuestion = [];
        $comm = [];
        $tech = [];
        $conf = [];
        $star = [];
        $improvements = [];
        $followUps = [];

        foreach ($questions as $q) {
            if (!is_array($q)) {
                continue;
            }
            $id = (string) ($q['id'] ?? '');
            $answer = trim((string) ($answers[$id] ?? ''));
            $pack = $this->scoreAnswer($q, $answer);
            $perQuestion[] = $pack;
            $comm[] = $pack['communication'];
            $tech[] = $pack['technical'];
            $conf[] = $pack['confidence'];
            $star[] = $pack['star'];
            foreach ($pack['improvements'] as $tip) {
                $improvements[] = $tip;
            }
            if ($pack['follow_up'] !== '') {
                $followUps[] = $pack['follow_up'];
            }
        }

        $communication = $this->avg($comm);
        $technical = $this->avg($tech);
        $confidence = $this->avg($conf);
        $starScore = $this->avg($star);
        $overall = (int) round(
            ($communication * 0.25)
            + ($technical * 0.30)
            + ($confidence * 0.20)
            + ($starScore * 0.25)
        );

        $summary = sprintf(
            'Overall interview score %d/100 (communication %d, technical %d, confidence %d, STAR %d).',
            $overall,
            $communication,
            $technical,
            $confidence,
            $starScore
        );

        $uniqueImprovements = array_values(array_unique(array_slice($improvements, 0, 10)));
        if ($uniqueImprovements === []) {
            $uniqueImprovements[] = 'Polish one quantified result in each answer to raise interviewer confidence.';
        }

        $analysis = [
            'per_question' => $perQuestion,
            'improvements' => $uniqueImprovements,
            'follow_up_questions' => array_values(array_unique(array_slice($followUps, 0, 8))),
            'summary' => $summary,
            'readiness_label' => $this->readinessLabel($overall),
        ];

        $report = [
            'title' => 'Mock Interview Report',
            'overall' => $overall,
            'dimensions' => [
                'communication' => $communication,
                'technical' => $technical,
                'confidence' => $confidence,
                'star' => $starScore,
            ],
            'summary' => $summary,
            'top_improvements' => array_slice($analysis['improvements'], 0, 5),
            'recommended_follow_ups' => array_slice($analysis['follow_up_questions'], 0, 5),
            'context_notes' => $this->contextNotes($context, $overall),
        ];

        $sessionJson['answers'] = $answers;
        $sessionJson['analysis'] = $analysis;
        $sessionJson['report'] = $report;

        return [
            'session_json' => $sessionJson,
            'overall' => $overall,
            'communication' => $communication,
            'technical' => $technical,
            'confidence' => $confidence,
            'star' => $starScore,
        ];
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $missing
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    private function buildQuestions(
        string $jobTitle,
        string $level,
        array $skills,
        array $missing,
        array $context,
    ): array {
        $primary = $skills[0] ?? 'your core strengths';
        $gap = $missing[0] ?? 'emerging tools';
        $qs = [];

        $qs[] = $this->q('hr1', 'hr', 'Tell me about yourself and why you are interested in ' . $jobTitle . '.', 'Motivation', 'easy');
        $qs[] = $this->q('hr2', 'hr', 'What do you know about this role’s responsibilities and how does your background fit?', 'Role fit', 'easy');
        $qs[] = $this->q('hr3', 'hr', 'Where do you see yourself in 3–5 years relative to this career path?', 'Career goals', 'medium');

        $qs[] = $this->q(
            'tech1',
            'technical',
            'Walk me through a technical challenge where you applied ' . $primary . ' to deliver a measurable outcome for work similar to ' . $jobTitle . '.',
            $primary,
            'medium'
        );
        $qs[] = $this->q(
            'tech2',
            'technical',
            'How would you approach learning ' . $gap . ' in the first 90 days if this role expects it?',
            $gap . ' gap',
            'hard'
        );
        if (isset($skills[1])) {
            $qs[] = $this->q(
                'tech3',
                'technical',
                'Explain a decision trade-off you made involving ' . $skills[1] . '. What alternatives did you reject and why?',
                $skills[1],
                'hard'
            );
        }

        $qs[] = $this->q(
            'beh1',
            'behavioral',
            'Describe a time you handled conflict with a teammate or stakeholder. Use the STAR method.',
            'Conflict',
            'medium'
        );
        $qs[] = $this->q(
            'beh2',
            'behavioral',
            'Tell me about a deadline you nearly missed. What did you do, and what changed afterward?',
            'Ownership',
            'medium'
        );
        $qs[] = $this->q(
            'beh3',
            'behavioral',
            'Give an example of feedback you received that improved how you work as a ' . strtolower($level) . ' professional.',
            'Growth',
            'easy'
        );

        $qs[] = $this->q(
            'scn1',
            'scenario',
            'Imagine your first week in this ' . $jobTitle . ' role. A critical handoff is incomplete. How do you stabilize delivery while building trust?',
            'Onboarding scenario',
            'hard'
        );
        $qs[] = $this->q(
            'scn2',
            'scenario',
            'A hiring manager asks you to prioritize two conflicting goals with limited resources. How do you decide and communicate?',
            'Prioritization scenario',
            'hard'
        );

        if ((int) ($context['portfolio_strength'] ?? 0) >= 50) {
            $qs[] = $this->q(
                'tech4',
                'technical',
                'Walk me through one portfolio project you would showcase for this role and the impact metrics you would cite.',
                'Portfolio proof',
                'medium'
            );
        }

        return $qs;
    }

    /**
     * @return array{id: string, type: string, prompt: string, focus: string, difficulty: string}
     */
    private function q(string $id, string $type, string $prompt, string $focus, string $difficulty): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'prompt' => $prompt,
            'focus' => $focus,
            'difficulty' => $difficulty,
        ];
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    private function scoreAnswer(array $question, string $answer): array
    {
        $type = (string) ($question['type'] ?? 'hr');
        $len = mb_strlen($answer);
        $lower = mb_strtolower($answer);

        $communication = 35;
        if ($len >= 80) {
            $communication += 20;
        }
        if ($len >= 180) {
            $communication += 15;
        }
        if ($len >= 320) {
            $communication += 10;
        }
        if (preg_match('/\b(i|we|my|our)\b/u', $lower)) {
            $communication += 5;
        }
        if ($len < 40 && $answer !== '') {
            $communication -= 10;
        }
        if ($answer === '') {
            $communication = 15;
        }
        $communication = max(0, min(100, $communication));

        $starHits = 0;
        foreach (['situation', 'task', 'action', 'result', 'because', 'outcome', 'impact', 'measured', 'improved'] as $kw) {
            if (str_contains($lower, $kw)) {
                $starHits++;
            }
        }
        $star = $answer === '' ? 10 : min(100, 30 + ($starHits * 10) + ($len >= 120 ? 15 : 0));
        if (in_array($type, ['behavioral', 'scenario'], true)) {
            $star = min(100, $star + 5);
        }

        $technical = 40;
        if ($type === 'technical' || $type === 'scenario') {
            $technical = 35;
            if ($len >= 100) {
                $technical += 20;
            }
            if (preg_match('/\b(step|process|tool|system|metric|data|design|implement|optimize)\b/u', $lower)) {
                $technical += 20;
            }
            if ($starHits >= 2) {
                $technical += 10;
            }
        } else {
            $technical = (int) round(($communication + $star) / 2);
        }
        if ($answer === '') {
            $technical = 15;
        }
        $technical = max(0, min(100, $technical));

        $confidence = 40;
        if ($len >= 100) {
            $confidence += 15;
        }
        if (!preg_match('/\b(maybe|not sure|i think|probably|guess)\b/u', $lower) && $answer !== '') {
            $confidence += 15;
        }
        if (preg_match('/\b(delivered|led|achieved|owned|shipped|improved)\b/u', $lower)) {
            $confidence += 15;
        }
        if ($answer === '') {
            $confidence = 20;
        }
        $confidence = max(0, min(100, $confidence));

        $improvements = [];
        if ($answer === '') {
            $improvements[] = 'Provide a concrete answer for: ' . (string) ($question['prompt'] ?? 'this question');
        } else {
            if ($star < 60) {
                $improvements[] = 'Strengthen STAR structure (Situation → Task → Action → Result) for "' . (string) ($question['focus'] ?? 'this topic') . '"';
            }
            if ($len < 120) {
                $improvements[] = 'Add more specific evidence and metrics to deepen your response';
            }
            if ($type === 'technical' && $technical < 65) {
                $improvements[] = 'Explain tools, steps, and trade-offs more explicitly for the technical prompt';
            }
            if ($confidence < 60) {
                $improvements[] = 'Use stronger ownership language and clearer outcomes';
            }
        }

        $followUp = '';
        if ($answer !== '') {
            $followUp = match ($type) {
                'technical' => 'What would you do differently if constraints doubled on that technical challenge?',
                'behavioral' => 'How did stakeholders react, and what lasting process change resulted?',
                'scenario' => 'Which leading indicator would tell you your scenario response is working within two weeks?',
                default => 'Can you quantify one outcome from that experience with a number or timeline?',
            };
        }

        return [
            'question_id' => (string) ($question['id'] ?? ''),
            'type' => $type,
            'focus' => (string) ($question['focus'] ?? ''),
            'answer_length' => $len,
            'communication' => $communication,
            'technical' => $technical,
            'confidence' => $confidence,
            'star' => $star,
            'improvements' => $improvements,
            'follow_up' => $followUp,
            'star_feedback' => $star >= 70
                ? 'Strong STAR signals detected'
                : 'STAR coverage is partial — make Situation/Task/Action/Result explicit',
        ];
    }

    private function careerLevel(float $years, string $title): string
    {
        $t = mb_strtolower($title);
        if (str_contains($t, 'intern') || $years < 1) {
            return 'Entry';
        }
        if (str_contains($t, 'senior') || str_contains($t, 'lead') || $years >= 7) {
            return 'Senior';
        }
        if ($years >= 3) {
            return 'Mid';
        }

        return 'Junior';
    }

    private function readinessLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Interview ready',
            $score >= 65 => 'Competitive with polish',
            $score >= 45 => 'Needs rehearsal',
            default => 'Foundational prep required',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function contextNotes(array $context, int $overall): array
    {
        $notes = [];
        if ((int) ($context['gap_percentage'] ?? 0) >= 40) {
            $notes[] = 'Skill-gap signals suggest preparing technical answers for missing skills.';
        }
        if ((int) ($context['portfolio_strength'] ?? 0) >= 50) {
            $notes[] = 'Lean on portfolio projects as proof points in technical answers.';
        }
        if ($overall < 60) {
            $notes[] = 'Rehearse STAR stories aloud and recalculate after revising answers.';
        } else {
            $notes[] = 'Maintain concise structure and add one quantified result per answer.';
        }

        return $notes;
    }

    /** @param list<int> $scores */
    private function avg(array $scores): int
    {
        if ($scores === []) {
            return 0;
        }

        return (int) round(array_sum($scores) / count($scores));
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
