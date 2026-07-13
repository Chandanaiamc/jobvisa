<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $activeNav
 * @var string $contentView
 * @var array<string, mixed> $actor
 */

$nav = [
    'overview' => ['label' => 'AI Dashboard', 'path' => '/employer'],
    'recruiter' => ['label' => 'Recruiter Assistant', 'path' => '/employer/recruiter-assistant'],
    'interview' => ['label' => 'Interview Assistant', 'path' => '/employer/interview-assistant'],
    'jobs' => ['label' => 'Jobs & Ranking', 'path' => '/employer/jobs'],
];

$name = (string) ($actor['full_name'] ?? 'Employer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | JobVisa.lk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/jobseeker.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/a11y.css')) ?>">
</head>
<body class="dash-body">
<?php require base_path('app/views/partials/skip-link.php'); ?>
<div class="dash">
    <aside class="dash-nav" aria-label="Employer navigation">
        <div class="dash-brand">
            <a href="<?= e(app_url('/employer')) ?>">JobVisa<span>.lk</span></a>
            <p class="muted">Employer</p>
        </div>
        <nav>
            <?php foreach ($nav as $key => $item): ?>
                <a class="dash-nav__link <?= ($activeNav ?? '') === $key ? 'is-active' : '' ?>"
                   href="<?= e(app_url($item['path'])) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="dash-nav__foot">
            <p><?= e($name) ?></p>
            <form method="post" action="<?= e(app_url('/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Sign out</button>
            </form>
        </div>
    </aside>
    <main class="dash-main" id="main" tabindex="-1">
        <?php
        $flashSuccess = \JobVisa\App\Security\SessionManager::getFlash('success');
        $flashError = \JobVisa\App\Security\SessionManager::getFlash('error');
        ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert--success" role="status"><?= e((string) $flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert--danger" role="alert"><?= e((string) $flashError) ?></div>
        <?php endif; ?>
        <?php require base_path('app/views/' . $contentView . '.php'); ?>
    </main>
</div>
<script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
