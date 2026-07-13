<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeAchievementDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeAchievementDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $statuses
 * @var list<string> $visibilities
 * @var list<string> $types
 * @var list<string> $awardLevels
 * @var list<array{id: int, title: string}> $projects
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $cities
 * @var string $query
 * @var string $searchUrl
 * @var string $citiesUrl
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeAchievementDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'achievements';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$typeLabels = [
    'award' => 'Award',
    'recognition' => 'Recognition',
    'scholarship' => 'Scholarship',
    'competition' => 'Competition',
    'publication' => 'Publication',
    'honor' => 'Honor',
    'other' => 'Other',
];
$levelLabels = [
    'local' => 'Local',
    'district' => 'District',
    'provincial' => 'Provincial',
    'national' => 'National',
    'regional' => 'Regional',
    'international' => 'International',
];
$awardLevels = $awardLevels ?? [];
$countries = $countries ?? [];
$cities = $cities ?? [];
$citiesUrl = $citiesUrl ?? '';
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/achievements/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/achievements');
$basePath = '/jobseeker/resumes/' . (int) $resume['id'] . '/achievements';
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Awards &amp; achievements</h2>
            <p class="panel__lead">Highlight recognitions per resume. Private items stay off public profiles.</p>
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

<section class="panel" id="achievements-panel" data-search-url="<?= e($searchUrl) ?>">
    <form method="get" action="<?= e(app_url($basePath)) ?>" class="achievement-search">
        <label for="achievement_q" class="sr-only">Search achievements</label>
        <input id="achievement_q" type="search" name="q" value="<?= e($query) ?>"
               placeholder="Search title, issuer, project…" autocomplete="off">
        <button type="submit" class="btn btn--secondary">Search</button>
        <?php if ($query !== ''): ?>
            <a class="btn btn--ghost" href="<?= e(app_url($basePath)) ?>" style="color:var(--brand-deep);border-color:var(--line)">Clear</a>
        <?php endif; ?>
    </form>
    <ul id="achievement-live-results" class="skill-suggestions" hidden role="listbox"></ul>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit achievement' : 'Add achievement' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="achievement-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="title">Title</label>
            <input id="title" name="title" maxlength="200" required value="<?= e((string) ($old['title'] ?? '')) ?>">
            <?= $fieldError($errors, 'title') ?>
        </div>
        <div class="form-field">
            <label for="issuer">Issuer / organization</label>
            <input id="issuer" name="issuer" maxlength="200" value="<?= e((string) ($old['issuer'] ?? '')) ?>">
            <?= $fieldError($errors, 'issuer') ?>
        </div>
        <div class="form-field">
            <label for="achievement_type">Type</label>
            <select id="achievement_type" name="achievement_type">
                <option value="">Select</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['achievement_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= e($typeLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'achievement_type') ?>
        </div>
        <div class="form-field">
            <label for="achievement_date">Date</label>
            <input id="achievement_date" type="date" name="achievement_date"
                   value="<?= e((string) ($old['achievement_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'achievement_date') ?>
        </div>
        <div class="form-field">
            <label for="project_id">Associated project</label>
            <select id="project_id" name="project_id">
                <option value="">None</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= (int) $project['id'] ?>"
                        <?= ((string) ($old['project_id'] ?? '') === (string) $project['id']) ? 'selected' : '' ?>>
                        <?= e($project['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'project_id') ?>
        </div>
        <div class="form-field">
            <label for="credential_url">Credential URL</label>
            <input id="credential_url" type="url" name="credential_url" maxlength="500"
                   value="<?= e((string) ($old['credential_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'credential_url') ?>
        </div>
        <div class="form-field">
            <label for="award_level">Award level</label>
            <select id="award_level" name="award_level">
                <option value="">Optional</option>
                <?php foreach ($awardLevels as $level): ?>
                    <option value="<?= e($level) ?>" <?= (($old['award_level'] ?? '') === $level) ? 'selected' : '' ?>>
                        <?= e($levelLabels[$level] ?? $level) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'award_level') ?>
        </div>
        <div class="form-field">
            <label for="rank_or_placement">Rank / placement</label>
            <input id="rank_or_placement" name="rank_or_placement" maxlength="120"
                   placeholder="e.g. 1st place, Gold"
                   value="<?= e((string) ($old['rank_or_placement'] ?? '')) ?>">
            <?= $fieldError($errors, 'rank_or_placement') ?>
        </div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" data-achievement-country data-cities-url="<?= e($citiesUrl) ?>">
                <option value="">Optional</option>
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
            <label for="city_id">City</label>
            <select id="city_id" name="city_id" data-achievement-city>
                <option value="">Optional</option>
                <?php
                $selectedCountry = (string) ($old['country_id'] ?? '');
                $selectedCity = (string) ($old['city_id'] ?? '');
                foreach ($cities as $city):
                    if ($selectedCountry !== '' && (string) $city['country_id'] !== $selectedCountry) {
                        continue;
                    }
                    ?>
                    <option value="<?= (int) $city['id'] ?>"
                            data-country="<?= (int) $city['country_id'] ?>"
                        <?= ($selectedCity === (string) $city['id']) ? 'selected' : '' ?>>
                        <?= e((string) $city['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'city_id') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= e((string) ($old['description'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'description') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" placeholder="Optional notes"><?= e((string) ($old['remarks'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'remarks') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_featured" value="1" <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
                Featured achievement
            </label>
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
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add achievement' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url($basePath)) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Certificate</h3>
        <p class="muted">PDF, JPG, or PNG · max 5MB. Stored securely; not shown on public profiles.</p>
        <?php if ($editingItem->certificatePath): ?>
            <p class="muted">
                Current file:
                <strong><?= e((string) ($editingItem->certificateOriginalName ?: 'Certificate')) ?></strong>
                <?php if ($editingItem->certificateMime): ?>
                    · <?= e($editingItem->certificateMime) ?>
                <?php endif; ?>
                <?php if ($editingItem->certificateSize): ?>
                    · <?= e((string) round($editingItem->certificateSize / 1024, 1)) ?> KB
                <?php endif; ?>
                · <a href="<?= e(app_url($basePath . '/' . (int) $editingId . '/certificate/download')) ?>">Download</a>
            </p>
            <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/certificate/delete')) ?>"
                  onsubmit="return confirm('Remove certificate file?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove certificate</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/certificate')) ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="certificate"><?= $editingItem->certificatePath ? 'Replace certificate' : 'Upload certificate' ?></label>
                <input id="certificate" type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary"><?= $editingItem->certificatePath ? 'Replace file' : 'Upload certificate' ?></button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage achievements.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Achievements on this resume</h2>
            <p class="panel__lead">Featured first, then sort order.</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted"><?= $query !== '' ? 'No achievements match your search.' : 'No achievements linked yet.' ?></p>
        </div>
    <?php else: ?>
        <ul class="record-list achievement-cards">
            <?php foreach ($items as $index => $item): ?>
                <li class="record achievement-card">
                    <div>
                        <div class="achievement-card__head">
                            <strong><?= e($item->title) ?></strong>
                            <?php if ($item->isFeatured): ?>
                                <span class="badge badge--published">Featured</span>
                            <?php endif; ?>
                            <span class="badge"><?= e(ucfirst($item->visibility)) ?></span>
                            <?php if ($item->achievementType): ?>
                                <span class="badge"><?= e($typeLabels[$item->achievementType] ?? $item->achievementType) ?></span>
                            <?php endif; ?>
                            <?php if ($item->awardLevel): ?>
                                <span class="badge"><?= e($levelLabels[$item->awardLevel] ?? $item->awardLevel) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item->issuer): ?>
                            <p class="muted"><?= e($item->issuer) ?></p>
                        <?php endif; ?>
                        <p class="muted">
                            <?= e((string) ($item->achievementDate ?? '—')) ?>
                            <?php if ($item->rankOrPlacement): ?> · <?= e($item->rankOrPlacement) ?><?php endif; ?>
                            <?php if ($item->countryName || $item->cityName): ?>
                                · <?= e(implode(', ', array_filter([$item->cityName, $item->countryName]))) ?>
                            <?php endif; ?>
                            <?php if ($item->projectTitle): ?> · Project: <?= e($item->projectTitle) ?><?php endif; ?>
                        </p>
                        <?php if ($item->credentialUrl || ($item->certificatePath && $canEdit)): ?>
                            <p class="muted">
                                <?php if ($item->credentialUrl): ?>
                                    <a href="<?= e($item->credentialUrl) ?>" rel="noopener noreferrer" target="_blank">Credential</a>
                                <?php endif; ?>
                                <?php if ($item->certificatePath && $canEdit): ?>
                                    <?php if ($item->credentialUrl): ?> · <?php endif; ?>
                                    <a href="<?= e(app_url($basePath . '/' . (int) $item->id . '/certificate/download')) ?>">Certificate</a>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="btn-row">
                            <?php if (count($items) > 1 && $query === ''): ?>
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
                                    <form method="post" action="<?= e(app_url($basePath . '/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($up as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move up">↑</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($index < count($ids) - 1): ?>
                                    <form method="post" action="<?= e(app_url($basePath . '/reorder')) ?>">
                                        <?= csrf_field() ?>
                                        <?php foreach ($down as $oid): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $oid ?>">
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary" type="submit" title="Move down">↓</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="btn btn--secondary" href="<?= e(app_url($basePath . '/' . (int) $item->id . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url($basePath . '/' . (int) $item->id . '/delete')) ?>"
                                  onsubmit="return confirm('Move this achievement to trash?');">
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
                    <strong><?= e($item->title) ?></strong>
                    <p class="muted">deleted <?= e((string) ($item->deletedAt ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url($basePath . '/' . (int) $item->id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--secondary" type="submit">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
