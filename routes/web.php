<?php

declare(strict_types=1);

/**
 * Public web routes (unchanged URLs).
 *
 * @var \JobVisa\App\Routing\RouteRegistrar $router
 */

$router->gets([
    '/' => 'HomeController@index',
    '/about' => 'PagesController@about',
    '/contact' => 'PagesController@contact',
    '/jobs' => 'PagesController@jobs',
    '/companies' => 'PagesController@companies',
]);
