<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeReferenceDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeReferenceDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $visibilities
 * @var list<string> $statuses
 * @var list<string> $sorts
 * @var list<string> $relationships
 * @var array<string, string> $relationshipLabels
 * @var list<array{id: int, title: string}> $projects
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $cities
 * @var array<string, string> $filters
 * @var array{total: int, page: int, per_page: int, last_page: int} $pagination
 * @var string $searchUrl
 * @var string $citiesUrl
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeReferenceDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'references';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$visibilityLabels = [
    'public' => 'Public',
    'employers' => 'Employers Only',
    'private' => 'Private',
];
$sortLabels = [
    'sort_order' => 'Sort order',
    'newest' => 'Newest',
    'oldest' => 'Oldest',
    'name' => 'Name',
    'featured' => 'Featured',
];
$relationships = $relationships ?? [];
$relationshipLabels = $relationshipLabels ?? [];
$countries = $countries ?? [];
$cities = $cities ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 10, 'last_page' => 1];
$citiesUrl = $citiesUrl ?? '';
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/references/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/references');
$basePath = '/jobseeker/resumes/' . (int) $resume['id'] . '/references';
$queryParams = array_filter($filters, static fn ($v) => $v !== '' && $v !== null);
$activeFilters = $queryParams;
unset($activeFilters['sort']);
if (($filters['sort'] ?? 'sort_order') !== 'sort_order') {
    $activeFilters['sort'] = $filters['sort'];
}
$hasFilters = $activeFilters !== [];
$canReorder = ($filters['sort'] ?? 'sort_order') === 'sort_order' && !$hasFilters;
$buildPageUrl = static function (int $page) use ($basePath, $queryParams): string {
    $params = array_merge($queryParams, ['page' => (string) $page]);

    return app_url($basePath . '?' . http_build_query($params));
};
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Professional references</h2>
            <p class="panel__lead">Add people who can speak to your work. Contact details stay private unless you grant permission and set visibility accordingly.</p>
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

<section class="panel" id="references-panel" data-search-url="<?= e($searchUrl) ?>">
    <form method="get" action="<?= e(app_url($basePath)) ?>" class="publication-filters">
        <div class="publication-filters__row">
            <label for="reference_q" class="sr-only">Search references</label>
            <input id="reference_q" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>"
                   placeholder="Search name, company, position, email, relationship…" autocomplete="off">
            <button type="submit" class="btn btn--secondary">Search</button>
            <?php if ($hasFilters): ?>
                <a class="btn btn--ghost" href="<?= e(app_url($basePath)) ?>" style="color:var(--brand-deep);border-color:var(--line)">Clear</a>
            <?php endif; ?>
        </div>
        <div class="publication-filters__grid">
            <div class="form-field">
                <label for="filter_relationship">Relationship</label>
                <select id="filter_relationship" name="relationship">
                    <option value="">Any</option>
                    <?php foreach ($relationships as $rel): ?>
                        <option value="<?= e($rel) ?>" <?= (($filters['relationship'] ?? '') === $rel) ? 'selected' : '' ?>>
                            <?= e($relationshipLabels[$rel] ?? $rel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_featured">Featured</label>
                <select id="filter_featured" name="is_featured">
                    <option value="">Any</option>
                    <option value="1" <?= (($filters['is_featured'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= (($filters['is_featured'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_visibility">Visibility</label>
                <select id="filter_visibility" name="visibility">
                    <option value="">Any</option>
                    <?php foreach ($visibilities as $vis): ?>
                        <option value="<?= e($vis) ?>" <?= (($filters['visibility'] ?? '') === $vis) ? 'selected' : '' ?>>
                            <?= e($visibilityLabels[$vis] ?? $vis) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_status">Status</label>
                <select id="filter_status" name="status">
                    <option value="">Any</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>>
                            <?= e(ucfirst($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_country">Country</label>
                <select id="filter_country" name="country_id">
                    <option value="">Any</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= (int) $country['id'] ?>"
                            <?= ((string) ($filters['country_id'] ?? '') === (string) $country['id']) ? 'selected' : '' ?>>
                            <?= e((string) $country['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_permission">Permission to contact</label>
                <select id="filter_permission" name="permission_to_contact">
                    <option value="">Any</option>
                    <option value="1" <?= (($filters['permission_to_contact'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= (($filters['permission_to_contact'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_sort">Sort by</label>
                <select id="filter_sort" name="sort">
                    <?php foreach ($sorts as $sort): ?>
                        <option value="<?= e($sort) ?>" <?= (($filters['sort'] ?? 'sort_order') === $sort) ? 'selected' : '' ?>>
                            <?= e($sortLabels[$sort] ?? $sort) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
    <ul id="reference-live-results" class="skill-suggestions" hidden role="listbox"></ul>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit reference' : 'Add reference' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <p class="muted" style="margin-top:0">
        Email and phone are never shown on public profiles. Employers see contact details only when visibility is Public or Employers Only <em>and</em> permission to contact is enabled. Private references stay off public and employer views.
    </p>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="reference-form">
        <?= csrf_field() ?>
        <div class="form-field">
            <label for="name">Name</label>
            <input id="name" name="name" maxlength="200" required value="<?= e((string) ($old['name'] ?? '')) ?>">
            <?= $fieldError($errors, 'name') ?>
        </div>
        <div class="form-field">
            <label for="designation">Position</label>
            <input id="designation" name="designation" maxlength="200"
                   value="<?= e((string) ($old['designation'] ?? $old['position'] ?? '')) ?>">
            <?= $fieldError($errors, 'designation') ?>
        </div>
        <div class="form-field">
            <label for="company">Company</label>
            <input id="company" name="company" maxlength="200" value="<?= e((string) ($old['company'] ?? '')) ?>">
            <?= $fieldError($errors, 'company') ?>
        </div>
        <div class="form-field">
            <label for="relationship">Relationship</label>
            <select id="relationship" name="relationship">
                <option value="">Select</option>
                <?php
                $selectedRel = (string) ($old['relationship'] ?? '');
                foreach ($relationships as $rel):
                    ?>
                    <option value="<?= e($rel) ?>" <?= ($selectedRel === $rel) ? 'selected' : '' ?>>
                        <?= e($relationshipLabels[$rel] ?? $rel) ?>
                    </option>
                <?php endforeach; ?>
                <?php if ($selectedRel !== '' && !in_array($selectedRel, $relationships, true)): ?>
                    <option value="<?= e($selectedRel) ?>" selected><?= e($selectedRel) ?> (custom)</option>
                <?php endif; ?>
            </select>
            <?= $fieldError($errors, 'relationship') ?>
        </div>
        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" maxlength="255" value="<?= e((string) ($old['email'] ?? '')) ?>">
            <?= $fieldError($errors, 'email') ?>
        </div>
        <div class="form-field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" maxlength="40" value="<?= e((string) ($old['phone'] ?? '')) ?>">
            <?= $fieldError($errors, 'phone') ?>
        </div>
        <div class="form-field">
            <label for="years_known">Years known</label>
            <input id="years_known" type="number" name="years_known" min="0" max="99.9" step="0.1"
                   value="<?= e((string) ($old['years_known'] ?? '')) ?>">
            <?= $fieldError($errors, 'years_known') ?>
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
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" data-reference-country data-cities-url="<?= e($citiesUrl) ?>">
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
            <select id="city_id" name="city_id" data-reference-city>
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
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"><?= e((string) ($old['notes'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'notes') ?>
        </div>
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="permission_to_contact" value="1" <?= !empty($old['permission_to_contact']) ? 'checked' : '' ?>>
                Permission to contact (share email/phone with employers when visibility allows)
            </label>
            <label>
                <input type="checkbox" name="is_featured" value="1" <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
                Featured reference
            </label>
        </div>
        <div class="form-field">
            <label for="visibility">Visibility</label>
            <select id="visibility" name="visibility" required>
                <?php foreach ($visibilities as $vis): ?>
                    <option value="<?= e($vis) ?>" <?= (($old['visibility'] ?? 'private') === $vis) ? 'selected' : '' ?>>
                        <?= e($visibilityLabels[$vis] ?? $vis) ?>
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
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add reference' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url($basePath)) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage references.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">References on this resume</h2>
            <p class="panel__lead">
                <?= (int) $pagination['total'] ?> result<?= (int) $pagination['total'] === 1 ? '' : 's' ?>
                <?php if ((int) $pagination['last_page'] > 1): ?>
                    · page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['last_page'] ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <p class="muted"><?= $hasFilters ? 'No references match your filters.' : 'No references linked yet.' ?></p>
        </div>
    <?php else: ?>
        <ul class="record-list publication-cards">
            <?php foreach ($items as $index => $item): ?>
                <li class="record publication-card">
                    <div>
                        <div class="publication-card__head">
                            <strong><?= e($item->name) ?></strong>
                            <?php if ($item->isFeatured): ?>
                                <span class="badge badge--published">Featured</span>
                            <?php endif; ?>
                            <span class="badge"><?= e($visibilityLabels[$item->visibility] ?? $item->visibility) ?></span>
                            <?php if ($item->permissionToContact): ?>
                                <span class="badge">Contact OK</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item->designation || $item->company): ?>
                            <p class="muted">
                                <?= e(implode(' · ', array_filter([$item->designation, $item->company]))) ?>
                            </p>
                        <?php endif; ?>
                        <p class="muted">
                            <?php if ($item->relationship): ?>
                                <?= e($relationshipLabels[$item->relationship] ?? $item->relationship) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                            <?php if ($item->yearsKnown !== null): ?>
                                · <?= e((string) $item->yearsKnown) ?> yr<?= $item->yearsKnown == 1.0 ? '' : 's' ?> known
                            <?php endif; ?>
                            <?php if ($item->countryName || $item->cityName): ?>
                                · <?= e(implode(', ', array_filter([$item->cityName, $item->countryName]))) ?>
                            <?php endif; ?>
                            <?php if ($item->projectTitle): ?> · Project: <?= e($item->projectTitle) ?><?php endif; ?>
                        </p>
                        <?php if ($canEdit && ($item->email || $item->phone)): ?>
                            <p class="muted">
                                <?php if ($item->email): ?><?= e($item->email) ?><?php endif; ?>
                                <?php if ($item->email && $item->phone): ?> · <?php endif; ?>
                                <?php if ($item->phone): ?><?= e($item->phone) ?><?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="btn-row">
                            <?php if (count($items) > 1 && $canReorder): ?>
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
                                  onsubmit="return confirm('Move this reference to trash?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ((int) $pagination['last_page'] > 1): ?>
            <nav class="pagination" aria-label="References pagination">
                <?php if ((int) $pagination['page'] > 1): ?>
                    <a class="btn btn--secondary" href="<?= e($buildPageUrl((int) $pagination['page'] - 1)) ?>">Previous</a>
                <?php endif; ?>
                <span class="muted">Page <?= (int) $pagination['page'] ?> / <?= (int) $pagination['last_page'] ?></span>
                <?php if ((int) $pagination['page'] < (int) $pagination['last_page']): ?>
                    <a class="btn btn--secondary" href="<?= e($buildPageUrl((int) $pagination['page'] + 1)) ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($canEdit && $deleted !== []): ?>
<section class="panel">
    <h2 class="panel__title">Trash</h2>
    <p class="muted">Soft-deleted references. Restore to make them available again.</p>
    <ul class="record-list">
        <?php foreach ($deleted as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e($item->name) ?></strong>
                    <?php if ($item->company): ?>
                        <p class="muted"><?= e($item->company) ?></p>
                    <?php endif; ?>
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
