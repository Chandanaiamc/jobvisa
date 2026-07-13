<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\SkillCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserSkillRepositoryInterface;
use JobVisa\App\Security\Validator;
use RuntimeException;

final class SkillService
{
    private const LEVELS = ['beginner', 'intermediate', 'expert'];

    public function __construct(
        private readonly UserSkillRepositoryInterface $userSkills,
        private readonly SkillCatalogRepositoryInterface $catalog,
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
            'items' => $this->userSkills->listByUserId($userId),
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

        $level = strtolower(trim((string) ($input['proficiency'] ?? 'intermediate')));

        if (!in_array($level, self::LEVELS, true)) {
            $level = 'intermediate';
        }

        $skillId = (int) ($input['skill_id'] ?? 0);
        $custom = trim((string) ($input['custom_skill'] ?? ''));

        if ($skillId < 1 && $custom === '') {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => ['skill_id' => ['Select a skill or enter a custom skill.']],
            ];
        }

        if ($skillId < 1) {
            $validator = Validator::make(['custom_skill' => $custom])->required('custom_skill')->max('custom_skill', 100);

            if ($validator->fails()) {
                return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()];
            }

            $skillId = $this->catalog->findOrCreateCustom($custom);
        }

        $this->userSkills->attach($userId, $skillId, $level);
        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Skill saved.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function delete(array $actor, int $userId, int $id): array
    {
        $this->assertEdit($actor, $userId);

        if (!$this->userSkills->detach($id, $userId)) {
            return ['success' => false, 'message' => 'Skill not found.'];
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Skill removed.'];
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
