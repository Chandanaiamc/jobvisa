<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\OfferEvaluation\DTO\OfferEvaluationAnalysisDTO|null $offerAnalysis
 * @var list<array<string, mixed>> $matchedJobs
 * @var array<string, mixed> $defaults
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'offer-evaluation';
$offerAnalysis = $offerAnalysis ?? null;
$matchedJobs = $matchedJobs ?? [];
$defaults = $defaults ?? [];
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$a = $offerAnalysis?->analysis ?? [];
$snap = is_array($a['offer_snapshot'] ?? null) ? $a['offer_snapshot'] : [];
$market = is_array($a['market_comparison'] ?? null) ? $a['market_comparison'] : [];
$counter = is_array($a['counter_offer'] ?? null) ? $a['counter_offer'] : [];

$formTitle = (string) ($offerAnalysis?->jobTitle ?: ($defaults['job_title'] ?? ''));
$formCompany = (string) ($offerAnalysis?->companyName ?? '');
$formCurrency = (string) ($offerAnalysis?->currency ?: ($defaults['currency'] ?? 'USD'));
$formBase = (string) ($offerAnalysis?->baseSalary ?: ($defaults['base_salary'] ?? ''));
$formBonus = (string) ($snap['bonus'] ?? '');
$formEquity = (string) ($snap['equity_value'] ?? '');
$formLocation = (string) ($snap['location'] ?? ($defaults['location'] ?? ''));
$formMode = (string) ($snap['work_mode'] ?? 'onsite');
$formBenefits = implode(', ', array_map('strval', $snap['benefits'] ?? []));
$formNotes = (string) ($snap['notes'] ?? '');
$formJobId = (int) ($offerAnalysis?->jobId ?? 0);
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Offer Evaluation Assistant</h1>
            <p class="panel__lead">Score compensation, benefits, growth and lifestyle — then get accept / negotiate / decline guidance.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title"><?= $offerAnalysis ? 'Recalculate offer' : 'Evaluate offer' ?></h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/' . ($offerAnalysis ? 'recalculate' : 'evaluate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Linked published job (optional)</span>
                <select name="job_id">
                    <option value="">— None —</option>
                    <?php foreach ($matchedJobs as $job): ?>
                        <?php $jid = (int) ($job['job_id'] ?? 0); if ($jid < 1) { continue; } ?>
                        <option value="<?= $jid ?>" <?= $formJobId === $jid ? 'selected' : '' ?>><?= e((string) ($job['job_title'] ?? 'Job #' . $jid)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="muted">Job title</span>
                <input type="text" name="job_title" maxlength="191" required value="<?= e($formTitle) ?>">
            </label>
            <label class="field">
                <span class="muted">Company</span>
                <input type="text" name="company_name" maxlength="191" value="<?= e($formCompany) ?>">
            </label>
            <label class="field">
                <span class="muted">Currency</span>
                <input type="text" name="currency" maxlength="3" value="<?= e($formCurrency) ?>">
            </label>
            <label class="field">
                <span class="muted">Base salary</span>
                <input type="number" name="base_salary" step="0.01" min="1" required value="<?= e($formBase) ?>">
            </label>
            <label class="field">
                <span class="muted">Bonus</span>
                <input type="number" name="bonus" step="0.01" min="0" value="<?= e($formBonus) ?>">
            </label>
            <label class="field">
                <span class="muted">Equity value (est.)</span>
                <input type="number" name="equity_value" step="0.01" min="0" value="<?= e($formEquity) ?>">
            </label>
            <label class="field">
                <span class="muted">Location</span>
                <input type="text" name="location" maxlength="128" value="<?= e($formLocation) ?>">
            </label>
            <label class="field">
                <span class="muted">Work mode</span>
                <select name="work_mode">
                    <?php foreach (['onsite' => 'Onsite', 'hybrid' => 'Hybrid', 'remote' => 'Remote'] as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $formMode === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="muted">Benefits (comma-separated)</span>
                <input type="text" name="benefits" maxlength="500" value="<?= e($formBenefits) ?>" placeholder="health, visa, housing, learning">
            </label>
            <label class="field">
                <span class="muted">Contract months (0 = permanent)</span>
                <input type="number" name="contract_months" min="0" max="60" value="<?= e((string) ($snap['contract_months'] ?? 0)) ?>">
            </label>
            <label class="field">
                <span class="muted">Notes</span>
                <textarea name="notes" rows="3" maxlength="2000"><?= e($formNotes) ?></textarea>
            </label>
            <button type="submit" class="btn btn--primary"><?= $offerAnalysis ? 'Recalculate evaluation' : 'Evaluate offer' ?></button>
        </form>
    <?php endif; ?>
</section>

<?php if ($offerAnalysis !== null): ?>
<section class="panel">
    <h2 class="panel__title">Evaluation result</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Overall</th>
                    <th>Recommendation</th>
                    <th>Compensation</th>
                    <th>Benefits</th>
                    <th>Growth</th>
                    <th>Lifestyle</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= (int) $offerAnalysis->overallScore ?>/100</strong></td>
                    <td><span class="badge"><?= e(strtoupper($offerAnalysis->recommendation)) ?></span></td>
                    <td><?= (int) $offerAnalysis->compensationScore ?></td>
                    <td><?= (int) $offerAnalysis->benefitsScore ?></td>
                    <td><?= (int) $offerAnalysis->growthScore ?></td>
                    <td><?= (int) $offerAnalysis->lifestyleScore ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><?= e((string) ($a['summary'] ?? '')) ?></p>
    <p class="muted"><?= e((string) ($a['recommendation_label'] ?? '')) ?></p>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/offer-evaluation/analyses/' . (int) $offerAnalysis->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Market comparison</h2>
    <div class="table-wrap">
        <table class="table">
            <tbody>
                <tr><th>Position</th><td><?= e((string) ($market['position_label'] ?? '')) ?></td></tr>
                <tr><th>Market mid</th><td><?= e((string) ($market['market_mid'] ?? '—')) ?></td></tr>
                <tr><th>Market band</th><td><?= e((string) ($market['market_min'] ?? '—')) ?> – <?= e((string) ($market['market_max'] ?? '—')) ?></td></tr>
                <tr><th>vs market %</th><td><?= isset($market['vs_market_pct']) && $market['vs_market_pct'] !== null ? e((string) $market['vs_market_pct']) . '%' : '—' ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Pros & cons</h2>
    <h3>Pros</h3>
    <ul>
        <?php foreach (($a['pros'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3>Cons</h3>
    <ul>
        <?php foreach (($a['cons'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Counter-offer & negotiation</h2>
    <div class="table-wrap">
        <table class="table">
            <tbody>
                <tr><th>Ask base</th><td><?= e((string) ($counter['ask_base'] ?? '')) ?> <?= e((string) ($counter['currency'] ?? $offerAnalysis->currency)) ?></td></tr>
                <tr><th>Stretch</th><td><?= e((string) ($counter['stretch_base'] ?? '')) ?></td></tr>
                <tr><th>Walk-away</th><td><?= e((string) ($counter['walk_away_floor'] ?? '')) ?></td></tr>
                <tr><th>Focus</th><td><?= e((string) ($counter['focus'] ?? '')) ?></td></tr>
            </tbody>
        </table>
    </div>
    <h3>Talking points</h3>
    <ul>
        <?php foreach (($a['negotiation_talking_points'] ?? []) as $tip): ?>
            <li><?= e((string) $tip) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3>Decision checklist</h3>
    <ul>
        <?php foreach (($a['decision_checklist'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Recent history</h2>
    <?php if ($history === []): ?>
        <p class="muted">No offer evaluations yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Headline</th><th>Action</th><th>Score</th><th>When</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($history, 0, 8) as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['headline'] ?? '')) ?></td>
                            <td><?= e((string) ($row['action'] ?? '')) ?></td>
                            <td><?= (int) ($row['overall_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
