<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Static public pages controller.
 */
final class PagesController extends Controller
{
    /**
     * Display the about page.
     */
    public function about(): void
    {
        $this->render('pages/about', [
            'title' => 'About',
        ]);
    }

    /**
     * Display the contact page.
     */
    public function contact(): void
    {
        $this->render('pages/contact', [
            'title' => 'Contact',
        ]);
    }

    /**
     * Display the jobs listing page.
     */
    public function jobs(): void
    {
        $this->render('pages/jobs', [
            'title' => 'Jobs',
        ]);
    }

    /**
     * Display the companies page.
     */
    public function companies(): void
    {
        $this->render('pages/companies', [
            'title' => 'Companies',
        ]);
    }
}
