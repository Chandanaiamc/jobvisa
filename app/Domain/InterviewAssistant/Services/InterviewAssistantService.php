<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\Services;

use JobVisa\App\Domain\InterviewAssistant\DTO\InterviewGenerationContext;
use JobVisa\App\Domain\InterviewAssistant\DTO\InterviewSessionDTO;
use JobVisa\App\Domain\InterviewAssistant\Exceptions\InterviewAssistantException;
use JobVisa\App\Domain\InterviewAssistant\Policies\InterviewAssistantPolicy;
use JobVisa\App\Domain\InterviewAssistant\Support\InterviewAssistantVersion;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\InterviewScorecardRepositoryInterface;
use JobVisa\App\Repositories\Contracts\InterviewSessionRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobApplicantRankingRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeIntelligenceRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeJobMatchRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeSkillRepositoryInterface;

/**
 * Employer AI Interview Assistant application service.
 */
final class InterviewAssistantService
{
    private const ALLOWED_RECOMMENDATIONS = ['hire', 'maybe', 'no', 'pending'];

    public function __construct(
        private readonly JobRepositoryInterface $jobs,
        private readonly ApplicationRepositoryInterface $applications,
        private readonly InterviewSessionRepositoryInterface $sessions,
        private readonly InterviewScorecardRepositoryInterface $scorecards,
        private readonly JobApplicantRankingRepositoryInterface $rankings,
        private readonly ResumeIntelligenceRepositoryInterface $intelligence,
        private readonly ResumeJobMatchRepositoryInterface $matches,
        private readonly ResumeSkillRepositoryInterface $skills,
        private readonly InterviewQuestionGenerator $questions,
        private readonly InterviewInsightService $insights,
        private readonly InterviewAssistantPolicy $policy,
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function page(array $actor, ?int $jobId = null): array
    {
        $this->assertCanUse($actor);
        $userId = (int) $actor['id'];
        $jobs = $this->jobs->listOwnedByEmployerUser($userId, 50);

        $selectedJob = null;
        $candidates = [];
        if ($jobId !== null && $jobId > 0) {
            $selectedJob = $this->requireOwnedJob($actor, $jobId);
            $candidates = $this->sessions->listCandidatesForJob($jobId, 100);
        }

        return [
            'jobs' => $jobs,
            'selected_job' => $selectedJob,
            'candidates' => $candidates,
            'history' => $this->sessions->listByEmployer($userId, 20),
            'version' => InterviewAssistantVersion::CURRENT,
            'disclaimer' => 'Interview Assistant builds questions and insights from resume data, AI scores, and job requirements using deterministic rules. It does not call external AI APIs and is not a hiring decision.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, session_id: int}
     */
    public function generate(array $actor, int $applicationId): array
    {
        $this->assertCanUse($actor);
        if ($applicationId < 1) {
            throw InterviewAssistantException::applicationNotFound();
        }

        $application = $this->applications->findRecordById($applicationId);
        if ($application === null) {
            throw InterviewAssistantException::applicationNotFound();
        }

        $jobId = (int) ($application['job_id'] ?? 0);
        $job = $this->requireOwnedJob($actor, $jobId);

        $resumeId = isset($application['resume_id']) && $application['resume_id'] !== null
            ? (int) $application['resume_id']
            : null;
        if ($resumeId === null || $resumeId < 1) {
            throw InterviewAssistantException::resumeRequired();
        }

        $ctx = $this->buildContext($job, $application, $resumeId);
        $pack = $this->questions->generate($ctx);
        $insight = $this->insights->analyze($ctx);

        $sessionId = $this->sessions->create([
            'employer_user_id' => (int) $actor['id'],
            'job_id' => $jobId,
            'application_id' => $applicationId,
            'resume_id' => $resumeId,
            'candidate_user_id' => (int) ($application['user_id'] ?? 0),
            'status' => 'prepared',
            'technical_questions' => $pack['technical'],
            'behavioral_questions' => $pack['behavioral'],
            'strengths_json' => $insight['strengths'],
            'weaknesses_json' => $insight['weaknesses'],
            'recommendations_json' => $insight['recommendations'],
            'context_scores_json' => $ctx->scores,
            'assistant_version' => InterviewAssistantVersion::CURRENT,
        ]);

        return [
            'success' => true,
            'message' => 'Interview pack prepared for ' . $ctx->candidateName . '.',
            'session_id' => $sessionId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function showSession(array $actor, int $sessionId): array
    {
        $this->assertCanUse($actor);
        $row = $this->sessions->findOwned($sessionId, (int) $actor['id']);
        if ($row === null) {
            throw InterviewAssistantException::sessionNotFound();
        }

        $scorecard = $this->scorecards->findBySessionId($sessionId);
        $dto = InterviewSessionDTO::fromRow($row, $scorecard);

        return [
            'session' => $dto,
            'version' => InterviewAssistantVersion::CURRENT,
            'disclaimer' => 'Use the scorecard during or immediately after the interview. Scores are employer-entered; AI questions are advisory only.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string}
     */
    public function saveScorecard(array $actor, int $sessionId, array $input): array
    {
        $this->assertCanUse($actor);
        if (!$this->policy->canScore($actor)) {
            throw InterviewAssistantException::forbidden();
        }

        $row = $this->sessions->findOwned($sessionId, (int) $actor['id']);
        if ($row === null) {
            throw InterviewAssistantException::sessionNotFound();
        }

        $technical = $this->clampScore($input['technical_score'] ?? null);
        $behavioral = $this->clampScore($input['behavioral_score'] ?? null);
        $communication = $this->clampScore($input['communication_score'] ?? null);
        $culture = $this->clampScore($input['culture_fit_score'] ?? null);
        $recommendation = strtolower(trim((string) ($input['hiring_recommendation'] ?? 'pending')));
        if (!in_array($recommendation, self::ALLOWED_RECOMMENDATIONS, true)) {
            throw InterviewAssistantException::invalidScorecard('Choose a valid hiring recommendation.');
        }

        $overall = (int) round(($technical + $behavioral + $communication + $culture) / 4);

        $this->scorecards->upsert($sessionId, [
            'technical_score' => $technical,
            'behavioral_score' => $behavioral,
            'communication_score' => $communication,
            'culture_fit_score' => $culture,
            'overall_score' => $overall,
            'notes' => trim((string) ($input['notes'] ?? '')),
            'hiring_recommendation' => $recommendation,
            'scored_by_user_id' => (int) $actor['id'],
        ]);

        $this->sessions->updateStatus($sessionId, (int) $actor['id'], 'scored');

        return [
            'success' => true,
            'message' => 'Scorecard saved (overall ' . $overall . '/100).',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function softDeleteHistory(array $actor, int $sessionId): array
    {
        $this->assertCanUse($actor);
        if (!$this->policy->canManageHistory($actor)) {
            throw InterviewAssistantException::forbidden();
        }

        $ok = $this->sessions->softDelete($sessionId, (int) $actor['id']);

        return [
            'success' => $ok,
            'message' => $ok ? 'Interview session removed from history.' : 'Session not found or already deleted.',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function clearHistory(array $actor): array
    {
        $this->assertCanUse($actor);
        if (!$this->policy->canManageHistory($actor)) {
            throw InterviewAssistantException::forbidden();
        }

        $n = $this->sessions->softDeleteAllForEmployer((int) $actor['id']);

        return [
            'success' => true,
            'message' => $n > 0
                ? ('Cleared ' . $n . ' interview session' . ($n === 1 ? '' : 's') . ' from history.')
                : 'History was already empty.',
        ];
    }

    /**
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $application
     */
    private function buildContext(array $job, array $application, int $resumeId): InterviewGenerationContext
    {
        $jobId = (int) $job['id'];
        $candidateUserId = (int) ($application['user_id'] ?? 0);

        $skillRows = $this->skills->listByResumeId($resumeId);
        $resumeSkills = [];
        foreach ($skillRows as $row) {
            $name = trim((string) ($row['skill_name'] ?? $row['name'] ?? ''));
            if ($name !== '') {
                $resumeSkills[] = $name;
            }
        }

        $intel = $this->intelligence->findLatestByResumeId($resumeId);
        $match = $this->matches->findByResumeAndJob($resumeId, $jobId);

        $matched = [];
        $missing = [];
        if ($match !== null) {
            $explanation = $this->decodeJson($match['explanation_json'] ?? []);
            $matched = $this->stringList(
                $explanation['matched_requirements']
                ?? ($explanation['matched_skills'] ?? [])
            );
            $missing = $this->stringList(
                $explanation['missing_required_skills']
                ?? ($explanation['missing_required'] ?? [])
            );
        }

        if ($matched === [] && $resumeSkills !== []) {
            $reqText = mb_strtolower((string) ($job['requirements'] ?? '') . ' ' . (string) ($job['description'] ?? ''));
            foreach ($resumeSkills as $skill) {
                if ($skill !== '' && str_contains($reqText, mb_strtolower($skill))) {
                    $matched[] = $skill;
                }
            }
        }

        if ($missing === []) {
            $keywords = $this->extractRequirementKeywords(
                (string) ($job['requirements'] ?? ''),
                (string) ($job['description'] ?? '')
            );
            $lowerSkills = array_map(static fn (string $s): string => mb_strtolower($s), $resumeSkills);
            foreach ($keywords as $kw) {
                if (!in_array(mb_strtolower($kw), $lowerSkills, true)) {
                    $missing[] = $kw;
                }
            }
        }

        $rankingRow = $this->findRankingForApplication($jobId, (int) ($application['id'] ?? 0));

        $candidateName = 'Candidate';
        $detailed = $this->applications->findDetailedByJobId($jobId, 200);
        foreach ($detailed as $row) {
            if ((int) ($row['id'] ?? 0) === (int) ($application['id'] ?? 0)) {
                $candidateName = (string) ($row['applicant_name'] ?? 'Candidate');
                break;
            }
        }

        $years = null;
        if ($rankingRow !== null && isset($rankingRow['explanation_json'])) {
            $exp = $this->decodeJson($rankingRow['explanation_json']);
            if (isset($exp['years_of_experience'])) {
                $years = (int) $exp['years_of_experience'];
            }
        }

        $requirementKeywords = $this->extractRequirementKeywords(
            (string) ($job['requirements'] ?? ''),
            (string) ($job['description'] ?? '')
        );

        $scores = [
            'resume_overall' => (int) ($intel['overall_score'] ?? ($rankingRow['resume_score'] ?? 0)),
            'ats_score' => (int) ($intel['ats_score'] ?? 0),
            'employer_readiness' => (int) ($intel['employer_readiness_score'] ?? 0),
            'match_overall' => (int) ($match['overall_score'] ?? ($rankingRow['job_match_score'] ?? 0)),
            'ranking_overall' => (int) ($rankingRow['overall_score'] ?? 0),
            'skills_score' => (int) ($rankingRow['skills_score'] ?? ($match['skills_score'] ?? 0)),
            'experience_score' => (int) ($rankingRow['experience_score'] ?? ($match['experience_score'] ?? 0)),
            'education_score' => (int) ($rankingRow['education_score'] ?? ($match['education_score'] ?? 0)),
            'certification_score' => (int) ($rankingRow['certification_score'] ?? ($match['certification_score'] ?? 0)),
            'rank_position' => (int) ($rankingRow['rank_position'] ?? 0),
        ];

        return new InterviewGenerationContext(
            $jobId,
            (string) ($job['title'] ?? ''),
            (string) ($job['requirements'] ?? ''),
            (string) ($job['description'] ?? ''),
            isset($job['experience_min_years']) && $job['experience_min_years'] !== null
                ? (int) $job['experience_min_years']
                : null,
            isset($job['education_level']) ? (string) $job['education_level'] : null,
            (int) ($application['id'] ?? 0),
            $candidateUserId,
            $candidateName,
            $resumeId,
            array_values(array_unique($resumeSkills)),
            array_values(array_unique($matched)),
            array_values(array_unique($missing)),
            $requirementKeywords,
            $scores,
            $years,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRankingForApplication(int $jobId, int $applicationId): ?array
    {
        foreach ($this->rankings->listByJobId($jobId, 200) as $row) {
            if ((int) ($row['application_id'] ?? 0) === $applicationId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extractRequirementKeywords(string $requirements, string $description): array
    {
        $text = trim($requirements !== '' ? $requirements : $description);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[,;\n\r•|\-]+/u', $text) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
            $part = trim($part, " .\t");
            if (mb_strlen($part) < 3 || mb_strlen($part) > 80) {
                continue;
            }
            // Skip very generic fragments
            $lower = mb_strtolower($part);
            if (in_array($lower, ['and', 'or', 'the', 'with', 'for', 'demo listing'], true)) {
                continue;
            }
            $out[] = $part;
            if (count($out) >= 8) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    private function requireOwnedJob(array $actor, int $jobId): array
    {
        $job = $this->jobs->findOwnedByEmployerUser($jobId, (int) $actor['id']);
        if ($job === null) {
            $any = $this->jobs->findRecordById($jobId);
            if ($any === null) {
                throw InterviewAssistantException::jobNotFound();
            }
            throw InterviewAssistantException::forbidden();
        }

        if (!$this->policy->canPrepareForJob($actor, $job)) {
            throw InterviewAssistantException::forbidden();
        }

        return $job;
    }

    /** @param array<string, mixed> $actor */
    private function assertCanUse(array $actor): void
    {
        if (!$this->policy->canUse($actor)) {
            throw InterviewAssistantException::forbidden();
        }
    }

    private function clampScore(mixed $value): int
    {
        if ($value === null || $value === '') {
            throw InterviewAssistantException::invalidScorecard('All score dimensions are required (0–100).');
        }
        $n = (int) $value;
        if ($n < 0 || $n > 100) {
            throw InterviewAssistantException::invalidScorecard('Scores must be between 0 and 100.');
        }

        return $n;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
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

        return $out;
    }
}
