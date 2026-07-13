<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $label
 * @var string $area
 * @var string $userName
 * @var string $userEmail
 * @var string $userRole
 * @var bool $emailVerified
 */
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
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/a11y.css')) ?>">
</head>
<body class="auth-body">
    <?php require base_path('app/views/partials/skip-link.php'); ?>
    <header class="auth-header">
        <div class="auth-header__inner">
            <a class="auth-brand" href="<?= e(app_url('/')) ?>">
                <span class="auth-brand__mark" aria-hidden="true">JV</span>
                <span class="auth-brand__text"><strong>JobVisa</strong><span>.lk</span></span>
            </a>
            <nav class="auth-header__nav" aria-label="Session">
                <form method="post" action="<?= e(app_url('/logout')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--ghost">Sign out</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="auth-main" id="main" tabindex="-1">
        <div class="auth-shell">
            <section class="auth-card">
                <p class="eyebrow"><?= e(strtoupper($area)) ?> DASHBOARD</p>
                <h1 class="auth-card__title"><?= e($label) ?> dashboard</h1>
                <p class="auth-card__lead">Temporary protected placeholder — full dashboards arrive later.</p>
                <ul class="portal-meta">
                    <li><strong>Name:</strong> <?= e($userName) ?></li>
                    <li><strong>Role:</strong> <?= e($userRole) ?></li>
                    <li><strong>Email:</strong> <?= e($userEmail) ?></li>
                    <li><strong>Email verification:</strong> <?= $emailVerified ? 'Verified' : 'Not verified' ?></li>
                </ul>
                <p><a class="btn btn--primary" href="<?= e(app_url('/')) ?>">Return home</a></p>
            </section>
        </div>
    </main>

    <?php require base_path('app/views/auth/partials/footer.php'); ?>
    <script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
