<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeProjectDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeProjectDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $statuses
 * @var list<string> $visibilities
 * @var list<string> $projectTypes
 * @var string $reorderUrl
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeProjectDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'projects';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$typeLabels = [
    'client' => 'Client project',
    'personal' => 'Personal',
    'open_source' => 'Open source',
    'academic' => 'Academic',
    'freelance' => 'Freelance',
    'internal' => 'Internal',
    'volunteer' => 'Volunteer',
    'other' => 'Other',
];
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/projects/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/projects');
$assetBase = static function (int $resumeId, int $projectId): string {
    return app_url('/jobseeker/resumes/' . $resumeId . '/projects/' . $projectId);
};
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Projects &amp; portfolio</h2>
            <p class="panel__lead">Showcase work per resume. Private projects and documents stay off public profiles.</p>
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
<section class="panel" id="projects-panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit project' : 'Add project' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="project-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="title">Project title</label>
            <input id="title" name="title" maxlength="200" required value="<?= e((string) ($old['title'] ?? '')) ?>">
            <?= $fieldError($errors, 'title') ?>
        </div>
        <div class="form-field">
            <label for="project_type">Category</label>
            <select id="project_type" name="project_type">
                <option value="">Select</option>
                <?php foreach ($projectTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['project_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= e($typeLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'project_type') ?>
        </div>
        <div class="form-field">
            <label for="role">Your role</label>
            <input id="role" name="role" maxlength="150" value="<?= e((string) ($old['role'] ?? '')) ?>">
            <?= $fieldError($errors, 'role') ?>
        </div>
        <div class="form-field">
            <label for="client_name">Client</label>
            <input id="client_name" name="client_name" maxlength="200" value="<?= e((string) ($old['client_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'client_name') ?>
        </div>
        <div class="form-field">
            <label for="organization">Organization</label>
            <input id="organization" name="organization" maxlength="200" value="<?= e((string) ($old['organization'] ?? '')) ?>">
            <?= $fieldError($errors, 'organization') ?>
        </div>
        <div class="form-field">
            <label for="industry">Industry</label>
            <input id="industry" name="industry" maxlength="150" value="<?= e((string) ($old['industry'] ?? '')) ?>">
            <?= $fieldError($errors, 'industry') ?>
        </div>
        <div class="form-field">
            <label for="location">Location</label>
            <input id="location" name="location" maxlength="200" value="<?= e((string) ($old['location'] ?? '')) ?>">
            <?= $fieldError($errors, 'location') ?>
        </div>
        <div class="form-field">
            <label for="team_size">Team size</label>
            <input id="team_size" type="number" min="1" max="10000" name="team_size"
                   value="<?= e((string) ($old['team_size'] ?? '')) ?>">
            <?= $fieldError($errors, 'team_size') ?>
        </div>
        <div class="form-field">
            <label for="start_date">Start date</label>
            <input id="start_date" type="date" name="start_date" value="<?= e((string) ($old['start_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'start_date') ?>
        </div>
        <div class="form-field">
            <label for="end_date">End date</label>
            <input id="end_date" type="date" name="end_date" value="<?= e((string) ($old['end_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'end_date') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="currently_working" id="currently_working" value="1"
                    <?= !empty($old['currently_working']) ? 'checked' : '' ?>>
                Currently working on this project
            </label>
        </div>
        <div class="form-field form-field--full">
            <label for="technologies">Technologies</label>
            <input id="technologies" name="technologies" maxlength="2000"
                   placeholder="PHP, MySQL, React…"
                   value="<?= e((string) ($old['technologies'] ?? '')) ?>">
            <p class="muted" style="margin:0.35rem 0 0">Comma-separated tags.</p>
            <div id="tech-tags" class="tech-tags" aria-live="polite"></div>
            <?= $fieldError($errors, 'technologies') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= e((string) ($old['description'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'description') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="responsibilities">Responsibilities</label>
            <textarea id="responsibilities" name="responsibilities" rows="3"><?= e((string) ($old['responsibilities'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'responsibilities') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="achievements">Achievements</label>
            <textarea id="achievements" name="achievements" rows="3"><?= e((string) ($old['achievements'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'achievements') ?>
        </div>
        <div class="form-field">
            <label for="project_url">Website</label>
            <input id="project_url" type="url" name="project_url" maxlength="500" value="<?= e((string) ($old['project_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'project_url') ?>
        </div>
        <div class="form-field">
            <label for="github_url">GitHub</label>
            <input id="github_url" type="url" name="github_url" maxlength="500" value="<?= e((string) ($old['github_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'github_url') ?>
        </div>
        <div class="form-field">
            <label for="portfolio_url">Portfolio</label>
            <input id="portfolio_url" type="url" name="portfolio_url" maxlength="500" value="<?= e((string) ($old['portfolio_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'portfolio_url') ?>
        </div>
        <div class="form-field">
            <label for="video_demo_url">Video demo</label>
            <input id="video_demo_url" type="url" name="video_demo_url" maxlength="500" value="<?= e((string) ($old['video_demo_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'video_demo_url') ?>
        </div>
        <div class="form-field">
            <label for="visibility">Visibility</label>
            <select id="visibility" name="visibility" required>
                <?php foreach ($visibilities as $vis): ?>
                    <option value="<?= e($vis) ?>" <?= (($old['visibility'] ?? 'public') === $vis) ? 'selected' : '' ?>>
                        <?= e(ucfirst($vis)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'visibility') ?>
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
            <label for="sort_order">Sort order</label>
            <input id="sort_order" type="number" min="0" max="9999" name="sort_order"
                   value="<?= e((string) ($old['sort_order'] ?? '0')) ?>">
            <?= $fieldError($errors, 'sort_order') ?>
        </div>
        <div class="form-actions form-field--full btn-row">
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add project' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/projects')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Screenshot</h3>
        <?php if ($editingItem->image): ?>
            <p class="muted">
                Image on record.
                <a href="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/image/download') ?>">Download</a>
            </p>
            <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/image/delete') ?>"
                  onsubmit="return confirm('Remove project image?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove image</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/image') ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="image">Upload JPG/PNG/WebP (max 5MB)</label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary">Upload image</button>
            </div>
        </form>

        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Document</h3>
        <p class="muted">Private project documents are never shown on public profiles.</p>
        <?php if ($editingItem->document): ?>
            <p class="muted">
                Document on record.
                <a href="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/document/download') ?>">Download</a>
            </p>
            <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/document/delete') ?>"
                  onsubmit="return confirm('Remove project document?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove document</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $editingId) . '/document') ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="document">Upload PDF (max 5MB)</label>
                <input id="document" type="file" name="document" accept="application/pdf,.pdf" required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary">Upload document</button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage projects.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Projects on this resume</h2>
            <p class="panel__lead"><?= $canEdit ? 'Drag cards to reorder, or use ↑↓.' : 'Ordered by sort position.' ?></p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No projects linked yet.</p>
        </div>
    <?php else: ?>
        <ul class="record-list project-cards" id="project-list"
            data-reorder-url="<?= e($reorderUrl) ?>"
            data-can-drag="<?= $canEdit ? '1' : '0' ?>">
            <?php foreach ($items as $index => $item): ?>
                <li class="record project-card" data-id="<?= (int) $item->id ?>" <?= $canEdit ? 'draggable="true"' : '' ?>>
                    <?php if ($canEdit): ?>
                        <span class="project-card__handle" title="Drag to reorder" aria-hidden="true">⋮⋮</span>
                    <?php endif; ?>
                    <div class="project-card__body">
                        <div class="project-card__head">
                            <strong><?= e($item->title) ?></strong>
                            <?php if ($item->currentlyWorking): ?>
                                <span class="badge badge--published">Current</span>
                            <?php endif; ?>
                            <span class="badge"><?= e(ucfirst($item->visibility)) ?></span>
                            <?php if ($item->projectType): ?>
                                <span class="badge"><?= e($typeLabels[$item->projectType] ?? $item->projectType) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item->role || $item->organization || $item->clientName): ?>
                            <p class="muted">
                                <?= e(implode(' · ', array_filter([
                                    $item->role,
                                    $item->organization,
                                    $item->clientName ? 'Client: ' . $item->clientName : null,
                                ]))) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($item->technologies !== []): ?>
                            <div class="tech-tags">
                                <?php foreach ($item->technologies as $tech): ?>
                                    <span class="tech-tag"><?= e($tech) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="muted">
                            <?= e((string) ($item->startDate ?? '—')) ?>
                            –
                            <?= $item->currentlyWorking ? 'Present' : e((string) ($item->endDate ?? '—')) ?>
                            <?php if ($item->image): ?> · Screenshot<?php endif; ?>
                            <?php if ($item->document && $canEdit): ?> · Document<?php endif; ?>
                        </p>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="btn-row project-card__actions">
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
                                    <form method="post" action="<?= e($reorderUrl) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e($reorderUrl) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e($assetBase((int) $resume['id'], (int) $item->id) . '/edit') ?>">Edit</a>
                            <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $item->id) . '/delete') ?>"
                                  onsubmit="return confirm('Move this project to trash?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($canEdit): ?>
            <form id="project-dnd-form" method="post" action="<?= e($reorderUrl) ?>" hidden>
                <?= csrf_field() ?>
                <div id="project-dnd-order"></div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($canEdit && $deleted !== []): ?>
<section class="panel">
    <h2 class="panel__title">Trash</h2>
    <ul class="record-list">
        <?php foreach ($deleted as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e($item->title) ?></strong>
                    <p class="muted">deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e($assetBase((int) $resume['id'], (int) $item->id) . '/restore') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
