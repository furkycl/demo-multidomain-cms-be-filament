<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * site-a.local domain'ine sahip Kaplan-tarzı dolu bir demo site oluşturur.
 *
 * 5 sayfa × 2 locale (TR + EN) = 10 page kaydı, ~40+ blok.
 *
 * Bu, ProvisionSite artisan komutunu seeder içinden çalıştırarak
 * tek kaynak (provision logic) prensibini korur.
 */
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
        ]);
    }
}
