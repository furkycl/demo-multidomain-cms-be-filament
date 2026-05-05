<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Brand-neutral demo: 3 site, hepsi neutral placeholder içerikle
        $sites = [
            ['domain' => 'site-a.local', 'name' => 'Site A', 'city' => 'City A', 'country' => 'GB'],
            ['domain' => 'site-b.local', 'name' => 'Site B', 'city' => 'City B', 'country' => 'US'],
            ['domain' => 'site-c.local', 'name' => 'Site C', 'city' => 'City C', 'country' => 'FR'],
        ];

        foreach ($sites as $s) {
            $this->command->call('multi-cms:provision-site', [
                '--domain' => $s['domain'], '--name' => $s['name'],
                '--brand' => 'kaplan', '--city' => $s['city'], '--country' => $s['country'],
                '--locales' => 'tr,en', '--force' => true,
            ]);
        }
    }
}
