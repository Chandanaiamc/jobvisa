<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Public home page controller.
 */
final class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index(): void
    {
        $this->render('home/index', [
            'title' => 'Home',
            'frameworkVersion' => '1.0',
            'environment' => (string) config('app.env', 'local'),
            'status' => 'Running',
        ]);
    }
}
