<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumePortfolioDTO> $items
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumePortfolioDTO> $deleted
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var list<string> $categories
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
 * @var int $maxGallery
 * @var \JobVisa\App\Domain\Resume\DTO\ResumePortfolioDTO|null $editingItem
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var int|null $editingId
 */

$resumeSection = 'portfolio';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$categoryLabels = [
    'web' => 'Web',
    'mobile' => 'Mobile',
    'ui_ux' => 'UI / UX',
    'graphic_design' => 'Graphic Design',
    'branding' => 'Branding',
    'photography' => 'Photography',
    'video' => 'Video',
    'illustration' => 'Illustration',
    'writing' => 'Writing',
    'research' => 'Research',
    'open_source' => 'Open Source',
    'product' => 'Product',
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
    'featured' => 'Featured',
];
$countries = $countries ?? [];
$cities = $cities ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['total' => 0, 'page' => 1, 'per_page' => 10, 'last_page' => 1];
$citiesUrl = $citiesUrl ?? '';
$maxGallery = (int) ($maxGallery ?? 12);
$formAction = $editingId
    ? app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio/' . (int) $editingId)
    : app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio');
$basePath = '/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio';
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
            <h2 class="panel__title">Professional portfolio</h2>
            <p class="panel__lead">Showcase work samples, case studies, and creative projects per resume. Private and employers-only items stay off public profiles.</p>
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

<section class="panel" id="portfolio-panel" data-search-url="<?= e($searchUrl) ?>">
    <form method="get" action="<?= e(app_url($basePath)) ?>" class="publication-filters">
        <div class="publication-filters__row">
            <label for="portfolio_q" class="sr-only">Search portfolio</label>
            <input id="portfolio_q" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>"
                   placeholder="Search title, description, category, URLs…" autocomplete="off">
            <button type="submit" class="btn btn--secondary">Search</button>
            <?php if ($hasFilters): ?>
                <a class="btn btn--ghost" href="<?= e(app_url($basePath)) ?>" style="color:var(--brand-deep);border-color:var(--line)">Clear</a>
            <?php endif; ?>
        </div>
        <div class="publication-filters__grid">
            <div class="form-field">
                <label for="filter_category">Category</label>
                <select id="filter_category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= (($filters['category'] ?? '') === $category) ? 'selected' : '' ?>>
                            <?= e($categoryLabels[$category] ?? $category) ?>
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
    <ul id="portfolio-live-results" class="skill-suggestions" hidden role="listbox"></ul>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title"><?= $editingId ? 'Edit portfolio item' : 'Add portfolio item' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e($formAction) ?>" class="form-grid" id="portfolio-form">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="title">Title</label>
            <input id="title" name="title" maxlength="200" required value="<?= e((string) ($old['title'] ?? '')) ?>">
            <?= $fieldError($errors, 'title') ?>
        </div>
        <div class="form-field">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>" <?= (($old['category'] ?? '') === $category) ? 'selected' : '' ?>>
                        <?= e($categoryLabels[$category] ?? $category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'category') ?>
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
        <div class="form-field form-field--full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?= e((string) ($old['description'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'description') ?>
        </div>
        <div class="form-field">
            <label for="portfolio_url">Portfolio URL</label>
            <input id="portfolio_url" type="url" name="portfolio_url" maxlength="500"
                   value="<?= e((string) ($old['portfolio_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'portfolio_url') ?>
        </div>
        <div class="form-field">
            <label for="github_url">GitHub URL</label>
            <input id="github_url" type="url" name="github_url" maxlength="500"
                   value="<?= e((string) ($old['github_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'github_url') ?>
        </div>
        <div class="form-field">
            <label for="behance_url">Behance URL</label>
            <input id="behance_url" type="url" name="behance_url" maxlength="500"
                   value="<?= e((string) ($old['behance_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'behance_url') ?>
        </div>
        <div class="form-field">
            <label for="dribbble_url">Dribbble URL</label>
            <input id="dribbble_url" type="url" name="dribbble_url" maxlength="500"
                   value="<?= e((string) ($old['dribbble_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'dribbble_url') ?>
        </div>
        <div class="form-field">
            <label for="figma_url">Figma URL</label>
            <input id="figma_url" type="url" name="figma_url" maxlength="500"
                   value="<?= e((string) ($old['figma_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'figma_url') ?>
        </div>
        <div class="form-field">
            <label for="youtube_url">YouTube URL</label>
            <input id="youtube_url" type="url" name="youtube_url" maxlength="500"
                   value="<?= e((string) ($old['youtube_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'youtube_url') ?>
        </div>
        <div class="form-field">
            <label for="google_drive_url">Google Drive URL</label>
            <input id="google_drive_url" type="url" name="google_drive_url" maxlength="500"
                   value="<?= e((string) ($old['google_drive_url'] ?? '')) ?>">
            <?= $fieldError($errors, 'google_drive_url') ?>
        </div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id" data-portfolio-country data-cities-url="<?= e($citiesUrl) ?>">
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
            <select id="city_id" name="city_id" data-portfolio-city>
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
        <div class="form-field form-field--full choice-row">
            <label>
                <input type="checkbox" name="is_featured" value="1" <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
                Featured portfolio item
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
            <button type="submit" class="btn btn--primary"><?= $editingId ? 'Save changes' : 'Add portfolio item' ?></button>
            <?php if ($editingId): ?>
                <a class="btn btn--secondary" href="<?= e(app_url($basePath)) ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($editingId && $editingItem): ?>
        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Featured image</h3>
        <p class="muted">JPG, PNG, or WebP · max 5MB.</p>
        <?php if ($editingItem->hasFeaturedImage()): ?>
            <p class="muted">
                Current file:
                <strong><?= e((string) ($editingItem->featuredImageOriginalName ?: 'Image')) ?></strong>
                <?php if ($editingItem->featuredImageMime): ?>
                    · <?= e($editingItem->featuredImageMime) ?>
                <?php endif; ?>
                <?php if ($editingItem->featuredImageSize): ?>
                    · <?= e((string) round($editingItem->featuredImageSize / 1024, 1)) ?> KB
                <?php endif; ?>
                · <a href="<?= e(app_url($basePath . '/' . (int) $editingId . '/download-featured')) ?>">Download</a>
            </p>
            <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/remove-featured')) ?>"
                  onsubmit="return confirm('Remove featured image?');" style="margin-bottom:0.8rem">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--danger">Remove featured image</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/featured')) ?>"
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-field form-field--full">
                <label for="featured_image"><?= $editingItem->hasFeaturedImage() ? 'Replace featured image' : 'Upload featured image' ?></label>
                <input id="featured_image" type="file" name="featured_image"
                       accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                       required>
            </div>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--secondary"><?= $editingItem->hasFeaturedImage() ? 'Replace image' : 'Upload image' ?></button>
            </div>
        </form>

        <hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
        <h3 class="panel__title" style="font-size:1.05rem">Gallery</h3>
        <p class="muted">Up to <?= (int) $maxGallery ?> images · JPG, PNG, or WebP · max 5MB each.</p>
        <?php
        $galleryImages = $editingItem->galleryImages;
        $galleryCount = count($galleryImages);
        ?>
        <?php if ($galleryImages !== []): ?>
            <ul class="record-list" style="margin-bottom:1rem">
                <?php foreach ($galleryImages as $galleryImage): ?>
                    <li class="record">
                        <div>
                            <strong><?= e((string) ($galleryImage['original_name'] ?? 'Image')) ?></strong>
                            <p class="muted">
                                <?php if (!empty($galleryImage['mime'])): ?>
                                    <?= e((string) $galleryImage['mime']) ?>
                                <?php endif; ?>
                                <?php if (!empty($galleryImage['file_size'])): ?>
                                    · <?= e((string) round(((int) $galleryImage['file_size']) / 1024, 1)) ?> KB
                                <?php endif; ?>
                            </p>
                        </div>
                        <form method="post"
                              action="<?= e(app_url($basePath . '/' . (int) $editingId . '/gallery/' . (int) $galleryImage['id'] . '/delete')) ?>"
                              onsubmit="return confirm('Remove this gallery image?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--danger">Remove</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No gallery images yet.</p>
        <?php endif; ?>

        <?php if ($galleryCount < $maxGallery): ?>
            <form method="post" action="<?= e(app_url($basePath . '/' . (int) $editingId . '/gallery')) ?>"
                  enctype="multipart/form-data" class="form-grid">
                <?= csrf_field() ?>
                <div class="form-field form-field--full">
                    <label for="gallery_image">Add gallery image (<?= (int) $galleryCount ?> / <?= (int) $maxGallery ?>)</label>
                    <input id="gallery_image" type="file" name="gallery_image"
                           accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                           required>
                </div>
                <div class="form-actions form-field--full">
                    <button type="submit" class="btn btn--secondary">Upload gallery image</button>
                </div>
            </form>
        <?php else: ?>
            <p class="muted">Gallery is full (<?= (int) $maxGallery ?> images).</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="panel">
    <p class="muted">Read-only view. Only the resume owner can manage portfolio items.</p>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Portfolio on this resume</h2>
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
            <p class="muted"><?= $hasFilters ? 'No portfolio items match your filters.' : 'No portfolio items linked yet.' ?></p>
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
                            <span class="badge"><?= e($categoryLabels[$item->category] ?? $item->category) ?></span>
                            <?php if ($item->hasFeaturedImage() && $canEdit): ?>
                                <span class="badge">Image</span>
                            <?php endif; ?>
                            <?php if ($item->galleryCount() > 0 && $canEdit): ?>
                                <span class="badge">Gallery <?= (int) $item->galleryCount() ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($item->description): ?>
                            <p class="muted"><?= e(mb_strimwidth($item->description, 0, 140, '…')) ?></p>
                        <?php endif; ?>
                        <p class="muted">
                            <?php if ($item->countryName || $item->cityName): ?>
                                <?= e(implode(', ', array_filter([$item->cityName, $item->countryName]))) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                            <?php if ($item->projectTitle): ?> · Project: <?= e($item->projectTitle) ?><?php endif; ?>
                        </p>
                        <?php
                        $links = array_filter([
                            'View' => $item->portfolioUrl,
                            'GitHub' => $item->githubUrl,
                            'Behance' => $item->behanceUrl,
                            'Dribbble' => $item->dribbbleUrl,
                            'Figma' => $item->figmaUrl,
                            'YouTube' => $item->youtubeUrl,
                        ]);
                        ?>
                        <?php if ($links !== [] || ($item->hasFeaturedImage() && $canEdit)): ?>
                            <p class="muted">
                                <?php
                                $linkParts = [];
                                foreach ($links as $label => $url) {
                                    $linkParts[] = '<a href="' . e((string) $url) . '" rel="noopener noreferrer" target="_blank">' . e($label) . '</a>';
                                }
                                if ($item->hasFeaturedImage() && $canEdit) {
                                    $linkParts[] = '<a href="' . e(app_url($basePath . '/' . (int) $item->id . '/download-featured')) . '">Download image</a>';
                                }
                                echo implode(' · ', $linkParts);
                                ?>
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
                                  onsubmit="return confirm('Move this portfolio item to trash?');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ((int) $pagination['last_page'] > 1): ?>
            <nav class="pagination" aria-label="Portfolio pagination">
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
    <p class="muted">Soft-deleted portfolio items. Images are kept until explicitly removed.</p>
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
