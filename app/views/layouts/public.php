<?php

declare(strict_types=1);

/**
 * Public site layout.
 *
 * @var string $title
 * @var string $contentView
 */

$contentView = $contentView ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-app-base="<?= e(rtrim((string) app_url(''), '/')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | JobVisa.lk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/a11y.css')) ?>">
    <?php if (!empty($extraHead)): ?>
        <?= $extraHead ?>
    <?php endif; ?>
</head>
<body class="public-body">
    <?php require base_path('app/views/partials/skip-link.php'); ?>
    <header class="public-header">
        <div class="public-header__inner">
            <a class="public-brand" href="<?= e(app_url('/')) ?>">
                <span class="public-brand__mark" aria-hidden="true">JV</span>
                <span class="public-brand__text">JobVisa.lk</span>
            </a>
            <nav class="public-nav" aria-label="Primary">
                <a href="<?= e(app_url('/jobs')) ?>"<?= in_array(($contentView ?? ''), ['pages/jobs/list', 'pages/jobs/detail', 'pages/jobs/missing'], true) ? ' aria-current="page"' : '' ?>>Jobs</a>
                <a href="<?= e(app_url('/about')) ?>">About</a>
                <a class="public-nav__cta" href="<?= e(app_url('/login')) ?>">Sign in</a>
            </nav>
        </div>
    </header>

    <main class="public-main" id="main" tabindex="-1">
        <?php require base_path('app/views/' . $contentView . '.php'); ?>
    </main>

    <footer class="public-footer">
        <div class="public-footer__inner">
            <p>JobVisa.lk — overseas careers for Sri Lankan professionals.</p>
        </div>
    </footer>

    <script src="<?= e(asset('js/api-client.js')) ?>" defer></script>
    <script src="<?= e(asset('js/jobs-api.js')) ?>" defer></script>
    <script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
