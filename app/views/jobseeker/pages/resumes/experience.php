<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeExperienceDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeExperienceDTO> $deleted
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $skillOptions
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var bool $includePrivate
 * @var list<string> $employmentTypes
 * @var list<string> $statuses
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'experience';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$typeLabels = [
    'full_time' => 'Full-time',
    'part_time' => 'Part-time',
    'contract' => 'Contract',
    'temporary' => 'Temporary',
    'internship' => 'Internship',
    'apprenticeship' => 'Apprenticeship',
    'freelance' => 'Freelance',
    'self_employed' => 'Self-employed',
    'volunteer' => 'Volunteer',
];
$selectedSkills = array_map('intval', is_array($old['skill_ids'] ?? null) ? $old['skill_ids'] : []);
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience');
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Work experience</h2>
            <p class="panel__lead">Add roles for this resume. Multiple current roles are allowed. Private fields stay off public views.</p>
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
<section class="panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit experience' : 'Add experience' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="experience-form">
        <?= csrf_field() ?>
        <div class="form-field">
            <label for="company_name">Company name</label>
            <input id="company_name" name="company_name" required maxlength="200"
                   value="<?= e((string) ($old['company_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'company_name') ?>
        </div>
        <div class="form-field">
            <label for="job_title">Job title</label>
            <input id="job_title" name="job_title" required maxlength="150"
                   value="<?= e((string) ($old['job_title'] ?? '')) ?>">
            <?= $fieldError($errors, 'job_title') ?>
        </div>
        <div class="form-field">
            <label for="employment_type">Employment type</label>
            <select id="employment_type" name="employment_type" required>
                <option value="">Select</option>
                <?php foreach ($employmentTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['employment_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= e($typeLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'employment_type') ?>
        </div>
        <div class="form-field">
            <label for="industry">Industry</label>
            <input id="industry" name="industry" maxlength="150" value="<?= e((string) ($old['industry'] ?? '')) ?>">
            <?= $fieldError($errors, 'industry') ?>
        </div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" required>
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>"
                        <?= ((string) ($old['country_id'] ?? '') === (string) $country['id']) ? 'selected' : '' ?>>
                        <?= e((string) $country['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'country_id') ?>
        </div>
        <div class="form-field">
            <label for="city">City</label>
            <input id="city" name="city" maxlength="120" value="<?= e((string) ($old['city'] ?? '')) ?>">
            <?= $fieldError($errors, 'city') ?>
        </div>
        <div class="form-field">
            <label for="start_date">Start date</label>
            <input id="start_date" type="date" name="start_date" required
                   value="<?= e((string) ($old['start_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'start_date') ?>
        </div>
        <div class="form-field">
            <label for="end_date">End date</label>
            <input id="end_date" type="date" name="end_date"
                   value="<?= e((string) ($old['end_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'end_date') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_current" value="1" id="is_current"
                    <?= !empty($old['is_current']) ? 'checked' : '' ?>>
                Currently working
            </label>
        </div>
        <div class="form-field form-field--full">
            <label for="responsibilities">Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="5"><?= e((string) ($old['responsibilities'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'responsibilities') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="achievements">Achievements</label>
            <textarea id="achievements" name="achievements" rows="4"><?= e((string) ($old['achievements'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'achievements') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="skill_ids">Skills used</label>
            <select id="skill_ids" name="skill_ids[]" multiple size="8" class="skill-multi">
                <?php foreach ($skillOptions as $skill): ?>
                    <option value="<?= (int) $skill['id'] ?>"
                        <?= in_array((int) $skill['id'], $selectedSkills, true) ? 'selected' : '' ?>>
                        <?= e((string) $skill['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">Hold Ctrl/Cmd to select multiple. Uses the master skills catalogue.</p>
            <?= $fieldError($errors, 'skill_ids') ?>
        </div>
        <div class="form-field">
            <label for="supervisor_name">Supervisor name (optional)</label>
            <input id="supervisor_name" name="supervisor_name" maxlength="150"
                   value="<?= e((string) ($old['supervisor_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'supervisor_name') ?>
        </div>
        <div class="form-field">
            <label for="supervisor_contact">Supervisor contact (private)</label>
            <input id="supervisor_contact" name="supervisor_contact" maxlength="150"
                   value="<?= e((string) ($old['supervisor_contact'] ?? '')) ?>">
            <?= $fieldError($errors, 'supervisor_contact') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="reason_for_leaving">Reason for leaving (optional, private)</label>
            <textarea id="reason_for_leaving" name="reason_for_leaving" rows="2"><?= e((string) ($old['reason_for_leaving'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'reason_for_leaving') ?>
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
        <div class="form-field">
            <label for="sort_order">Display order</label>
            <input id="sort_order" type="number" min="0" max="9999" name="sort_order"
                   value="<?= e((string) ($old['sort_order'] ?? '0')) ?>">
            <?= $fieldError($errors, 'sort_order') ?>
        </div>
        <div class="form-actions form-field--full btn-row">
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add experience' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can add or edit experience.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Your experience records</h2>
            <p class="panel__lead">Current roles first, then reverse chronological by start date.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No work experience yet. Add your first role above.</p>
        </div>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $index => $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e($item->jobTitle) ?></strong>
                        <?php if ($item->isCurrent): ?>
                            <span class="badge badge--published">Current</span>
                        <?php endif; ?>
                        <span class="badge badge--draft"><?= e(ucfirst($item->status)) ?></span>
                        <p><?= e($item->companyName) ?>
                            <?php if ($item->employmentType): ?>
                                · <?= e($typeLabels[$item->employmentType] ?? $item->employmentType) ?>
                            <?php endif; ?>
                        </p>
                        <p class="muted">
                            <?php if ($item->industry): ?><?= e($item->industry) ?> · <?php endif; ?>
                            <?php if ($item->countryName || $item->city): ?>
                                <?= e(trim(($item->city ? $item->city . ', ' : '') . ($item->countryName ?? ''), ', ')) ?> ·
                            <?php endif; ?>
                            <?= e((string) ($item->startDate ?? '')) ?>
                            –
                            <?= $item->isCurrent ? 'Present' : e((string) ($item->endDate ?? '')) ?>
                        </p>
                        <?php if ($item->responsibilities): ?>
                            <p><strong>Responsibilities:</strong> <?= nl2br(e($item->responsibilities)) ?></p>
                        <?php endif; ?>
                        <?php if ($item->achievements): ?>
                            <p><strong>Achievements:</strong> <?= nl2br(e($item->achievements)) ?></p>
                        <?php endif; ?>
                        <?php if ($item->skills !== []): ?>
                            <p class="muted">Skills:
                                <?= e(implode(', ', array_map(static fn ($s) => (string) $s['name'], $item->skills))) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($includePrivate && ($item->reasonForLeaving || $item->supervisorContact)): ?>
                            <p class="muted" style="font-size:0.85rem">
                                <?php if ($item->supervisorName): ?>Supervisor: <?= e($item->supervisorName) ?><?php endif; ?>
                                <?php if ($item->supervisorContact): ?> · Contact: <?= e($item->supervisorContact) ?><?php endif; ?>
                                <?php if ($item->reasonForLeaving): ?><br>Leaving: <?= e($item->reasonForLeaving) ?><?php endif; ?>
                            </p>
                        <?php elseif ($item->supervisorName): ?>
                            <p class="muted" style="font-size:0.85rem">Supervisor: <?= e($item->supervisorName) ?></p>
                        <?php endif; ?>
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
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this experience record to trash?');">
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
    <p class="panel__lead">Soft-deleted records can be restored.</p>
    <ul class="record-list">
        <?php foreach ($deleted as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e($item->jobTitle) ?></strong>
                    <p class="muted"><?= e($item->companyName) ?> · deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/experience/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
