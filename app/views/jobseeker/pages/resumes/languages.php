<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeLanguageDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeLanguageDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $cefr
 * @var list<string> $certificateTypes
 * @var list<string> $statuses
 * @var string $searchUrl
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeLanguageDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'languages';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages');
$cefrSelect = static function (string $name, string $value, array $cefr) use ($fieldError, $errors): string {
    $html = '<select id="' . e($name) . '" name="' . e($name) . '" required>';
    foreach ($cefr as $level) {
        $sel = $value === $level ? ' selected' : '';
        $html .= '<option value="' . e($level) . '"' . $sel . '>' . e($level) . '</option>';
    }
    $html .= '</select>' . $fieldError($errors, $name);

    return $html;
};
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Languages</h2>
            <p class="panel__lead">CEFR proficiency per resume. Profile languages (`user_languages`) stay separate.</p>
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
<section class="panel" id="languages-panel" data-search-url="<?= e($searchUrl) ?>">
    <h2 class="panel__title"><?= $editingId ? 'Edit language' : 'Add language' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="language-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="language_search">Language</label>
            <?php if ($editingId): ?>
                <input type="hidden" name="language_id" value="<?= e((string) ($old['language_id'] ?? '')) ?>">
                <input id="language_search" type="text" value="<?= e((string) ($old['language_name'] ?? '')) ?>" readonly>
            <?php else: ?>
                <input type="hidden" name="language_id" id="language_id" value="<?= e((string) ($old['language_id'] ?? '')) ?>">
                <input id="language_search" type="search" autocomplete="off" placeholder="Search language catalogue…"
                       value="<?= e((string) ($old['language_name'] ?? '')) ?>" required>
                <ul id="language-suggestions" class="skill-suggestions" hidden role="listbox"></ul>
            <?php endif; ?>
            <?= $fieldError($errors, 'language_id') ?>
        </div>
        <div class="form-field">
            <label for="speaking">Speaking</label>
            <?= $cefrSelect('speaking', (string) ($old['speaking'] ?? 'B1'), $cefr) ?>
        </div>
        <div class="form-field">
            <label for="reading">Reading</label>
            <?= $cefrSelect('reading', (string) ($old['reading'] ?? 'B1'), $cefr) ?>
        </div>
        <div class="form-field">
            <label for="writing">Writing</label>
            <?= $cefrSelect('writing', (string) ($old['writing'] ?? 'B1'), $cefr) ?>
        </div>
        <div class="form-field">
            <label for="listening">Listening</label>
            <?= $cefrSelect('listening', (string) ($old['listening'] ?? 'B1'), $cefr) ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_native" value="1" <?= !empty($old['is_native']) ? 'checked' : '' ?>>
                Native language
            </label>
        </div>
        <div class="form-field">
            <label for="certificate_type">Certificate</label>
            <select id="certificate_type" name="certificate_type">
                <option value="">None</option>
                <?php foreach ($certificateTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['certificate_type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'certificate_type') ?>
        </div>
        <div class="form-field">
            <label for="certificate_score">Certificate score</label>
            <input id="certificate_score" name="certificate_score" maxlength="32"
                   value="<?= e((string) ($old['certificate_score'] ?? '')) ?>">
            <?= $fieldError($errors, 'certificate_score') ?>
        </div>
        <div class="form-field">
            <label for="certificate_issued_at">Issue date</label>
            <input id="certificate_issued_at" type="date" name="certificate_issued_at"
                   value="<?= e((string) ($old['certificate_issued_at'] ?? '')) ?>">
            <?= $fieldError($errors, 'certificate_issued_at') ?>
        </div>
        <div class="form-field">
            <label for="certificate_expires_at">Expiry date</label>
            <input id="certificate_expires_at" type="date" name="certificate_expires_at"
                   value="<?= e((string) ($old['certificate_expires_at'] ?? '')) ?>">
            <?= $fieldError($errors, 'certificate_expires_at') ?>
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
                    <option value="<?= e($status) ?>" <?= (($old['status'] ?? 'active') === $status) ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'status') ?>
        </div>
        <div class="form-actions form-field--full btn-row">
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add language' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Certificate file</h3>
        <?php if ($editingItem->certificatePath): ?>
            <p class="muted">
                File on record.
                <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $editingId . '/certificate/download')) ?>">Download</a>
            </p>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $editingId . '/certificate/delete')) ?>"
                  onsubmit="return confirm('Remove certificate file?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove file</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $editingId . '/certificate')) ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="certificate">Upload PDF or image (max 5MB)</label>
                <input id="certificate" type="file" name="certificate" accept=".pdf,image/jpeg,image/png" required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary">Upload certificate</button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage languages.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Languages on this resume</h2>
            <p class="panel__lead">Native languages first, then sort order.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No languages linked yet.</p>
        </div>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $index => $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e($item->languageName) ?></strong>
                        <?php if ($item->isNative): ?>
                            <span class="badge badge--published">Native</span>
                        <?php endif; ?>
                        <p class="muted">
                            Speak <?= e($item->speaking) ?> · Read <?= e($item->reading) ?> ·
                            Write <?= e($item->writing) ?> · Listen <?= e($item->listening) ?>
                        </p>
                        <?php if ($item->certificateType): ?>
                            <p class="muted">
                                <?= e($item->certificateType) ?>
                                <?php if ($item->certificateScore): ?> · <?= e($item->certificateScore) ?><?php endif; ?>
                                <?php if ($item->certificatePath): ?>
                                    · <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $item->id . '/certificate/download')) ?>">Certificate</a>
                                <?php endif; ?>
                            </p>
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
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this language to trash?');">
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
                    <strong><?= e($item->languageName) ?></strong>
                    <p class="muted">deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/languages/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
