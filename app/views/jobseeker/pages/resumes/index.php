<?php

declare(strict_types=1);

/**
 * @var list<\JobVisa\App\Domain\Resume\DTO\ResumeData> $items
 */
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Your resumes</h2>
            <p class="panel__lead">Manage multiple resumes. One can be the default for applications and CV uploads.</p>
        </div>
        <a class="btn btn--primary" href="<?= e(app_url('/jobseeker/resumes/create')) ?>">New resume</a>
    </div>

    <?php if ($items === []): ?>
        <p class="muted">No resumes yet. Create your first draft to get started.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $item): ?>
                <li class="record resume-card">
                    <div>
                        <strong>
                            <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id)) ?>"><?= e($item->title) ?></a>
                        </strong>
                        <p class="muted">
                            <span class="badge badge--<?= e($item->status) ?>"><?= e(ucfirst($item->status)) ?></span>
                            · Visibility: <?= e($item->visibility) ?>
                            <?php if ($item->isDefault): ?> · <strong>Default</strong><?php endif; ?>
                        </p>
                        <div class="completeness" style="max-width:220px;margin-top:0.5rem">
                            <div class="completeness__meta">
                                <span>Completion</span>
                                <strong><?= (int) $item->completenessScore ?>%</strong>
                            </div>
                            <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $item->completenessScore ?>" aria-valuemin="0" aria-valuemax="100">
                                <span style="width: <?= (int) $item->completenessScore ?>%"></span>
                            </div>
                        </div>
                        <p class="muted" style="margin-top:0.5rem;font-size:0.85rem">
                            Updated <?= e((string) ($item->updatedAt ?? '—')) ?>
                        </p>
                    </div>
                    <div class="btn-row">
                        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id . '/edit')) ?>">Edit</a>
                        <?php if ($item->status !== 'published'): ?>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id . '/publish')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--secondary" type="submit">Publish</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id . '/draft')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--secondary" type="submit">Unpublish</button>
                            </form>
                        <?php endif; ?>
                        <?php if (!$item->isDefault): ?>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id . '/default')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn--secondary" type="submit">Set default</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $item->id . '/delete')) ?>" onsubmit="return confirm('Soft-delete this resume?');">
                            <?= csrf_field() ?>
                            <button class="btn btn--danger" type="submit">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
