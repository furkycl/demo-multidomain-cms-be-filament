<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::firstOrCreate(
            ['domain' => 'localhost'],
            [
                'name' => 'Demo Site',
                'brand' => 'kaplan',
                'city' => 'London',
                'country' => 'GB',
                'theme' => ['primary_color' => '#0ea5e9', 'font' => 'Inter'],
            ],
        );

        $home = Page::firstOrCreate(
            ['site_id' => $site->id, 'locale' => 'tr', 'slug' => '/'],
            [
                'title' => 'Ana Sayfa',
                'is_published' => true,
                'seo' => ['title' => 'Demo Site', 'description' => 'multi-cms demo'],
            ],
        );

        $home->blocks()->delete();

        Block::create([
            'page_id' => $home->id,
            'type' => 'header',
            'order' => 0,
            'content' => [
                'title' => 'Demo Site',
                'background_color' => '#0ea5e9',
                'links' => [
                    ['label' => 'Ana Sayfa', 'href' => '/'],
                    ['label' => 'Hakkımızda', 'href' => '/about'],
                ],
            ],
        ]);

        Block::create([
            'page_id' => $home->id,
            'type' => 'hero',
            'order' => 1,
            'content' => [
                'headline' => 'Tek panelden tüm sitelerini yönet',
                'subheadline' => 'Filament admin paneli ile hızlı içerik yönetimi.',
                'cta_label' => 'Hemen başla',
                'cta_href' => '/get-started',
                'background_color' => '#0f172a',
                'text_color' => '#ffffff',
            ],
        ]);

        Block::create([
            'page_id' => $home->id,
            'type' => 'rich_text',
            'order' => 2,
            'content' => [
                'markdown' => "## Nasıl çalışır?\n\nFilament panelinde site ekle, sayfa aç, blok düzenle.",
            ],
        ]);

        Block::create([
            'page_id' => $home->id,
            'type' => 'footer',
            'order' => 3,
            'content' => [
                'text' => '© '.date('Y').' Demo Site',
                'background_color' => '#0f172a',
                'text_color' => '#94a3b8',
            ],
        ]);
    }
}
