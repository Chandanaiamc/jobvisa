<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\ResumeBuilder\DTO\AiResumeVersionDTO|null $preview
 * @var list<\JobVisa\App\Domain\ResumeBuilder\DTO\AiResumeVersionDTO> $versions
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'ai-builder';
$preview = $preview ?? null;
$versions = $versions ?? [];
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
$content = $preview?->content ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Resume Builder</h1>
            <p class="panel__lead">Generate ATS-friendly summaries, bullets, skills and keyword optimizations.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/career-coach')) ?>">Career Coach</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title">Generate preview</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/generate')) ?>" class="recruiter-search">
        <?= csrf_field() ?>
        <label class="field">
            <span class="muted">Target role (optional)</span>
            <input type="text" name="target_role" maxlength="191" value="<?= e((string) ($preview?->targetRole ?? '')) ?>" placeholder="e.g. Registered Nurse">
        </label>
        <label class="field">
            <span class="muted">Version label (optional)</span>
            <input type="text" name="version_label" maxlength="120" placeholder="Gulf nursing ATS draft">
        </label>
        <button type="submit" class="btn btn--primary">Generate preview</button>
    </form>
    <?php if ($preview !== null): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/regenerate')) ?>" style="margin-top:.75rem">
            <?= csrf_field() ?>
            <input type="hidden" name="target_role" value="<?= e((string) ($preview->targetRole ?? '')) ?>">
            <button type="submit" class="btn btn--secondary">Regenerate</button>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($preview === null): ?>
<section class="panel">
    <p class="muted">No AI resume versions yet. Generate a preview to begin.</p>
</section>
<?php else: ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Preview — <?= e($preview->versionLabel) ?></h2>
            <p class="muted">
                Status: <?= e($preview->status) ?>
                · ATS optimization: <strong><?= $preview->atsScore ?></strong>/100
                <?php if ($preview->isActive): ?> · Active<?php endif; ?>
            </p>
        </div>
        <?php if ($canEdit): ?>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <?php if ($preview->status === 'preview'): ?>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/versions/' . $preview->id . '/save')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Save version</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/versions/' . $preview->id . '/activate')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Set active</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <h3 class="panel__title">Professional summary</h3>
    <p><?= e($preview->professionalSummary) ?></p>

    <h3 class="panel__title">ATS optimization score</h3>
    <p><strong><?= $preview->atsScore ?></strong>/100</p>

    <h3 class="panel__title">Missing keywords (matched jobs)</h3>
    <?php if ($preview->missingKeywords === []): ?>
        <p class="muted">No strong missing keywords detected.</p>
    <?php else: ?>
        <p><?= e(implode(' · ', $preview->missingKeywords)) ?></p>
    <?php endif; ?>

    <h3 class="panel__title">Keyword optimization suggestions</h3>
    <ul class="record-list">
        <?php foreach ($preview->keywordSuggestions as $s): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($s['keyword'] ?? '')) ?></strong>
                    <span class="badge"><?= e((string) ($s['priority'] ?? '')) ?></span>
                    <p class="muted"><?= e((string) ($s['action'] ?? '')) ?> — <?= e((string) ($s['why'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Improved experience bullets</h2>
    <?php foreach (($content['experience_bullets'] ?? []) as $exp): ?>
        <div class="record" style="margin-bottom:1rem">
            <strong><?= e((string) ($exp['job_title'] ?? '')) ?> — <?= e((string) ($exp['company_name'] ?? '')) ?></strong>
            <?php if (!empty($exp['original'])): ?>
                <p class="muted">Original: <?= e((string) $exp['original']) ?></p>
            <?php endif; ?>
            <ul>
                <?php foreach (($exp['improved_bullets'] ?? []) as $b): ?>
                    <li><?= e((string) $b) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Suggested skills</h2>
    <h3 class="muted">Technical</h3>
    <ul>
        <?php foreach (($content['suggested_technical_skills'] ?? []) as $s): ?>
            <li><strong><?= e((string) ($s['name'] ?? '')) ?></strong> — <?= e((string) ($s['reason'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3 class="muted">Soft</h3>
    <ul>
        <?php foreach (($content['suggested_soft_skills'] ?? []) as $s): ?>
            <li><strong><?= e((string) ($s['name'] ?? '')) ?></strong> — <?= e((string) ($s['reason'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Education & certification improvements</h2>
    <ul class="record-list">
        <?php foreach (($content['education_improvements'] ?? []) as $e): ?>
            <li class="record"><div><strong><?= e((string) ($e['label'] ?? '')) ?></strong><p><?= e((string) ($e['improved_description'] ?? '')) ?></p></div></li>
        <?php endforeach; ?>
        <?php foreach (($content['certification_improvements'] ?? []) as $c): ?>
            <li class="record"><div><strong><?= e((string) ($c['name'] ?? '')) ?></strong><p><?= e((string) ($c['improved_description'] ?? '')) ?></p></div></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">ATS-friendly content</h2>
    <pre style="white-space:pre-wrap;font-family:Outfit,sans-serif;font-size:.95rem"><?= e((string) ($content['ats_friendly_content'] ?? '')) ?></pre>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">AI resume versions</h2>
    <?php if ($versions === []): ?>
        <p class="muted">No versions stored.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($versions as $v): ?>
                <li class="record">
                    <div>
                        <strong><?= e($v->versionLabel) ?></strong>
                        <p class="muted"><?= e($v->status) ?> · ATS <?= $v->atsScore ?><?= $v->isActive ? ' · active' : '' ?> · <?= e($v->createdAt) ?></p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder?version=' . $v->id)) ?>">Preview</a>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/versions/' . $v->id . '/delete')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--secondary">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">Generation history</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/history/clear')) ?>" onsubmit="return confirm('Clear all generation history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No generation history yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($history as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($item['headline'] ?? $item['action'] ?? '')) ?></strong>
                        <p class="muted"><?= e((string) ($item['action'] ?? '')) ?> · ATS <?= (int) ($item['ats_score'] ?? 0) ?> · <?= e((string) ($item['created_at'] ?? '')) ?></p>
                    </div>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/history/' . (int) ($item['id'] ?? 0) . '/delete')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($deletedHistory !== []): ?>
<section class="panel">
    <h2 class="panel__title">Recently removed history</h2>
    <ul class="record-list">
        <?php foreach ($deletedHistory as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($item['headline'] ?? '')) ?></strong>
                    <p class="muted">Removed <?= e((string) ($item['deleted_at'] ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder/history/' . (int) ($item['id'] ?? 0) . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php endif; ?>
