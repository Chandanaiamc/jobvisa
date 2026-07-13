<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $activeNav
 * @var string $contentView
 * @var array<string, mixed> $actor
 * @var array{score: int, sections: array<string, array{label: string, complete: bool, weight: int}>} $completeness
 */

$nav = [
    'overview' => ['label' => 'Overview', 'path' => '/jobseeker'],
    'profile' => ['label' => 'Profile', 'path' => '/jobseeker/profile'],
    'resumes' => ['label' => 'Resumes', 'path' => '/jobseeker/resumes'],
    'education' => ['label' => 'Education', 'path' => '/jobseeker/education'],
    'experience' => ['label' => 'Experience', 'path' => '/jobseeker/experience'],
    'skills' => ['label' => 'Skills', 'path' => '/jobseeker/skills'],
    'languages' => ['label' => 'Languages', 'path' => '/jobseeker/languages'],
    'cv' => ['label' => 'CV', 'path' => '/jobseeker/cv'],
    'settings' => ['label' => 'Settings', 'path' => '/jobseeker/settings'],
];

$score = (int) ($completeness['score'] ?? 0);
$name = (string) ($actor['full_name'] ?? 'Job Seeker');
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
    <aside class="dash-sidebar" aria-label="Job seeker navigation">
        <a class="dash-brand" href="<?= e(app_url('/jobseeker')) ?>">
            <span class="dash-brand__mark" aria-hidden="true">JV</span>
            <span><strong>JobVisa</strong>.lk</span>
        </a>
        <p class="dash-sidebar__user"><?= e($name) ?></p>
        <nav class="dash-nav">
            <?php foreach ($nav as $key => $item): ?>
                <a class="dash-nav__link <?= $activeNav === $key ? 'is-active' : '' ?>"
                   href="<?= e(app_url($item['path'])) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(app_url('/logout')) ?>" class="dash-logout">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost">Sign out</button>
        </form>
    </aside>

    <div class="dash-main">
        <header class="dash-top">
            <div>
                <p class="eyebrow">Job seeker</p>
                <h1><?= e($title) ?></h1>
            </div>
            <div class="completeness" aria-label="Profile completeness">
                <div class="completeness__meta">
                    <span>Profile completeness</span>
                    <strong><?= $score ?>%</strong>
                </div>
                <div class="completeness__bar" role="progressbar" aria-valuenow="<?= $score ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: <?= $score ?>%"></span>
                </div>
            </div>
        </header>

        <?php require base_path('app/views/jobseeker/partials/flash.php'); ?>

        <main class="dash-content" id="main" tabindex="-1">
            <?php require base_path('app/views/' . str_replace('.', '/', $contentView) . '.php'); ?>
        </main>
    </div>
</div>
<script src="<?= e(asset('js/jobseeker.js')) ?>" defer></script>
<script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
