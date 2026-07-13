<?php

declare(strict_types=1);

/** @var string $title */
/** @var bool $success */
/** @var string $databaseName */
/** @var string $phpVersion */
/** @var string|null $mysqlVersion */
/** @var string|null $errorMessage */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | JobVisa.lk</title>
</head>
<body>
<?php if ($success): ?>
    <h1>Database Connection Successful</h1>

    <p><strong>Database:</strong><br>
    <?= e($databaseName) ?></p>

    <p><strong>PHP Version:</strong><br>
    <?= e($phpVersion) ?></p>

    <p><strong>MySQL Version:</strong><br>
    <?= e((string) $mysqlVersion) ?></p>
<?php else: ?>
    <h1>Database Connection Failed</h1>

    <p><strong>Database:</strong><br>
    <?= e($databaseName) ?></p>

    <p><strong>PHP Version:</strong><br>
    <?= e($phpVersion) ?></p>

    <p><strong>Error:</strong><br>
    <?= e((string) $errorMessage) ?></p>
<?php endif; ?>
</body>
</html>
