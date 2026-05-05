<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->call('multi-cms:provision-site', [
            '--domain' => 'site-a.local',
            '--name' => 'Kaplan London (Demo)',
            '--brand' => 'kaplan',
            '--city' => 'London',
            '--country' => 'GB',
            '--locales' => 'tr,en',
            '--force' => true,
            '--youtube' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
        ]);
    }
}
