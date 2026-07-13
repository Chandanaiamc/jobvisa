<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds default employer and seeker subscription plans.
 */
final class SubscriptionPlanSeeder extends Seeder
{
    public function name(): string
    {
        return 'Subscription Plans';
    }

    public function run(): void
    {
        if (!$this->tableExists('subscription_plans')) {
            return;
        }

        $now = $this->now();

        $plans = [
            [
                'code' => 'employer_free',
                'name' => 'Employer Free',
                'audience' => 'employer',
                'description' => 'Starter plan for verified employers',
                'price' => '0.00',
                'currency' => 'LKR',
                'duration_days' => 30,
                'job_post_limit' => 2,
                'features_json' => json_encode(['job_posts' => 2, 'featured' => false], JSON_THROW_ON_ERROR),
            ],
            [
                'code' => 'employer_basic',
                'name' => 'Employer Basic',
                'audience' => 'employer',
                'description' => 'Standard hiring package',
                'price' => '9900.00',
                'currency' => 'LKR',
                'duration_days' => 30,
                'job_post_limit' => 10,
                'features_json' => json_encode(['job_posts' => 10, 'featured' => false], JSON_THROW_ON_ERROR),
            ],
            [
                'code' => 'employer_pro',
                'name' => 'Employer Pro',
                'audience' => 'employer',
                'description' => 'High-volume hiring with featured listings',
                'price' => '24900.00',
                'currency' => 'LKR',
                'duration_days' => 30,
                'job_post_limit' => 50,
                'features_json' => json_encode(['job_posts' => 50, 'featured' => true], JSON_THROW_ON_ERROR),
            ],
            [
                'code' => 'seeker_free',
                'name' => 'Seeker Free',
                'audience' => 'seeker',
                'description' => 'Free job seeker access',
                'price' => '0.00',
                'currency' => 'LKR',
                'duration_days' => 365,
                'job_post_limit' => null,
                'features_json' => json_encode(['applications' => 20, 'cv_slots' => 1], JSON_THROW_ON_ERROR),
            ],
            [
                'code' => 'seeker_premium',
                'name' => 'Seeker Premium',
                'audience' => 'seeker',
                'description' => 'Priority visibility and more applications',
                'price' => '2900.00',
                'currency' => 'LKR',
                'duration_days' => 30,
                'job_post_limit' => null,
                'features_json' => json_encode(['applications' => 100, 'cv_slots' => 3, 'priority' => true], JSON_THROW_ON_ERROR),
            ],
        ];

        foreach ($plans as $plan) {
            $this->upsertByUnique(
                'subscription_plans',
                ['code' => $plan['code']],
                [
                    'code' => $plan['code'],
                    'name' => $plan['name'],
                    'audience' => $plan['audience'],
                    'description' => $plan['description'],
                    'price' => $plan['price'],
                    'currency' => $plan['currency'],
                    'duration_days' => $plan['duration_days'],
                    'job_post_limit' => $plan['job_post_limit'],
                    'features_json' => $plan['features_json'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name',
                    'audience',
                    'description',
                    'price',
                    'currency',
                    'duration_days',
                    'job_post_limit',
                    'features_json',
                    'is_active',
                    'updated_at',
                ]
            );
        }
    }
}
