<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $user
 * @var array<string, mixed>|null $profile
 * @var array{score: int, sections: array} $completeness
 * @var list<array<string, mixed>> $education
 * @var list<array<string, mixed>> $experience
 * @var list<array<string, mixed>> $skills
 * @var list<array<string, mixed>> $languages
 * @var array<string, mixed>|null $resume
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Seeker Profile') ?> | JobVisa.lk</title>
    <link rel="stylesheet" href="<?= e(asset('css/jobseeker.css')) ?>">
</head>
<body class="dash-body">
<main class="dash-main" style="max-width:960px;margin:0 auto">
    <p class="eyebrow">Admin · read only</p>
    <h1><?= e((string) ($user['full_name'] ?? 'Seeker')) ?></h1>
    <p class="muted"><?= e((string) ($user['email'] ?? '')) ?> · Completeness <?= (int) ($completeness['score'] ?? 0) ?>%</p>

    <section class="panel">
        <h2 class="panel__title">Profile</h2>
        <ul class="meta-list">
            <li><strong>Headline:</strong> <?= e((string) ($profile['headline'] ?? '—')) ?></li>
            <li><strong>Phone:</strong> <?= e((string) ($profile['user_phone'] ?? '—')) ?></li>
            <li><strong>WhatsApp:</strong> <?= e((string) ($profile['whatsapp'] ?? '—')) ?></li>
            <li><strong>Nationality:</strong> <?= e((string) ($profile['nationality_name'] ?? '—')) ?></li>
            <li><strong>Preferred country:</strong> <?= e((string) ($profile['preferred_country_name'] ?? '—')) ?></li>
            <li><strong>Summary:</strong> <?= nl2br(e((string) ($profile['summary'] ?? '—'))) ?></li>
        </ul>
    </section>

    <section class="panel">
        <h2 class="panel__title">Education (<?= count($education) ?>)</h2>
        <?php foreach ($education as $row): ?>
            <p><strong><?= e((string) $row['degree']) ?></strong> — <?= e((string) $row['institution']) ?></p>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <h2 class="panel__title">Experience (<?= count($experience) ?>)</h2>
        <?php foreach ($experience as $row): ?>
            <p><strong><?= e((string) $row['job_title']) ?></strong> — <?= e((string) $row['company_name']) ?></p>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <h2 class="panel__title">Skills</h2>
        <p><?= e(implode(', ', array_map(static fn ($s) => (string) $s['skill_name'], $skills)) ?: '—') ?></p>
    </section>

    <section class="panel">
        <h2 class="panel__title">Languages</h2>
        <p><?= e(implode(', ', array_map(static fn ($s) => (string) $s['language_name'], $languages)) ?: '—') ?></p>
    </section>

    <section class="panel">
        <h2 class="panel__title">CV</h2>
        <p><?= !empty($resume['file_path']) ? 'CV on file' : 'No CV uploaded' ?></p>
    </section>

    <p><a class="btn btn--secondary" href="<?= e(app_url('/admin')) ?>">Back to admin</a></p>
</main>
</body>
</html>
