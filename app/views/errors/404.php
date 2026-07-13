<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $path */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | JobVisa.lk</title>
</head>
<body>
    <h1><?= e($title) ?></h1>
    <p>The page <strong><?= e($path) ?></strong> could not be found.</p>
    <p><a href="<?= e(url('public')) ?>">Return to Home</a></p>
</body>
</html>
