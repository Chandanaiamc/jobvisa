<?php

declare(strict_types=1);

/**
 * Auth layout shell.
 *
 * Expected variables: $title, $contentView (relative under views/), plus any view data.
 *
 * @var string $title
 * @var string $contentView
 */

use JobVisa\App\Security\SessionManager;

$flashSuccess = SessionManager::getFlash('success');
$flashError = SessionManager::getFlash('error');
/** @var array<string, list<string>> $errors */
$errors = $errors ?? [];
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
    <?php require base_path('app/views/auth/partials/header.php'); ?>

    <main class="auth-main" id="main" tabindex="-1">
        <div class="auth-shell">
            <?php require base_path('app/views/auth/partials/flash.php'); ?>
            <?php require base_path('app/views/' . $contentView . '.php'); ?>
        </div>
    </main>

    <?php require base_path('app/views/auth/partials/footer.php'); ?>
    <script src="<?= e(asset('js/auth.js')) ?>" defer></script>
    <script src="<?= e(asset('js/a11y.js')) ?>" defer></script>
</body>
</html>
