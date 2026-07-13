<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var array{score?: int, sections?: array} $completion
 * @var \JobVisa\App\Domain\SalaryIntelligence\DTO\SalaryPredictionDTO|null $prediction
 * @var list<array<string, mixed>> $versions
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'salary-intelligence';
$prediction = $prediction ?? null;
$versions = $versions ?? [];
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
$canEdit = $canEdit ?? false;
$impacts = $prediction?->analysis['impacts'] ?? [];
$tips = $prediction?->analysis['negotiation_tips'] ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Salary Intelligence</h1>
            <p class="panel__lead">Predict expected salary ranges from your resume signals before you negotiate.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/salary-intelligence/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Estimate</h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/salary-intelligence/' . ($prediction ? 'recalculate' : 'calculate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--primary"><?= $prediction ? 'Recalculate salary' : 'Calculate salary' ?></button>
        </form>
    <?php endif; ?>

    <?php if ($prediction === null): ?>
        <p class="muted" style="margin-top:1rem">No prediction yet. Run a calculation to see min/max, market average, and negotiation tips.</p>
    <?php else: ?>
        <div class="table-wrap" style="margin-top:1rem">
            <table class="table">
                <thead>
                    <tr>
                        <th>Predicted</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>Market avg</th>
                        <th>Target</th>
                        <th>Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?= e($prediction->currency) ?> <?= e(number_format($prediction->predictedSalary, 0)) ?></strong></td>
                        <td><?= e($prediction->currency) ?> <?= e(number_format($prediction->minSalary, 0)) ?></td>
                        <td><?= e($prediction->currency) ?> <?= e(number_format($prediction->maxSalary, 0)) ?></td>
                        <td><?= e($prediction->currency) ?> <?= e(number_format($prediction->marketAverage, 0)) ?></td>
                        <td><?= e($prediction->currency) ?> <?= e(number_format($prediction->recommendedTarget, 0)) ?></td>
                        <td><span class="badge"><?= (int) $prediction->confidenceScore ?>/100</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p><strong><?= e($prediction->careerLevel) ?></strong> · <?= e($prediction->jobTitle) ?> · <?= e($prediction->locationLabel) ?> · <?= e($prediction->industry) ?></p>
        <p><?= e((string) ($prediction->analysis['explanation'] ?? '')) ?></p>
        <p>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/salary-intelligence/predictions/' . (int) $prediction->id . '/export/pdf')) ?>">Export PDF</a>
        </p>
    <?php endif; ?>
</section>

<?php if ($prediction !== null): ?>
<section class="panel">
    <h2 class="panel__title">Impact factors</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Factor</th>
                    <th>Impact</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($impacts as $impact): ?>
                    <?php if (!is_array($impact)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= e((string) ($impact['label'] ?? '')) ?></td>
                        <td><?= e((string) ($impact['pct'] ?? 0)) ?>%</td>
                        <td><?= e((string) ($impact['detail'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Negotiation tips</h2>
    <ul>
        <?php foreach ($tips as $tip): ?>
            <li><?= e((string) $tip) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($versions !== []): ?>
<section class="panel">
    <h2 class="panel__title">Recent predictions</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Predicted</th>
                    <th>Confidence</th>
                    <th>Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $row): ?>
                    <tr>
                        <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                        <td><?= e((string) ($row['currency'] ?? '')) ?> <?= e(number_format((float) ($row['predicted_salary'] ?? 0), 0)) ?></td>
                        <td><?= (int) ($row['confidence_score'] ?? 0) ?></td>
                        <td><?= e((string) ($row['career_level'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($history !== []): ?>
<section class="panel">
    <h2 class="panel__title">Salary history</h2>
    <ul>
        <?php foreach (array_slice($history, 0, 8) as $row): ?>
            <li><?= e((string) ($row['headline'] ?? '')) ?> · <?= e((string) ($row['created_at'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
