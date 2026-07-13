<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $resume */
$hasFile = !empty($resume['file_path']);
?>
<section class="panel">
    <h2 class="panel__title">Curriculum Vitae</h2>
    <p class="panel__lead">Upload a PDF CV (max 5MB). You can replace or delete it anytime.</p>

    <?php if ($hasFile): ?>
        <div class="cv-card">
            <p><strong>Current CV on file</strong></p>
            <p class="muted">Size: <?= isset($resume['file_size_bytes']) ? number_format((int) $resume['file_size_bytes'] / 1024, 1) . ' KB' : '—' ?></p>
            <div class="btn-row">
                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/cv/download')) ?>">Download CV</a>
                <form method="post" action="<?= e(app_url('/jobseeker/cv/delete')) ?>" onsubmit="return confirm('Delete your CV?');">
                    <?= csrf_field() ?>
                    <button class="btn btn--danger" type="submit">Delete CV</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <p class="muted">No CV uploaded yet.</p>
    <?php endif; ?>

    <form method="post" action="<?= e(app_url('/jobseeker/cv')) ?>" enctype="multipart/form-data" class="stack-form" style="margin-top:1.25rem">
        <?= csrf_field() ?>
        <label for="cv"><?= $hasFile ? 'Replace CV (PDF)' : 'Upload CV (PDF)' ?></label>
        <input id="cv" type="file" name="cv" accept="application/pdf" required>
        <button class="btn btn--primary" type="submit"><?= $hasFile ? 'Replace CV' : 'Upload CV' ?></button>
    </form>
</section>
