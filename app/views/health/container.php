<?php

declare(strict_types=1);

/** @var bool $containerRunning */
/** @var bool $configLoaded */
/** @var bool $singletonPassed */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Health | JobVisa.lk</title>
</head>
<body>
    <p>Container Status: <?= $containerRunning ? 'Running' : 'Failed' ?></p>
    <p>Config Status: <?= $configLoaded ? 'Loaded' : 'Failed' ?></p>
    <p>Singleton Status: <?= $singletonPassed ? 'Passed' : 'Failed' ?></p>
</body>
</html>
