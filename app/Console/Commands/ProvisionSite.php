<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;

/**
 * 4 sayfalı (Home/Destinations/Products/Blog) Kaplan TV tarzı site provision komutu.
 */
class ProvisionSite extends Command
{
    protected $signature = 'multi-cms:provision-site
        {--domain= : Site domain}
        {--name= : Görüntülenen ad}
        {--brand= : kaplan|alpadia|azurlingua}
        {--city= : Şehir}
        {--country= : ISO-2 ülke}
        {--locales=tr,en : Locale\'ler}
        {--revalidate-url= : Frontend revalidate URL}
        {--revalidate-secret= : Secret (boş ise üretilir)}
        {--force : Üzerine yaz}
        {--youtube= : Hero video YouTube URL}';

    protected $description = '4 sayfalı (Home/Destinations/Products/Blog) Kaplan TV yapılı site provision et';

    public function handle(): int
    {
        $domain = $this->option('domain') ?: $this->ask('Domain');
        $name = $this->option('name') ?: $this->ask('Ad', $domain);
        $brand = $this->option('brand') ?: $this->choice('Brand', Site::BRANDS, 0);
        $city = $this->option('city') ?: $this->ask('Şehir', null);
        $country = $this->option('country') ?: $this->ask('Ülke (ISO-2)', null);
        $locales = array_filter(array_map('trim', explode(',', (string) $this->option('locales'))));
        $revalidateUrl = $this->option('revalidate-url');
        $revalidateSecret = $this->option('revalidate-secret') ?: bin2hex(random_bytes(32));
        $youtube = $this->option('youtube') ?: 'https://www.youtube.com/watch?v=ScMzIvxBSi4';

        $supported = array_keys(config('locales.supported', []));
        foreach ($locales as $loc) {
            if (! in_array($loc, $supported, true)) {
                $this->error("Locale '$loc' desteklenmiyor.");
                return self::INVALID;
            }
        }

        $existing = Site::where('domain', $domain)->first();
        if ($existing && ! $this->option('force')) {
            $this->error("Site zaten var. --force ile üzerine yaz.");
            return self::FAILURE;
        }
        if ($existing) {
            $existing->delete();
        }

        $site = Site::create([
            'domain' => $domain,
            'name' => $name,
            'brand' => $brand,
            'city' => $city,
            'country' => strtoupper($country ?: ''),
            'default_locales' => count($locales) === count($supported) ? null : $locales,
            'revalidate_url' => $revalidateUrl,
            'revalidate_secret' => $revalidateSecret,
            'theme' => ['primary_color' => '#0f1e3d', 'accent_color' => '#ff6b35', 'font' => 'Inter'],
        ]);

        $this->info("Site: id={$site->id} {$site->domain}");

        $pages = [
            ['/', 'Anasayfa', $this->homeBlocks($site, $youtube)],
            ['/destinations', 'Destinations', $this->destinationsBlocks($site)],
            ['/products', 'Products', $this->productsBlocks($site)],
            ['/blog', 'Blog', $this->blogBlocks($site)],
        ];

        $created = 0;
        foreach ($locales as $locale) {
            foreach ($pages as [$slug, $title, $blocks]) {
                $page = Page::create([
                    'site_id' => $site->id,
                    'locale' => $locale,
                    'slug' => $slug,
                    'title' => $this->translateTitle($title, $locale),
                    'is_published' => true,
                    'seo' => [
                        'title' => "{$this->translateTitle($title, $locale)} — {$site->name}",
                        'description' => "{$site->name} {$this->translateTitle($title, $locale)}",
                    ],
                ]);
                foreach ($blocks as $i => $block) {
                    Block::create([
                        'page_id' => $page->id,
                        'type' => $block['type'],
                        'order' => $i,
                        'content' => $block['content'],
                    ]);
                }
                $created++;
            }
        }

        $this->info("{$created} sayfa (".count($pages)." × ".count($locales).' locale)');
        $this->info("Revalidate secret: {$revalidateSecret}");
        $this->newLine();
        $this->line('SITE_DOMAIN='.$site->domain);
        $this->line('REVALIDATE_SECRET='.$revalidateSecret);

        return self::SUCCESS;
    }

    private function homeBlocks(Site $site, string $youtubeUrl): array
    {
        return [
            ['type' => 'hero_video', 'content' => [
                'youtube_url' => $youtubeUrl,
                'badge_text' => 'YENİ DÖNEM',
                'headline' => "{$site->city} ile Dünyaya Açıl",
                'subheadline' => "{$site->name} ile akredite eğitim, gerçek deneyim, ömürlük dostluklar.",
                'cta_label' => 'Hemen başvur',
                'cta_href' => '#contact',
                'secondary_cta_label' => 'Lokasyonlar',
                'secondary_cta_href' => '/destinations',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'destinations_grid', 'content' => [
                'title' => 'Lokasyonlarımız',
                'intro' => 'Dünya çapında 40+ şehir.',
                'items' => [
                    ['city' => 'London', 'country' => 'United Kingdom', 'href' => '/destinations/london', 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'badge' => 'Popular'],
                    ['city' => 'New York', 'country' => 'United States', 'href' => '/destinations/new-york', 'image_url' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600'],
                    ['city' => 'Sydney', 'country' => 'Australia', 'href' => '/destinations/sydney', 'image_url' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600'],
                    ['city' => 'Toronto', 'country' => 'Canada', 'href' => '/destinations/toronto', 'image_url' => 'https://images.unsplash.com/photo-1517090504586-fde19ea6066f?w=600'],
                    ['city' => 'Cape Town', 'country' => 'South Africa', 'href' => '/destinations/cape-town', 'image_url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600'],
                    ['city' => 'Dublin', 'country' => 'Ireland', 'href' => '/destinations/dublin', 'image_url' => 'https://images.unsplash.com/photo-1549918864-48ac978761a4?w=600'],
                ],
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Öne Çıkan Programlar',
                'items' => [
                    ['name' => 'General English', 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'href' => '/products/general-english', 'image_url' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=600'],
                    ['name' => 'IELTS Preparation', 'level' => 'b2', 'duration' => '20+ ders/hafta', 'price_from' => '£345/hafta', 'href' => '/products/ielts', 'image_url' => 'https://images.unsplash.com/photo-1456406644174-8ddd4cd52a06?w=600'],
                    ['name' => 'Business English', 'level' => 'b1', 'duration' => '15 ders/hafta', 'price_from' => '£325/hafta', 'href' => '/products/business-english', 'image_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600'],
                ],
            ]],
            ['type' => 'testimonials', 'content' => [
                'title' => 'Öğrencilerimiz Anlatıyor',
                'items' => [
                    ['quote' => "B1'den C1'e 6 ayda çıktım.", 'author' => 'Elena R.', 'author_title' => 'İspanya', 'rating' => 5],
                    ['quote' => 'Şehir muhteşem, eğitim profesyonel.', 'author' => 'Yuki M.', 'author_title' => 'Japonya', 'rating' => 5],
                    ['quote' => 'Her şey çok düşünülmüş.', 'author' => 'Ahmet K.', 'author_title' => 'Türkiye', 'rating' => 5],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hayalini gerçekleştirmenin tam zamanı',
                'cta_label' => 'Ücretsiz danışmanlık',
                'cta_href' => '#contact',
                'background_color' => '#0f1e3d', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'İletişim',
                'intro' => 'Bilgi al — 24 saat içinde dönelim.',
                'form_type' => 'contact',
                'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true,
                'success_message' => 'Teşekkürler! 24 saat içinde dönüş yapacağız.',
                'cta_label' => 'Gönder',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function destinationsBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Lokasyonlarımız',
                'subheadline' => '40+ şehirde okul ağımız var.',
                'background_image' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'destinations_grid', 'content' => [
                'title' => 'Tüm Şehirler',
                'items' => [
                    ['city' => 'London', 'country' => 'United Kingdom', 'href' => '/destinations/london', 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'description' => 'Tarihi merkez.'],
                    ['city' => 'New York', 'country' => 'United States', 'href' => '/destinations/new-york', 'image_url' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600', 'description' => 'Manhattan kalbinde.'],
                    ['city' => 'Sydney', 'country' => 'Australia', 'href' => '/destinations/sydney', 'image_url' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600', 'description' => 'Plajdan derse.'],
                    ['city' => 'Toronto', 'country' => 'Canada', 'href' => '/destinations/toronto', 'image_url' => 'https://images.unsplash.com/photo-1517090504586-fde19ea6066f?w=600', 'description' => 'Çok kültürlü.'],
                    ['city' => 'Cape Town', 'country' => 'South Africa', 'href' => '/destinations/cape-town', 'image_url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600', 'description' => 'Doğa harikası.'],
                    ['city' => 'Dublin', 'country' => 'Ireland', 'href' => '/destinations/dublin', 'image_url' => 'https://images.unsplash.com/photo-1549918864-48ac978761a4?w=600', 'description' => 'Genç Avrupa.'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hangi şehir sana uygun?',
                'cta_label' => 'Danışmanlık al',
                'cta_href' => '#contact',
                'background_color' => '#ff6b35', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'Şehir seçiminde yardım',
                'form_type' => 'contact',
                'show_phone' => true, 'show_message' => true, 'show_country' => true,
                'cta_label' => 'Bilgi al',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function productsBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Programlarımız',
                'subheadline' => "A1'den C2'ye kapsamlı kurs portföyü.",
                'background_image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Genel İngilizce',
                'items' => [
                    ['name' => 'General English (Standard)', 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'description' => 'Günlük iletişim.', 'href' => '/products/general-english'],
                    ['name' => 'General English (Intensive)', 'level' => 'mixed', 'duration' => '30 ders/hafta', 'price_from' => '£415/hafta', 'description' => 'Hızlı ilerleme.', 'href' => '/products/general-english-intensive'],
                    ['name' => 'Mini Group Premium', 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£695/hafta', 'description' => 'Maks 6 kişi.', 'href' => '/products/mini-group'],
                ],
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Sınav Hazırlık',
                'items' => [
                    ['name' => 'IELTS Preparation', 'level' => 'b2', 'duration' => '20-30 ders/hafta', 'price_from' => '£345/hafta', 'description' => 'Academic & General.', 'href' => '/products/ielts'],
                    ['name' => 'Cambridge Exam Prep', 'level' => 'c1', 'duration' => '30 ders/hafta', 'price_from' => '£425/hafta', 'description' => 'FCE, CAE, CPE.', 'href' => '/products/cambridge'],
                    ['name' => 'TOEFL', 'level' => 'b2', 'duration' => '20 ders/hafta', 'price_from' => '£365/hafta', 'description' => 'ABD üniversiteleri.', 'href' => '/products/toefl'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hangi program sana uygun?',
                'cta_label' => 'Seviye testi yap',
                'cta_href' => '#contact',
                'background_color' => '#ff6b35', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'Bilgi al',
                'form_type' => 'price_quote',
                'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true,
                'cta_label' => 'Teklif iste',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function blogBlocks(Site $site): array
    {
        $citySlug = strtolower($site->city ?: 'city');
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Blog',
                'subheadline' => 'Şehir rehberleri, öğrenci hayatı, dil ipuçları.',
                'background_image' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'article_list', 'content' => [
                'title' => 'Son Yazılar',
                'items' => [
                    ['title' => "{$site->city} Görülmesi Gereken 10 Yer", 'category' => 'Şehir Rehberi', 'excerpt' => 'Tarih + doğa.', 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'date' => now()->subDays(3)->format('Y-m-d'), 'href' => "/blog/top-10-{$citySlug}"],
                    ['title' => "IELTS'te 7+ İçin 5 İpucu", 'category' => 'Sınav', 'excerpt' => 'Speaking + writing.', 'image_url' => 'https://images.unsplash.com/photo-1456406644174-8ddd4cd52a06?w=600', 'date' => now()->subDays(8)->format('Y-m-d'), 'href' => '/blog/ielts-7-tips'],
                    ['title' => 'Öğrenci Bütçesi', 'category' => 'Yaşam', 'excerpt' => 'Pratik öneriler.', 'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600', 'date' => now()->subDays(15)->format('Y-m-d'), 'href' => '/blog/student-budget'],
                    ['title' => 'Vize Süreci', 'category' => 'Vize', 'excerpt' => 'Adım adım.', 'image_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600', 'date' => now()->subDays(22)->format('Y-m-d'), 'href' => '/blog/visa-guide'],
                    ['title' => "Dil Öğrenirken 7 Hata", 'category' => 'Dil', 'excerpt' => 'Kaçın.', 'image_url' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=600', 'date' => now()->subDays(28)->format('Y-m-d'), 'href' => '/blog/language-mistakes'],
                    ['title' => 'Konaklama Karşılaştırması', 'category' => 'Yaşam', 'excerpt' => 'Aile vs yurt.', 'image_url' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600', 'date' => now()->subDays(35)->format('Y-m-d'), 'href' => '/blog/accommodation-comparison'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hayalindeki şehirde başla',
                'cta_label' => 'Başvur',
                'cta_href' => '#contact',
                'background_color' => '#0f1e3d', 'text_color' => '#ffffff',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function footerContent(Site $site): array
    {
        return [
            'tagline' => $site->name,
            'columns' => [
                ['title' => 'Programlar', 'links' => [['label' => 'Genel İngilizce', 'href' => '/products'], ['label' => 'Sınav Hazırlık', 'href' => '/products']]],
                ['title' => 'Lokasyonlar', 'links' => [['label' => 'Tüm Şehirler', 'href' => '/destinations']]],
                ['title' => 'Kaynaklar', 'links' => [['label' => 'Blog', 'href' => '/blog'], ['label' => 'İletişim', 'href' => '#contact']]],
            ],
            'social_links' => [
                ['platform' => 'instagram', 'href' => 'https://instagram.com/'],
                ['platform' => 'facebook', 'href' => 'https://facebook.com/'],
                ['platform' => 'youtube', 'href' => 'https://youtube.com/'],
            ],
            'copyright_text' => '© '.date('Y').' '.$site->name,
            'background_color' => '#0f1e3d',
            'text_color' => '#cbd5e1',
        ];
    }

    private function translateTitle(string $text, string $locale): string
    {
        if ($locale === 'tr') return $text;
        return ['Anasayfa' => 'Home', 'Destinations' => 'Destinations', 'Products' => 'Products', 'Blog' => 'Blog'][$text] ?? $text;
    }
}
