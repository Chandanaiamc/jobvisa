<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $jobs
 * @var string $disclaimer
 */
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">My jobs</h1>
            <p class="panel__lead"><?= e($disclaimer) ?></p>
        </div>
    </div>
</section>

<section class="panel">
    <?php if ($jobs === []): ?>
        <p class="muted">No jobs found for your employer profile.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Applications</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?= e((string) ($job['title'] ?? '')) ?></td>
                            <td><span class="badge"><?= e((string) ($job['status'] ?? '')) ?></span></td>
                            <td><?= e((string) ($job['country_name'] ?? '—')) ?></td>
                            <td><?= (int) ($job['applications_count'] ?? 0) ?></td>
                            <td>
                                <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . (int) $job['id'] . '/applicants/ranking')) ?>">Top candidates</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
