<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeData;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Factories\ResumeFactory;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeValidator;
use JobVisa\App\Domain\Support\AbstractDomainService;
use PDO;

/**
 * Resume use-case orchestration (Sprint 2D.1 foundation).
 */
final class ResumeService extends AbstractDomainService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeFactory $factory,
        private readonly ResumeValidator $validator,
        private readonly ResumePolicy $policy,
        private readonly PDO $pdo,
        private readonly ?ResumeCompletionCalculator $completion = null
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<ResumeData>
     */
    public function listForActor(array $actor, int $userId): array
    {
        if (!$this->policy->allows('viewAny', null, $actor) && !$this->isAdmin($actor)) {
            throw ResumeException::forbidden();
        }

        if (!$this->isAdmin($actor) && (int) ($actor['id'] ?? 0) !== $userId) {
            throw ResumeException::forbidden();
        }

        $items = [];

        foreach ($this->resumes->listActiveRecordsForUser($userId) as $row) {
            $items[] = ResumeData::fromArray($row);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    public function get(array $actor, int $resumeId): ResumeData
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);

        if ($aggregate === null) {
            throw ResumeException::notFound();
        }

        if (!$this->policy->allows('view', $aggregate->resume(), $actor)) {
            throw ResumeException::forbidden();
        }

        return ResumeData::fromEntity($aggregate->resume());
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, resume?: ResumeData}
     */
    public function create(array $actor, array $input): array
    {
        if (!$this->policy->allows('create', null, $actor)) {
            return ['success' => false, 'message' => ResumeException::forbidden()->getMessage()];
        }

        $errors = $this->validator->fieldErrors($input);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $userId = (int) ($actor['id'] ?? 0);
        $existing = $this->resumes->listActiveRecordsForUser($userId);
        $asDefault = $existing === [] || !empty($input['is_default']);

        $aggregate = $this->factory->newDraft(
            $userId,
            (string) ($input['title'] ?? 'Untitled Resume'),
            $asDefault
        );

        $visibility = (string) ($input['visibility'] ?? 'employers');
        $aggregate->updateDetails((string) $input['title'], $visibility);

        if (($input['status'] ?? '') === 'published') {
            $aggregate->publish();
        }

        $score = $this->calculateCompletion($userId, null);
        $aggregate->setCompletionPercentage($score);

        $saved = $this->resumes->saveAggregate($aggregate);

        return [
            'success' => true,
            'message' => 'Resume created.',
            'resume' => ResumeData::fromEntity($saved->resume()),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, resume?: ResumeData}
     */
    public function update(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->resumes->findOwnedAggregate($resumeId, (int) ($actor['id'] ?? 0));

        if ($aggregate === null) {
            return ['success' => false, 'message' => ResumeException::notFound()->getMessage()];
        }

        if (!$this->policy->allows('update', $aggregate->resume(), $actor)) {
            return ['success' => false, 'message' => ResumeException::forbidden()->getMessage()];
        }

        $errors = $this->validator->fieldErrors($input);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        try {
            $aggregate->updateDetails(
                (string) ($input['title'] ?? $aggregate->resume()->title()),
                (string) ($input['visibility'] ?? $aggregate->resume()->visibility())
            );

            if (($input['status'] ?? null) === 'published') {
                $aggregate->publish();
            } elseif (($input['status'] ?? null) === 'draft') {
                $aggregate->draft();
            }
        } catch (ResumeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $score = $this->calculateCompletion((int) $actor['id'], $resumeId);
        $aggregate->setCompletionPercentage($score);
        $saved = $this->resumes->saveAggregate($aggregate);

        return [
            'success' => true,
            'message' => 'Resume updated.',
            'resume' => ResumeData::fromEntity($saved->resume()),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function publish(array $actor, int $resumeId): array
    {
        return $this->transition($actor, $resumeId, 'publish', static function (ResumeAggregate $a): void {
            $a->publish();
        }, 'Resume published.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function draft(array $actor, int $resumeId): array
    {
        return $this->transition($actor, $resumeId, 'draft', static function (ResumeAggregate $a): void {
            $a->draft();
        }, 'Resume set to draft.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function setDefault(array $actor, int $resumeId): array
    {
        return $this->transition($actor, $resumeId, 'setDefault', static function (ResumeAggregate $a): void {
            $a->makeDefault();
        }, 'Default resume updated.');
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $resumeId): array
    {
        $userId = (int) ($actor['id'] ?? 0);
        $aggregate = $this->resumes->findOwnedAggregate($resumeId, $userId);

        if ($aggregate === null) {
            return ['success' => false, 'message' => ResumeException::notFound()->getMessage()];
        }

        if (!$this->policy->allows('delete', $aggregate->resume(), $actor)) {
            return ['success' => false, 'message' => ResumeException::forbidden()->getMessage()];
        }

        $wasDefault = $aggregate->resume()->isDefault();
        $this->resumes->softDeleteAggregate($aggregate);

        if ($wasDefault) {
            $remaining = $this->resumes->listActiveRecordsForUser($userId);

            if ($remaining !== []) {
                $next = $this->factory->fromRow($remaining[0]);
                $next->makeDefault();
                $this->resumes->saveAggregate($next);
            }
        }

        return ['success' => true, 'message' => 'Resume deleted.'];
    }

    /**
     * Lightweight completion % for a resume (delegates to reusable calculator when available).
     */
    public function calculateCompletion(int $userId, ?int $resumeId): int
    {
        if ($resumeId === null || $resumeId < 1) {
            return ResumeCompletionCalculator::WEIGHT_TITLE;
        }

        if ($this->completion !== null) {
            return $this->completion->evaluate($userId, $resumeId)['score'];
        }

        $aggregate = $this->resumes->findOwnedAggregate($resumeId, $userId);

        if ($aggregate === null) {
            return 0;
        }

        $resume = $aggregate->resume();
        $score = 0;

        if (trim($resume->title()) !== '') {
            $score += 20;
        }

        if ($resume->filePath() !== null && $resume->filePath() !== '') {
            $score += 30;
        }

        $eduStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `education` WHERE `resume_id` = ?');
        $eduStmt->execute([$resumeId]);
        $edu = (int) $eduStmt->fetchColumn();

        $expStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `work_experience` WHERE `resume_id` = ?');
        $expStmt->execute([$resumeId]);
        $exp = (int) $expStmt->fetchColumn();

        if ($edu > 0) {
            $score += 25;
        }

        if ($exp > 0) {
            $score += 25;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  callable(ResumeAggregate): void  $mutator
     * @return array{success: bool, message: string}
     */
    private function transition(array $actor, int $resumeId, string $action, callable $mutator, string $success): array
    {
        $aggregate = $this->resumes->findOwnedAggregate($resumeId, (int) ($actor['id'] ?? 0));

        if ($aggregate === null) {
            return ['success' => false, 'message' => ResumeException::notFound()->getMessage()];
        }

        if (!$this->policy->allows($action, $aggregate->resume(), $actor)) {
            return ['success' => false, 'message' => ResumeException::forbidden()->getMessage()];
        }

        try {
            $mutator($aggregate);
            $this->resumes->saveAggregate($aggregate);
        } catch (ResumeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'message' => $success];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function isAdmin(array $actor): bool
    {
        return in_array((string) ($actor['role'] ?? ''), ['admin', 'super_admin', 'staff'], true);
    }
}
