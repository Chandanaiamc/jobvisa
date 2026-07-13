<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeSkillDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeSkillDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $levels
 * @var list<string> $statuses
 * @var string $searchUrl
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'skills';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$levelLabels = [
    'beginner' => 'Beginner',
    'intermediate' => 'Intermediate',
    'advanced' => 'Advanced',
    'expert' => 'Expert',
];
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills');
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Skills</h2>
            <p class="panel__lead">Link catalogue skills to this resume. Profile skills (`user_skills`) stay separate.</p>
        </div>
        <div class="completeness" style="min-width:200px">
            <div class="completeness__meta">
                <span>Resume completion</span>
                <strong><?= (int) $completion['score'] ?>%</strong>
            </div>
            <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $completion['score'] ?>" aria-valuemin="0" aria-valuemax="100">
                <span style="width: <?= (int) $completion['score'] ?>%"></span>
            </div>
        </div>
    </div>
</section>

<?php if ($canEdit): ?>
<section class="panel" id="skills-panel" data-search-url="<?= e($searchUrl) ?>">
    <h2 class="panel__title"><?= $editingId ? 'Edit skill' : 'Add skill' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="skill-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="skill_search">Skill</label>
            <?php if ($editingId): ?>
                <input type="hidden" name="skill_id" value="<?= e((string) ($old['skill_id'] ?? '')) ?>">
                <input id="skill_search" type="text" value="<?= e((string) ($old['skill_name'] ?? '')) ?>" readonly>
                <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">Skill catalogue entry is fixed on edit. Remove and re-add to change skill.</p>
            <?php else: ?>
                <input type="hidden" name="skill_id" id="skill_id" value="<?= e((string) ($old['skill_id'] ?? '')) ?>">
                <input id="skill_search" type="search" autocomplete="off" placeholder="Start typing to search catalogue…"
                       value="<?= e((string) ($old['skill_name'] ?? '')) ?>" required>
                <ul id="skill-suggestions" class="skill-suggestions" hidden role="listbox"></ul>
            <?php endif; ?>
            <?= $fieldError($errors, 'skill_id') ?>
        </div>
        <div class="form-field">
            <label for="level">Level</label>
            <select id="level" name="level" required>
                <?php foreach ($levels as $level): ?>
                    <option value="<?= e($level) ?>" <?= (($old['level'] ?? 'intermediate') === $level) ? 'selected' : '' ?>>
                        <?= e($levelLabels[$level] ?? $level) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'level') ?>
        </div>
        <div class="form-field">
            <label for="years_experience">Years of experience</label>
            <input id="years_experience" type="number" min="0" max="60" step="0.5" name="years_experience"
                   value="<?= e((string) ($old['years_experience'] ?? '')) ?>">
            <?= $fieldError($errors, 'years_experience') ?>
        </div>
        <div class="form-field">
            <label for="last_used_year">Last used year</label>
            <input id="last_used_year" type="number" min="1950" max="<?= (int) date('Y') + 1 ?>" name="last_used_year"
                   value="<?= e((string) ($old['last_used_year'] ?? '')) ?>">
            <?= $fieldError($errors, 'last_used_year') ?>
        </div>
        <div class="form-field">
            <label for="sort_order">Sort order</label>
            <input id="sort_order" type="number" min="0" max="9999" name="sort_order"
                   value="<?= e((string) ($old['sort_order'] ?? '0')) ?>">
            <?= $fieldError($errors, 'sort_order') ?>
        </div>
        <div class="form-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= (($old['status'] ?? 'active') === $status) ? 'selected' : '' ?>>
                        <?= e(ucfirst($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'status') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_primary" value="1" <?= !empty($old['is_primary']) ? 'checked' : '' ?>>
                Primary skill
            </label>
        </div>
        <div class="form-actions form-field--full btn-row">
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add skill' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage skills.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Skills on this resume</h2>
            <p class="panel__lead">Primary skills first, then sort order.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No skills linked yet. Search the catalogue above to add one.</p>
        </div>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $index => $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e($item->skillName) ?></strong>
                        <?php if ($item->isPrimary): ?>
                            <span class="badge badge--published">Primary</span>
                        <?php endif; ?>
                        <span class="badge badge--draft"><?= e($levelLabels[$item->level] ?? $item->level) ?></span>
                        <p class="muted">
                            <?php if ($item->yearsExperience !== null): ?>
                                <?= e($item->yearsExperience) ?> yrs
                            <?php endif; ?>
                            <?php if ($item->lastUsedYear !== null): ?>
                                · Last used <?= (int) $item->lastUsedYear ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="btn-row">
                            <?php if (count($items) > 1): ?>
                                <?php
                                $ids = array_map(static fn ($r) => (int) $r->id, $items);
                                if ($index > 0) {
                                    $up = $ids;
                                    [$up[$index - 1], $up[$index]] = [$up[$index], $up[$index - 1]];
                                }
                                if ($index < count($ids) - 1) {
                                    $down = $ids;
                                    [$down[$index], $down[$index + 1]] = [$down[$index + 1], $down[$index]];
                                }
                                ?>
                                <?php if ($index > 0): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this skill to trash?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($canEdit && $deleted !== []): ?>
<section class="panel">
    <h2 class="panel__title">Trash</h2>
    <ul class="record-list">
        <?php foreach ($deleted as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e($item->skillName) ?></strong>
                    <p class="muted">deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skills/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
