<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\DTO\ResumeProfessionalDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeProfessionalValidator;
use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;

/**
 * Resume builder — professional headline & summary section.
 */
final class ResumeProfessionalService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeProfessionalRepositoryInterface $professionalRepo,
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly ResumeProfessionalValidator $validator,
        private readonly ResumePolicy $policy,
        private readonly ResumeCompletionCalculator $completion
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{
     *   professional: ResumeProfessionalDTO,
     *   completion: array{score: int, sections: array},
     *   resume: array<string, mixed>,
     *   can_edit: bool
     * }
     */
    public function form(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'view');
        $userId = $aggregate->resume()->userId();
        $canEdit = $this->policy->allows('update', $aggregate->resume(), $actor);

        $row = $this->professionalRepo->findByResumeId($resumeId);
        $profile = $this->profiles->findByUserId($userId);
        $dto = ResumeProfessionalDTO::fromRow($resumeId, $row, $profile, $canEdit);
        $completion = $this->completion->evaluate($userId, $resumeId, null, $dto);

        return [
            'professional' => $dto,
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, professional?: ResumeProfessionalDTO, completion?: array}
     */
    public function save(array $actor, int $resumeId, array $input, bool $autosave = false): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'update');
        $userId = $aggregate->resume()->userId();

        $errors = $this->validator->validate($input);

        // Autosave is lenient: persist valid fields even if incomplete, but still block invalid formats.
        if ($errors !== [] && !$autosave) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if ($autosave) {
            $errors = $this->filterHardErrors($errors);
            if ($errors !== []) {
                return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
            }
            // Allow draft persistence without expected salary until the seeker finishes the section.
        }

        $payload = $this->normalize($input);
        $this->professionalRepo->upsert($resumeId, $payload);

        // Keep shared profile headline/summary/expected salary in sync when provided.
        $profilePatch = [];
        if ($payload['headline'] !== null) {
            $profilePatch['headline'] = $payload['headline'];
        }
        if ($payload['summary'] !== null) {
            $profilePatch['summary'] = $payload['summary'];
        }
        if ($payload['expected_salary'] !== null) {
            $profilePatch['expected_salary'] = $payload['expected_salary'];
        }
        if ($profilePatch !== []) {
            $this->profiles->upsertForUser($userId, $profilePatch);
        }

        $form = $this->form($actor, $resumeId);

        return [
            'success' => true,
            'message' => $autosave ? 'Draft autosaved.' : 'Professional summary saved.',
            'professional' => $form['professional'],
            'completion' => $form['completion'],
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, list<string>>
     */
    private function filterHardErrors(array $errors): array
    {
        $hard = [];
        foreach ($errors as $field => $messages) {
            // Skip "required" style messages on autosave; keep format/range errors.
            $kept = [];
            foreach ($messages as $message) {
                if (!str_contains(strtolower($message), 'required')
                    && !str_contains(strtolower($message), 'at least 40')) {
                    $kept[] = $message;
                }
            }
            if ($kept !== []) {
                $hard[$field] = $kept;
            }
        }

        return $hard;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $currency = strtoupper(trim((string) ($input['preferred_currency'] ?? 'LKR')));
        if ($currency === '') {
            $currency = 'LKR';
        }

        return [
            'headline' => $this->nullStr($input['headline'] ?? null),
            'summary' => $this->nullStr($input['summary'] ?? null),
            'career_objective' => $this->nullStr($input['career_objective'] ?? null),
            'years_of_experience' => $this->nullDecimal($input['years_of_experience'] ?? null, 1),
            'current_job_title' => $this->nullStr($input['current_job_title'] ?? null),
            'current_company' => $this->nullStr($input['current_company'] ?? null),
            'industry' => $this->nullStr($input['industry'] ?? null),
            'current_salary' => $this->nullDecimal($input['current_salary'] ?? null, 2),
            'expected_salary' => $this->nullDecimal($input['expected_salary'] ?? null, 2),
            'preferred_currency' => $currency,
            'notice_period' => $this->nullStr($input['notice_period'] ?? null),
            'employment_status' => $this->nullStr($input['employment_status'] ?? null),
            'open_to_relocate' => !empty($input['open_to_relocate']),
            'open_to_remote' => !empty($input['open_to_remote']),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, string $action): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);

        if ($aggregate === null) {
            throw ResumeException::notFound();
        }

        if (!$this->policy->allows($action, $aggregate->resume(), $actor)) {
            throw ResumeException::forbidden();
        }

        return $aggregate;
    }

    private function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullDecimal(mixed $value, int $decimals): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, $decimals, '.', '');
    }
}
