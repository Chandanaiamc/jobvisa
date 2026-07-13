<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $frameworkVersion */
/** @var string $environment */
/** @var string $status */

$environmentLabel = ucfirst(strtolower($environment));
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
    <p>JobVisa.lk Enterprise</p>
    <p>Framework Version <?= e($frameworkVersion) ?></p>
    <p>Environment: <?= e($environmentLabel) ?></p>
    <p>Status: <?= e($status) ?></p>
</body>
</html>
