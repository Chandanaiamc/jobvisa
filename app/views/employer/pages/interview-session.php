<?php

declare(strict_types=1);

/**
 * @var \JobVisa\App\Domain\InterviewAssistant\DTO\InterviewSessionDTO $session
 * @var string $version
 * @var string $disclaimer
 */

$sc = $session->scorecard ?? null;
$tech = (int) ($sc['technical_score'] ?? 70);
$beh = (int) ($sc['behavioral_score'] ?? 70);
$comm = (int) ($sc['communication_score'] ?? 70);
$culture = (int) ($sc['culture_fit_score'] ?? 70);
$notes = (string) ($sc['notes'] ?? '');
$hire = (string) ($sc['hiring_recommendation'] ?? 'pending');
$scores = $session->contextScores;
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">Interview — <?= e($session->candidateName) ?></h1>
            <p class="panel__lead"><?= e($session->jobTitle) ?> · <?= e($session->candidateEmail) ?></p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/employer/interview-assistant?job=' . $session->jobId)) ?>">Back</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?> · Session #<?= $session->id ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">AI context scores</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Resume</th>
                    <th>Match</th>
                    <th>Ranking</th>
                    <th>Skills</th>
                    <th>Experience</th>
                    <th>Education</th>
                    <th>Certs</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= (int) ($scores['resume_overall'] ?? 0) ?></td>
                    <td><?= (int) ($scores['match_overall'] ?? 0) ?></td>
                    <td><?= (int) ($scores['ranking_overall'] ?? 0) ?></td>
                    <td><?= (int) ($scores['skills_score'] ?? 0) ?></td>
                    <td><?= (int) ($scores['experience_score'] ?? 0) ?></td>
                    <td><?= (int) ($scores['education_score'] ?? 0) ?></td>
                    <td><?= (int) ($scores['certification_score'] ?? 0) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Strengths</h2>
    <ul>
        <?php foreach ($session->strengths as $line): ?>
            <li><?= e($line) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Weaknesses / risks</h2>
    <ul>
        <?php foreach ($session->weaknesses as $line): ?>
            <li><?= e($line) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Interviewer recommendations</h2>
    <ul>
        <?php foreach ($session->recommendations as $line): ?>
            <li><?= e($line) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Technical questions</h2>
    <ol class="record-list">
        <?php foreach ($session->technicalQuestions as $q): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($q['prompt'] ?? '')) ?></strong>
                    <p class="muted">Focus: <?= e((string) ($q['focus'] ?? '')) ?> · <?= e((string) ($q['difficulty'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="panel">
    <h2 class="panel__title">Behavioral questions</h2>
    <ol class="record-list">
        <?php foreach ($session->behavioralQuestions as $q): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($q['prompt'] ?? '')) ?></strong>
                    <p class="muted">Focus: <?= e((string) ($q['focus'] ?? '')) ?> · <?= e((string) ($q['difficulty'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="panel">
    <h2 class="panel__title">Interview scorecard</h2>
    <?php if ($sc !== null): ?>
        <p class="muted">Last overall: <strong><?= (int) ($sc['overall_score'] ?? 0) ?></strong>/100 · Recommendation: <?= e((string) ($sc['hiring_recommendation'] ?? '')) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= e(app_url('/employer/interview-assistant/sessions/' . $session->id . '/scorecard')) ?>" class="recruiter-search">
        <?= csrf_field() ?>
        <label class="field">
            <span class="muted">Technical (0–100)</span>
            <input type="number" name="technical_score" min="0" max="100" required value="<?= $tech ?>">
        </label>
        <label class="field">
            <span class="muted">Behavioral (0–100)</span>
            <input type="number" name="behavioral_score" min="0" max="100" required value="<?= $beh ?>">
        </label>
        <label class="field">
            <span class="muted">Communication (0–100)</span>
            <input type="number" name="communication_score" min="0" max="100" required value="<?= $comm ?>">
        </label>
        <label class="field">
            <span class="muted">Culture fit (0–100)</span>
            <input type="number" name="culture_fit_score" min="0" max="100" required value="<?= $culture ?>">
        </label>
        <label class="field">
            <span class="muted">Hiring recommendation</span>
            <select name="hiring_recommendation" required>
                <?php foreach (['pending' => 'Pending', 'hire' => 'Hire', 'maybe' => 'Maybe', 'no' => 'No'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $hire === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field" style="width:100%">
            <span class="muted">Notes</span>
            <textarea name="notes" rows="4" maxlength="5000"><?= e($notes) ?></textarea>
        </label>
        <button type="submit" class="btn btn--primary">Save scorecard</button>
    </form>
</section>
