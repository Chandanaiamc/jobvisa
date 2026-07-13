<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

require_once $basePath . '/bootstrap/autoload.php';
require_once $basePath . '/app/helpers/functions.php';

use App\Core\Config;

Config::loadEnv($basePath . '/.env');
Config::load($basePath . '/config');

date_default_timezone_set((string) config('app.timezone', 'Asia/Colombo'));
