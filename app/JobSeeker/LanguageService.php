<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserLanguageRepositoryInterface;
use JobVisa\App\Security\Validator;
use RuntimeException;

final class LanguageService
{
    private const LEVELS = ['basic', 'conversational', 'fluent', 'native'];

    public function __construct(
        private readonly UserLanguageRepositoryInterface $userLanguages,
        private readonly LanguageCatalogRepositoryInterface $catalog,
        private readonly ProfileCompletenessService $completeness,
        private readonly ProfileAccess $access
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{items: list<array<string, mixed>>, catalog: list<array<string, mixed>>}
     */
    public function page(array $actor, int $userId): array
    {
        $this->assertView($actor, $userId);

        return [
            'items' => $this->userLanguages->listByUserId($userId),
            'catalog' => $this->catalog->listActive(),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function store(array $actor, int $userId, array $input): array
    {
        $this->assertEdit($actor, $userId);
        $validated = $this->validate($input);

        if ($validated['errors'] !== null) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validated['errors']];
        }

        $this->userLanguages->create($userId, $validated['data']);
        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Language added.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function update(array $actor, int $userId, int $id, array $input): array
    {
        $this->assertEdit($actor, $userId);
        $validated = $this->validate($input);

        if ($validated['errors'] !== null) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validated['errors']];
        }

        if (!$this->userLanguages->update($id, $userId, $validated['data'])) {
            return ['success' => false, 'message' => 'Language record not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Language updated.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $userId, int $id): array
    {
        $this->assertEdit($actor, $userId);

        if (!$this->userLanguages->delete($id, $userId)) {
            return ['success' => false, 'message' => 'Language record not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Language removed.'];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{data: array<string, mixed>, errors: array<string, list<string>>|null}
     */
    private function validate(array $input): array
    {
        $validator = Validator::make($input)->required('language_id');

        if ($validator->fails()) {
            return ['data' => [], 'errors' => $validator->errors()];
        }

        $speaking = $this->level($input['speaking'] ?? 'conversational');
        $reading = $this->level($input['reading'] ?? $speaking);
        $writing = $this->level($input['writing'] ?? $speaking);

        return [
            'errors' => null,
            'data' => [
                'language_id' => (int) $input['language_id'],
                'speaking' => $speaking,
                'reading' => $reading,
                'writing' => $writing,
                'proficiency' => $speaking,
            ],
        ];
    }

    private function level(mixed $value): string
    {
        $level = strtolower(trim((string) $value));

        return in_array($level, self::LEVELS, true) ? $level : 'conversational';
    }

    /** @param array<string, mixed> $actor */
    private function assertView(array $actor, int $userId): void
    {
        if (!$this->access->canView($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }

    /** @param array<string, mixed> $actor */
    private function assertEdit(array $actor, int $userId): void
    {
        if (!$this->access->canEdit($actor, $userId)) {
            throw new RuntimeException('Forbidden');
        }
    }
}
