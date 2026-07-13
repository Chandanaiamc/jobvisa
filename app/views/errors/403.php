<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $message */
$title = $title ?? 'Forbidden';
$message = $message ?? 'You do not have permission to access this area.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | JobVisa.lk</title>
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
</head>
<body class="auth-body">
    <main class="auth-main">
        <div class="auth-shell">
            <section class="auth-card">
                <p class="eyebrow">403</p>
                <h1 class="auth-card__title">Forbidden</h1>
                <p class="auth-card__lead"><?= e($message) ?></p>
                <p><a class="btn btn--primary" href="<?= e(app_url('/')) ?>">Go home</a></p>
            </section>
        </div>
    </main>
</body>
</html>
