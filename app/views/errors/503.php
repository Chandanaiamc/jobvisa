<?php

declare(strict_types=1);

/**
 * HTTP 503 — maintenance mode.
 *
 * @var string $title
 * @var string $appName
 */
$title = $title ?? 'Maintenance';
$appName = $appName ?? 'JobVisa.lk';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= htmlspecialchars($title . ' · ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; margin: 0; min-height: 100vh; display: grid; place-items: center;
            background: linear-gradient(160deg, #0f2740 0%, #1a3a52 45%, #0c1c2c 100%); color: #f4f7fb; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        h1 { font-size: 2rem; margin: 0 0 .75rem; letter-spacing: .02em; }
        p { line-height: 1.55; opacity: .9; }
    </style>
</head>
<body>
<main>
    <h1>We’ll be right back</h1>
    <p><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> is undergoing scheduled maintenance. Please try again in a few minutes.</p>
</main>
</body>
</html>
