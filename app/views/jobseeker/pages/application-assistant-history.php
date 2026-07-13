<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $job
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var list<array<string, mixed>> $versions
 * @var string $version
 */

$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">Application Assistant history</h1>
            <p class="panel__lead"><?= e((string) ($job['title'] ?? '')) ?></p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/jobs/' . (int) ($job['id'] ?? 0) . '/application-assistant')) ?>">Back</a>
    </div>
    <p class="muted">Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">History</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/history/clear')) ?>" onsubmit="return confirm('Clear all history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear</button>
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
                        <p class="muted"><?= e((string) ($item['action'] ?? '')) ?> · <?= (int) ($item['readiness_score'] ?? 0) ?>/100 · <?= e((string) ($item['created_at'] ?? '')) ?></p>
                    </div>
                    <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/history/' . (int) ($item['id'] ?? 0) . '/delete')) ?>">
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
                    <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/history/' . (int) ($item['id'] ?? 0) . '/restore')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Restore</button>
                    </form>
                    <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/history/' . (int) ($item['id'] ?? 0) . '/purge')) ?>" onsubmit="return confirm('Permanently delete?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary">Delete forever</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
