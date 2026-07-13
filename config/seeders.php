<?php

declare(strict_types=1);

/**
 * Database seeder registry and demo account defaults.
 *
 * Order respects FK dependencies from migrations 001–031.
 */

use JobVisa\App\Database\Seeders\CitySeeder;
use JobVisa\App\Database\Seeders\CountrySeeder;
use JobVisa\App\Database\Seeders\DefaultAdminSeeder;
use JobVisa\App\Database\Seeders\DemoEmployerSeeder;
use JobVisa\App\Database\Seeders\DemoJobSeeder;
use JobVisa\App\Database\Seeders\DemoJobSeekerSeeder;
use JobVisa\App\Database\Seeders\JobCategorySeeder;
use JobVisa\App\Database\Seeders\JobTypeSeeder;
use JobVisa\App\Database\Seeders\LanguageSeeder;
use JobVisa\App\Database\Seeders\PermissionSeeder;
use JobVisa\App\Database\Seeders\RoleSeeder;
use JobVisa\App\Database\Seeders\SkillSeeder;
use JobVisa\App\Database\Seeders\SubscriptionPlanSeeder;

return [
    /*
    | Execution order (do not reorder without reviewing FKs).
    */
    'order' => [
        RoleSeeder::class,
        PermissionSeeder::class,
        CountrySeeder::class,
        CitySeeder::class,
        JobCategorySeeder::class,
        JobTypeSeeder::class,
        SkillSeeder::class,
        LanguageSeeder::class,
        SubscriptionPlanSeeder::class,
        DefaultAdminSeeder::class,
        DemoEmployerSeeder::class,
        DemoJobSeekerSeeder::class,
        DemoJobSeeder::class,
    ],

    /*
    | Local/demo accounts — override via .env (never use defaults in production).
    */
    'demo' => [
        'admin_email' => env('SEED_ADMIN_EMAIL', 'admin@jobvisa.lk'),
        'admin_password' => env('SEED_ADMIN_PASSWORD', 'ChangeMeAdmin!123'),
        'admin_name' => env('SEED_ADMIN_NAME', 'System Administrator'),

        'employer_email' => env('SEED_EMPLOYER_EMAIL', 'employer@demo.jobvisa.lk'),
        'employer_password' => env('SEED_EMPLOYER_PASSWORD', 'ChangeMeEmployer!123'),
        'employer_name' => env('SEED_EMPLOYER_NAME', 'Demo Employer'),

        'seeker_email' => env('SEED_SEEKER_EMAIL', 'seeker@demo.jobvisa.lk'),
        'seeker_password' => env('SEED_SEEKER_PASSWORD', 'ChangeMeSeeker!123'),
        'seeker_name' => env('SEED_SEEKER_NAME', 'Demo Job Seeker'),
    ],
];
