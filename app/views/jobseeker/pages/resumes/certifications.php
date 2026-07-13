<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeCertificationDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeCertificationDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $statuses
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeCertificationDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'certifications';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications');
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Certifications &amp; licences</h2>
            <p class="panel__lead">Resume-scoped credentials. Separate from profile personal licence fields.</p>
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
<section class="panel" id="certifications-panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit certification' : 'Add certification' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="certification-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="name">Certification name</label>
            <input id="name" name="name" maxlength="200" required value="<?= e((string) ($old['name'] ?? '')) ?>">
            <?= $fieldError($errors, 'name') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="issuing_organization">Issuing organization</label>
            <input id="issuing_organization" name="issuing_organization" maxlength="200" required
                   value="<?= e((string) ($old['issuing_organization'] ?? '')) ?>">
            <?= $fieldError($errors, 'issuing_organization') ?>
        </div>
        <div class="form-field">
            <label for="credential_id">Credential ID</label>
            <input id="credential_id" name="credential_id" maxlength="120"
                   value="<?= e((string) ($old['credential_id'] ?? '')) ?>">
            <?= $fieldError($errors, 'credential_id') ?>
        </div>
        <div class="form-field">
            <label for="license_number">License number</label>
            <input id="license_number" name="license_number" maxlength="120"
                   value="<?= e((string) ($old['license_number'] ?? '')) ?>">
            <?= $fieldError($errors, 'license_number') ?>
        </div>
        <div class="form-field">
            <label for="credential_url">Credential URL</label>
            <input id="credential_url" type="url" name="credential_url" maxlength="500"
                   value="<?= e((string) ($old['credential_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'credential_url') ?>
        </div>
        <div class="form-field">
            <label for="verification_url">Verification URL</label>
            <input id="verification_url" type="url" name="verification_url" maxlength="500"
                   value="<?= e((string) ($old['verification_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'verification_url') ?>
        </div>
        <div class="form-field">
            <label for="issue_date">Issue date</label>
            <input id="issue_date" type="date" name="issue_date" required
                   value="<?= e((string) ($old['issue_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'issue_date') ?>
        </div>
        <div class="form-field">
            <label for="expiry_date">Expiry date</label>
            <input id="expiry_date" type="date" name="expiry_date"
                   value="<?= e((string) ($old['expiry_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'expiry_date') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="does_not_expire" id="does_not_expire" value="1"
                    <?= !empty($old['does_not_expire']) ? 'checked' : '' ?>>
                Does not expire
            </label>
            <label>
                <input type="checkbox" name="is_primary" value="1" <?= !empty($old['is_primary']) ? 'checked' : '' ?>>
                Primary certification
            </label>
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
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add certification' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications')) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Certificate file</h3>
        <?php if ($editingItem->certificatePath): ?>
            <p class="muted">
                File on record.
                <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $editingId . '/certificate/download')) ?>">Download</a>
            </p>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $editingId . '/certificate/delete')) ?>"
                  onsubmit="return confirm('Remove certificate file?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove file</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $editingId . '/certificate')) ?>"
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
    <p class="muted">Read-only view. Only the resume owner can manage certifications.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Certifications on this resume</h2>
            <p class="panel__lead">Primary first, then sort order.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted">No certifications linked yet.</p>
        </div>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $index => $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e($item->name) ?></strong>
                        <?php if ($item->isPrimary): ?>
                            <span class="badge badge--published">Primary</span>
                        <?php endif; ?>
                        <p class="muted"><?= e($item->issuingOrganization) ?></p>
                        <p class="muted">
                            Issued <?= e((string) ($item->issueDate ?? '—')) ?>
                            <?php if ($item->doesNotExpire): ?>
                                · Does not expire
                            <?php elseif ($item->expiryDate): ?>
                                · Expires <?= e($item->expiryDate) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($item->credentialId || $item->licenseNumber): ?>
                            <p class="muted">
                                <?php if ($item->credentialId): ?>ID: <?= e($item->credentialId) ?><?php endif; ?>
                                <?php if ($item->credentialId && $item->licenseNumber): ?> · <?php endif; ?>
                                <?php if ($item->licenseNumber): ?>License: <?= e($item->licenseNumber) ?><?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($item->certificatePath || $item->credentialUrl || $item->verificationUrl): ?>
                            <p class="muted">
                                <?php if ($item->certificatePath): ?>
                                    <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $item->id . '/certificate/download')) ?>">Certificate</a>
                                <?php endif; ?>
                                <?php if ($item->credentialUrl): ?>
                                    <?php if ($item->certificatePath): ?> · <?php endif; ?>
                                    <a href="<?= e($item->credentialUrl) ?>" rel="noopener noreferrer" target="_blank">Credential</a>
                                <?php endif; ?>
                                <?php if ($item->verificationUrl): ?>
                                    <?php if ($item->certificatePath || $item->credentialUrl): ?> · <?php endif; ?>
                                    <a href="<?= e($item->verificationUrl) ?>" rel="noopener noreferrer" target="_blank">Verify</a>
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
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this certification to trash?');">
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
                    <strong><?= e($item->name) ?></strong>
                    <p class="muted">deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/certifications/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
