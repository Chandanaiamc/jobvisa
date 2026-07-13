<?php

declare(strict_types=1);

/**
 * Front controller — public HTTP entry point for the MVC framework.
 */

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->run();
