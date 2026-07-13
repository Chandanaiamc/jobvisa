<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\CoverLetter\DTO\CoverLetterVersionDTO|null $preview
 * @var list<\JobVisa\App\Domain\CoverLetter\DTO\CoverLetterVersionDTO> $versions
 * @var list<array<string, mixed>> $matchedJobs
 * @var list<string> $styles
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'cover-letters';
$preview = $preview ?? null;
$versions = $versions ?? [];
$matchedJobs = $matchedJobs ?? [];
$styles = $styles ?? [];
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
$highlights = $preview?->highlights ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Cover Letter Generator</h1>
            <p class="panel__lead">Personalized, ATS-friendly letters from resume intelligence, coach signals and job matches.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/ai-builder')) ?>">AI Builder</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <h2 class="panel__title">Generate preview</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/generate')) ?>" class="recruiter-search">
        <?= csrf_field() ?>
        <label class="field">
            <span class="muted">Target job (optional)</span>
            <select name="job_id">
                <option value="">Best matched / general</option>
                <?php foreach ($matchedJobs as $job): ?>
                    <option value="<?= (int) ($job['job_id'] ?? 0) ?>" <?= ($preview?->jobId === (int) ($job['job_id'] ?? 0)) ? 'selected' : '' ?>>
                        <?= e((string) ($job['job_title'] ?? '')) ?> (match <?= (int) ($job['overall_score'] ?? 0) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="muted">Writing style</span>
            <select name="style" required>
                <?php foreach ($styles as $style): ?>
                    <option value="<?= e($style) ?>" <?= ($preview?->style ?? 'professional') === $style ? 'selected' : '' ?>><?= e(ucfirst($style)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="muted">Tone (optional)</span>
            <input type="text" name="tone" maxlength="64" value="<?= e((string) ($preview?->tone ?? '')) ?>" placeholder="e.g. confident, warm, concise">
        </label>
        <label class="field">
            <span class="muted">Version label</span>
            <input type="text" name="version_label" maxlength="120" placeholder="Dubai RN — professional">
        </label>
        <button type="submit" class="btn btn--primary">Generate preview</button>
    </form>
</section>
<?php endif; ?>

<?php if ($preview === null): ?>
<section class="panel"><p class="muted">No cover letter versions yet. Generate a preview to begin.</p></section>
<?php else: ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Preview — <?= e($preview->versionLabel) ?></h2>
            <p class="muted">
                <?= e(ucfirst($preview->style)) ?>
                · <?= e($preview->status) ?>
                · ATS <?= $preview->atsScore ?>/100
                <?php if ($preview->jobTitle !== ''): ?> · <?= e($preview->jobTitle) ?><?php endif; ?>
            </p>
        </div>
        <?php if ($canEdit): ?>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <?php if ($preview->status === 'preview'): ?>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/versions/' . $preview->id . '/save')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Save version</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/regenerate')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="job_id" value="<?= (int) ($preview->jobId ?? 0) ?>">
                    <input type="hidden" name="style" value="<?= e($preview->style) ?>">
                    <input type="hidden" name="tone" value="<?= e((string) ($preview->tone ?? '')) ?>">
                    <button type="submit" class="btn btn--secondary">Regenerate</button>
                </form>
                <button type="button" class="btn btn--secondary" id="copy-cover-letter">Copy</button>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/versions/' . $preview->id . '/export/pdf')) ?>">PDF</a>
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/versions/' . $preview->id . '/export/docx')) ?>">DOCX</a>
            </div>
        <?php endif; ?>
    </div>

    <h3 class="panel__title">Matching skills & achievements</h3>
    <p class="muted">Skills: <?= e(implode(', ', $highlights['matched_skills'] ?? []) ?: '—') ?></p>
    <p class="muted">Achievements: <?= e(implode(' · ', $highlights['achievements'] ?? []) ?: '—') ?></p>

    <h3 class="panel__title">Letter body</h3>
    <pre id="cover-letter-body" style="white-space:pre-wrap;font-family:Outfit,sans-serif;font-size:1rem;line-height:1.55"><?= e($preview->bodyText) ?></pre>
</section>
<script>
(function () {
  var btn = document.getElementById('copy-cover-letter');
  var body = document.getElementById('cover-letter-body');
  if (!btn || !body) return;
  btn.addEventListener('click', function () {
    var text = body.textContent || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () { btn.textContent = 'Copied'; });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      btn.textContent = 'Copied';
    }
  });
})();
</script>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Versions</h2>
    <?php if ($versions === []): ?>
        <p class="muted">No versions stored.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($versions as $v): ?>
                <li class="record">
                    <div>
                        <strong><?= e($v->versionLabel) ?></strong>
                        <p class="muted"><?= e($v->style) ?> · <?= e($v->status) ?> · ATS <?= $v->atsScore ?> · <?= e($v->createdAt) ?></p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters?version=' . $v->id)) ?>">Preview</a>
                        <?php if ($canEdit): ?>
                            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/versions/' . $v->id . '/delete')) ?>">
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
        <h2 class="panel__title">Version history</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/history/clear')) ?>" onsubmit="return confirm('Clear all history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No history yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($history as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($item['headline'] ?? '')) ?></strong>
                        <p class="muted"><?= e((string) ($item['action'] ?? '')) ?> · <?= e((string) ($item['style'] ?? '')) ?> · <?= e((string) ($item['created_at'] ?? '')) ?></p>
                    </div>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/history/' . (int) ($item['id'] ?? 0) . '/delete')) ?>">
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
    <h2 class="panel__title">Recently removed</h2>
    <ul class="record-list">
        <?php foreach ($deletedHistory as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($item['headline'] ?? '')) ?></strong>
                    <p class="muted">Removed <?= e((string) ($item['deleted_at'] ?? '')) ?></p>
                </div>
                <div style="display:flex;gap:.5rem">
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/history/' . (int) ($item['id'] ?? 0) . '/restore')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Restore</button>
                    </form>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/cover-letters/history/' . (int) ($item['id'] ?? 0) . '/purge')) ?>" onsubmit="return confirm('Permanently delete this history entry?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary">Delete forever</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php endif; ?>
