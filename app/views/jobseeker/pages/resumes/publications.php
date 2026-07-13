<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumePublicationDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumePublicationDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $types
 * @var list<string> $visibilities
 * @var list<string> $statuses
 * @var list<string> $sorts
 * @var list<array{id: int, title: string}> $projects
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $cities
 * @var array<string, string> $filters
 * @var array{total: int, page: int, per_page: int, last_page: int} $pagination
 * @var string $searchUrl
 * @var string $citiesUrl
 * @var \JobVisa\App\Domain\Resume\DTO\ResumePublicationDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'publications';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$typeLabels = [
    'book' => 'Book',
    'book_chapter' => 'Book Chapter',
    'research_paper' => 'Research Paper',
    'journal_article' => 'Journal Article',
    'conference_paper' => 'Conference Paper',
    'thesis' => 'Thesis',
    'dissertation' => 'Dissertation',
    'white_paper' => 'White Paper',
    'technical_report' => 'Technical Report',
    'patent' => 'Patent',
    'magazine_article' => 'Magazine Article',
    'newspaper_article' => 'Newspaper Article',
    'blog_post' => 'Blog Post',
    'case_study' => 'Case Study',
    'working_paper' => 'Working Paper',
    'other' => 'Other',
];
$visibilityLabels = [
    'public' => 'Public',
    'employers' => 'Employers Only',
    'private' => 'Private',
];
$sortLabels = [
    'sort_order' => 'Sort order',
    'newest' => 'Newest',
    'oldest' => 'Oldest',
    'title' => 'Title',
    'year' => 'Publication year',
    'featured' => 'Featured',
];
$countries = $countries ?? [];
$cities = $cities ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 10, 'last_page' => 1];
$citiesUrl = $citiesUrl ?? '';
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/publications/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/publications');
$basePath = '/jobseeker/resumes/' . (int) $resume['id'] . '/publications';
$queryParams = array_filter($filters, static fn ($v) => $v !== '' && $v !== null);
$activeFilters = $queryParams;
unset($activeFilters['sort']);
if (($filters['sort'] ?? 'sort_order') !== 'sort_order') {
    $activeFilters['sort'] = $filters['sort'];
}
$hasFilters = $activeFilters !== [];
$canReorder = ($filters['sort'] ?? 'sort_order') === 'sort_order' && !$hasFilters;$buildPageUrl = static function (int $page) use ($basePath, $queryParams): string {
    $params = array_merge($queryParams, ['page' => (string) $page]);

    return app_url($basePath . '?' . http_build_query($params));
};
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Publications &amp; research</h2>
            <p class="panel__lead">Add academic, technical, and media publications per resume. Private and employers-only items stay off public profiles.</p>
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

<section class="panel" id="publications-panel" data-search-url="<?= e($searchUrl) ?>">
    <form method="get" action="<?= e(app_url($basePath)) ?>" class="publication-filters">
        <div class="publication-filters__row">
            <label for="publication_q" class="sr-only">Search publications</label>
            <input id="publication_q" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>"
                   placeholder="Search title, publisher, author, DOI, ISBN…" autocomplete="off">
            <button type="submit" class="btn btn--secondary">Search</button>
            <?php if ($hasFilters): ?>
                <a class="btn btn--ghost" href="<?= e(app_url($basePath)) ?>" style="color:var(--brand-deep);border-color:var(--line)">Clear</a>
            <?php endif; ?>
        </div>
        <div class="publication-filters__grid">
            <div class="form-field">
                <label for="filter_publication_type">Type</label>
                <select id="filter_publication_type" name="publication_type">
                    <option value="">All types</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($filters['publication_type'] ?? '') === $type) ? 'selected' : '' ?>>
                            <?= e($typeLabels[$type] ?? $type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="filter_publication_year">Year</label>
                <input id="filter_publication_year" type="number" name="publication_year" min="1900" max="2100"
                       value="<?= e((string) ($filters['publication_year'] ?? '')) ?>" placeholder="e.g. 2024">
            </div>
            <div class="form-field">
                <label for="filter_peer">Peer reviewed</label>
                <select id="filter_peer" name="is_peer_reviewed">
                    <option value="">Any</option>
                    <option value="1" <?= (($filters['is_peer_reviewed'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= (($filters['is_peer_reviewed'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
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
    <ul id="publication-live-results" class="skill-suggestions" hidden role="listbox"></ul>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit publication' : 'Add publication' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="publication-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="title">Title</label>
            <input id="title" name="title" maxlength="300" required value="<?= e((string) ($old['title'] ?? '')) ?>">
            <?= $fieldError($errors, 'title') ?>
        </div>
        <div class="form-field">
            <label for="publication_type">Publication type</label>
            <select id="publication_type" name="publication_type" required>
                <option value="">Select</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= e($type) ?>" <?= (($old['publication_type'] ?? '') === $type) ? 'selected' : '' ?>>
                        <?= e($typeLabels[$type] ?? $type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'publication_type') ?>
        </div>
        <div class="form-field">
            <label for="publisher">Publisher / journal / institution</label>
            <input id="publisher" name="publisher" maxlength="255" value="<?= e((string) ($old['publisher'] ?? '')) ?>">
            <?= $fieldError($errors, 'publisher') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="authors">Authors</label>
            <textarea id="authors" name="authors" rows="2" maxlength="5000"><?= e((string) ($old['authors'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'authors') ?>
        </div>
        <div class="form-field">
            <label for="user_contribution">Your contribution / role</label>
            <input id="user_contribution" name="user_contribution" maxlength="200"
                   value="<?= e((string) ($old['user_contribution'] ?? '')) ?>">
            <?= $fieldError($errors, 'user_contribution') ?>
        </div>
        <div class="form-field">
            <label for="publication_date">Publication date</label>
            <input id="publication_date" type="date" name="publication_date"
                   value="<?= e((string) ($old['publication_date'] ?? '')) ?>">
            <?= $fieldError($errors, 'publication_date') ?>
        </div>
        <div class="form-field">
            <label for="publication_year">Publication year</label>
            <input id="publication_year" type="number" name="publication_year" min="1900" max="2100"
                   value="<?= e((string) ($old['publication_year'] ?? '')) ?>">
            <?= $fieldError($errors, 'publication_year') ?>
        </div>
        <div class="form-field">
            <label for="volume">Volume</label>
            <input id="volume" name="volume" maxlength="64" value="<?= e((string) ($old['volume'] ?? '')) ?>">
            <?= $fieldError($errors, 'volume') ?>
        </div>
        <div class="form-field">
            <label for="issue">Issue</label>
            <input id="issue" name="issue" maxlength="64" value="<?= e((string) ($old['issue'] ?? '')) ?>">
            <?= $fieldError($errors, 'issue') ?>
        </div>
        <div class="form-field">
            <label for="page_range">Page range</label>
            <input id="page_range" name="page_range" maxlength="64" placeholder="e.g. 12–28"
                   value="<?= e((string) ($old['page_range'] ?? '')) ?>">
            <?= $fieldError($errors, 'page_range') ?>
        </div>
        <div class="form-field">
            <label for="doi">DOI</label>
            <input id="doi" name="doi" maxlength="200" placeholder="10.xxxx/…"
                   value="<?= e((string) ($old['doi'] ?? '')) ?>">
            <?= $fieldError($errors, 'doi') ?>
        </div>
        <div class="form-field">
            <label for="isbn">ISBN</label>
            <input id="isbn" name="isbn" maxlength="32" value="<?= e((string) ($old['isbn'] ?? '')) ?>">
            <?= $fieldError($errors, 'isbn') ?>
        </div>
        <div class="form-field">
            <label for="issn">ISSN</label>
            <input id="issn" name="issn" maxlength="32" value="<?= e((string) ($old['issn'] ?? '')) ?>">
            <?= $fieldError($errors, 'issn') ?>
        </div>
        <div class="form-field">
            <label for="patent_number">Patent number</label>
            <input id="patent_number" name="patent_number" maxlength="120"
                   value="<?= e((string) ($old['patent_number'] ?? '')) ?>">
            <?= $fieldError($errors, 'patent_number') ?>
        </div>
        <div class="form-field">
            <label for="conference_name">Conference name</label>
            <input id="conference_name" name="conference_name" maxlength="255"
                   value="<?= e((string) ($old['conference_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'conference_name') ?>
        </div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" data-publication-country data-cities-url="<?= e($citiesUrl) ?>">
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
            <select id="city_id" name="city_id" data-publication-city>
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
            <label for="abstract_summary">Abstract / summary</label>
            <textarea id="abstract_summary" name="abstract_summary" rows="4"><?= e((string) ($old['abstract_summary'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'abstract_summary') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="keywords">Keywords</label>
            <input id="keywords" name="keywords" maxlength="1000" placeholder="Comma-separated"
                   value="<?= e((string) ($old['keywords'] ?? '')) ?>">
            <?= $fieldError($errors, 'keywords') ?>
        </div>
        <div class="form-field">
            <label for="publication_url">Publication URL</label>
            <input id="publication_url" type="url" name="publication_url" maxlength="500"
                   value="<?= e((string) ($old['publication_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'publication_url') ?>
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
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_peer_reviewed" value="1" <?= !empty($old['is_peer_reviewed']) ? 'checked' : '' ?>>
                Peer reviewed
            </label>
            <label>
                <input type="checkbox" name="is_featured" value="1" <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
                Featured publication
            </label>
        </div>
        <div class="form-field">
            <label for="visibility">Visibility</label>
            <select id="visibility" name="visibility" required>
                <?php foreach ($visibilities as $vis): ?>
                    <option value="<?= e($vis) ?>" <?= (($old['visibility'] ?? 'public') === $vis) ? 'selected' : '' ?>>
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
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add publication' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url($basePath)) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Publication document</h3>
        <p class="muted">PDF, DOC, DOCX, JPG, or PNG · max 10MB. Stored securely; internal path never exposed publicly.</p>
        <?php if ($editingItem->hasDocument()): ?>
            <p class="muted">
                Current file:
                <strong><?= e((string) ($editingItem->documentOriginalName ?: 'Document')) ?></strong>
                <?php if ($editingItem->documentMime): ?>
                    · <?= e($editingItem->documentMime) ?>
                <?php endif; ?>
                <?php if ($editingItem->documentSize): ?>
                    · <?= e((string) round($editingItem->documentSize / 1024, 1)) ?> KB
                <?php endif; ?>
                · <a href="<?= e(app_url($basePath . '/' . (int) $editingId . '/download')) ?>">Download</a>
            </p>
            <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/remove-document')) ?>"
                  onsubmit="return confirm('Remove publication document?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove document</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/document')) ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="document"><?= $editingItem->hasDocument() ? 'Replace document' : 'Upload document' ?></label>
                <input id="document" type="file" name="document"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png"
                       required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary"><?= $editingItem->hasDocument() ? 'Replace file' : 'Upload document' ?></button>
            </div>
        </form>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage publications.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Publications on this resume</h2>
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
            <p class="muted"><?= $hasFilters ? 'No publications match your filters.' : 'No publications linked yet.' ?></p>
        </div>
    <?php else: ?>
        <ul class="record-list publication-cards">
            <?php foreach ($items as $index => $item): ?>
                <li class="record publication-card">
                    <div>
                        <div class="publication-card__head">
                            <strong><?= e($item->title) ?></strong>
                            <?php if ($item->isFeatured): ?>
                                <span class="badge badge--published">Featured</span>
                            <?php endif; ?>
                            <span class="badge"><?= e($visibilityLabels[$item->visibility] ?? $item->visibility) ?></span>
                            <span class="badge"><?= e($typeLabels[$item->publicationType] ?? $item->publicationType) ?></span>
                            <?php if ($item->isPeerReviewed): ?>
                                <span class="badge">Peer reviewed</span>
                            <?php endif; ?>
                            <?php if ($item->hasDocument() && $canEdit): ?>
                                <span class="badge">Document</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item->publisher): ?>
                            <p class="muted"><?= e($item->publisher) ?></p>
                        <?php endif; ?>
                        <p class="muted">
                            <?= e((string) ($item->publicationYear ?? $item->publicationDate ?? '—')) ?>
                            <?php if ($item->authors): ?> · <?= e(mb_strimwidth($item->authors, 0, 80, '…')) ?><?php endif; ?>
                            <?php if ($item->countryName || $item->cityName): ?>
                                · <?= e(implode(', ', array_filter([$item->cityName, $item->countryName]))) ?>
                            <?php endif; ?>
                            <?php if ($item->projectTitle): ?> · Project: <?= e($item->projectTitle) ?><?php endif; ?>
                        </p>
                        <?php if ($item->publicationUrl || ($item->hasDocument() && $canEdit)): ?>
                            <p class="muted">
                                <?php if ($item->publicationUrl): ?>
                                    <a href="<?= e($item->publicationUrl) ?>" rel="noopener noreferrer" target="_blank">View online</a>
                                <?php endif; ?>
                                <?php if ($item->hasDocument() && $canEdit): ?>
                                    <?php if ($item->publicationUrl): ?> · <?php endif; ?>
                                    <a href="<?= e(app_url($basePath . '/' . (int) $item->id . '/download')) ?>">Download</a>
                                <?php endif; ?>
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
                                  onsubmit="return confirm('Move this publication to trash?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ((int) $pagination['last_page'] > 1): ?>
            <nav class="pagination" aria-label="Publications pagination">
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
    <p class="muted">Soft-deleted publications. Documents are kept until explicitly removed.</p>
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
