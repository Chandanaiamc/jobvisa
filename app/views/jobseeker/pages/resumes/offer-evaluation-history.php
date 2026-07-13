<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var string $version
 */

$resumeSection = 'offer-evaluation';
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">Offer Evaluation History</h1>
            <p class="panel__lead"><?= e((string) ($resume['title'] ?? 'Resume')) ?></p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation')) ?>">Back</a>
    </div>
    <p class="muted">Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Active history</h2>
    <?php if ($history === []): ?>
        <p class="muted">No history yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Headline</th><th>Action</th><th>Score</th><th>When</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['headline'] ?? '')) ?></td>
                            <td><?= e((string) ($row['action'] ?? '')) ?></td>
                            <td><?= (int) ($row['overall_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                            <td>
                                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/history/' . (int) ($row['id'] ?? 0) . '/delete')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--secondary">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/history/clear')) ?>" style="margin-top:1rem">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--secondary">Clear history</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Deleted history</h2>
    <?php if ($deletedHistory === []): ?>
        <p class="muted">No deleted entries.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Headline</th><th>Deleted</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($deletedHistory as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['headline'] ?? '')) ?></td>
                            <td><?= e((string) ($row['deleted_at'] ?? '')) ?></td>
                            <td>
                                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/history/' . (int) ($row['id'] ?? 0) . '/restore')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--secondary">Restore</button>
                                </form>
                                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/history/' . (int) ($row['id'] ?? 0) . '/purge')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--secondary">Purge</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
