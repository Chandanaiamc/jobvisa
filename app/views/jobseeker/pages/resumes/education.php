<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeEducationDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeEducationDTO> $deleted
 * @var list<array<string, mixed>> $countries
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $qualificationTypes
 * @var list<string> $statuses
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'education';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$readonly = !$canEdit;
$typeLabels = [
    'high_school' => 'High school',
    'certificate' => 'Certificate',
    'diploma' => 'Diploma',
    'associate' => 'Associate degree',
    'bachelor' => 'Bachelor’s',
    'master' => 'Master’s',
    'doctorate' => 'Doctorate / PhD',
    'vocational' => 'Vocational',
    'professional' => 'Professional',
    'other' => 'Other',
];
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education');
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Education</h2>
            <p class="panel__lead">Add qualifications for this resume. Records are stored on the shared education table and scored for completion.</p>
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
    <h2 class="panel__title"><?= $editingId ? 'Edit education' : 'Add education' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="education-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="institution">Institution / school / university</label>
            <input id="institution" name="institution" required maxlength="200"
                   value="<?= e((string) ($old['institution'] ?? '')) ?>">
            <?= $fieldError($errors, 'institution') ?>
        </div>
        <div class="form-field">
            <label for="school">Campus / school name (optional)</label>
            <input id="school" name="school" maxlength="200" value="<?= e((string) ($old['school'] ?? '')) ?>">
            <?= $fieldError($errors, 'school') ?>
        </div>
        <div class="form-field">
            <label for="qualification_type">Qualification type</label>
            <select id="qualification_type" name="qualification_type" required>
                <option value="">Select</option>
                <?php foreach ($qualificationTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['qualification_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= e($typeLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'qualification_type') ?>
        </div>
        <div class="form-field">
            <label for="degree">Qualification title</label>
            <input id="degree" name="degree" required maxlength="150"
                   value="<?= e((string) ($old['degree'] ?? '')) ?>">
            <?= $fieldError($errors, 'degree') ?>
        </div>
        <div class="form-field">
            <label for="field_of_study">Field of study</label>
            <input id="field_of_study" name="field_of_study" maxlength="150"
                   value="<?= e((string) ($old['field_of_study'] ?? '')) ?>">
            <?= $fieldError($errors, 'field_of_study') ?>
        </div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id">
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
        <div class="form-field">
            <label for="grade">Grade / GPA</label>
            <input id="grade" name="grade" maxlength="64" value="<?= e((string) ($old['grade'] ?? '')) ?>">
            <?= $fieldError($errors, 'grade') ?>
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
        <div class="form-field form-field--full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= e((string) ($old['description'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'description') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_current" value="1" id="is_current"
                    <?= !empty($old['is_current']) ? 'checked' : '' ?>>
                Currently studying
            </label>
        </div>
        <div class="form-actions form-field--full btn-row">
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add education' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php elseif (!$canEdit): ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can add or edit education.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Your education records</h2>
            <p class="panel__lead">Ordered by display order, then most recent start date.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No education records yet. Add your first qualification above.</p>
        </div>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $index => $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e($item->degree) ?></strong>
                        <?php if ($item->isCurrent): ?>
                            <span class="badge badge--published">Current</span>
                        <?php endif; ?>
                        <span class="badge badge--draft"><?= e(ucfirst($item->status)) ?></span>
                        <p>
                            <?= e($item->institution) ?>
                            <?php if ($item->school): ?> · <?= e($item->school) ?><?php endif; ?>
                        </p>
                        <p class="muted">
                            <?= e($typeLabels[$item->qualificationType ?? ''] ?? (string) ($item->qualificationType ?? '—')) ?>
                            <?php if ($item->fieldOfStudy): ?> · <?= e($item->fieldOfStudy) ?><?php endif; ?>
                            <?php if ($item->grade): ?> · Grade <?= e($item->grade) ?><?php endif; ?>
                        </p>
                        <p class="muted">
                            <?php if ($item->countryName || $item->city): ?>
                                <?= e(trim(($item->city ? $item->city . ', ' : '') . ($item->countryName ?? ''), ', ')) ?> ·
                            <?php endif; ?>
                            <?= e((string) ($item->startDate ?? '')) ?>
                            –
                            <?= $item->isCurrent ? 'Present' : e((string) ($item->endDate ?? '')) ?>
                        </p>
                        <?php if ($item->description): ?>
                            <p><?= nl2br(e($item->description)) ?></p>
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
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this education record to trash?');">
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
                    <strong><?= e($item->degree) ?></strong>
                    <p class="muted"><?= e($item->institution) ?> · deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/education/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
