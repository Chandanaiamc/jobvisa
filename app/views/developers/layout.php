<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $activeNav
 * @var string $contentView
 * @var list<array{slug: string, label: string, path: string}> $nav
 * @var string $portal_version
 * @var string $api_base
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · JobVisa.lk Developers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/developers.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/a11y.css')) ?>">
</head>
<body class="dp-body">
<?php require base_path('app/views/partials/skip-link.php'); ?>
<div class="dp-shell">
    <header class="dp-top">
        <a class="dp-brand" href="<?= e(url('/developers')) ?>">JobVisa<span>.lk</span> Developers</a>
        <div class="dp-top-meta">API v1 · Portal <?= e((string) $portal_version) ?></div>
    </header>

    <nav class="dp-nav" aria-label="Developer portal">
        <?php foreach ($nav as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="<?= $activeNav === $item['slug'] ? 'is-active' : '' ?>">
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <main id="main" tabindex="-1">
        <?php require base_path('app/views/' . $contentView . '.php'); ?>
    </main>

    <footer class="dp-footer">
        Base URL <code><?= e($api_base) ?></code>
        · Web routes and CSRF remain unchanged for browser sessions.
    </footer>
</div>
<script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
