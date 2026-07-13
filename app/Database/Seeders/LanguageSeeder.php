<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders;

use JobVisa\App\Database\Seeders\Support\Seeder;

/**
 * Seeds language reference data.
 */
final class LanguageSeeder extends Seeder
{
    public function name(): string
    {
        return 'Languages';
    }

    public function run(): void
    {
        if (!$this->tableExists('languages')) {
            return;
        }

        $now = $this->now();

        $languages = [
            ['name' => 'English', 'code' => 'en'],
            ['name' => 'Sinhala', 'code' => 'si'],
            ['name' => 'Tamil', 'code' => 'ta'],
            ['name' => 'Arabic', 'code' => 'ar'],
            ['name' => 'Hindi', 'code' => 'hi'],
            ['name' => 'Malay', 'code' => 'ms'],
            ['name' => 'Japanese', 'code' => 'ja'],
            ['name' => 'Korean', 'code' => 'ko'],
        ];

        foreach ($languages as $language) {
            $this->upsertByUnique(
                'languages',
                ['code' => $language['code']],
                [
                    'name' => $language['name'],
                    'code' => $language['code'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['name', 'is_active', 'updated_at']
            );
        }
    }
}
