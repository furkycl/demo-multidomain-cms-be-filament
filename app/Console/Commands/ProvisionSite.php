<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;

/**
 * Yeni site provision: 4 sayfa (Home/Destinations/Products/Blog) + iskelet bloklar
 * + her sayfada nötr placeholder içerik. Admin Filament'te içerikleri günceller.
 *
 * Brand-neutral: tüm metinler "Your School", "Destination 1" gibi placeholder.
 * Locale-aware: TR ve EN için ayrı doğal dil placeholder'ları.
 *
 * Kullanım:
 *   php artisan multi-cms:provision-site --domain=site-a.local --name="Site A" \
 *       --brand=kaplan --city=Istanbul --country=TR --locales=tr,en --force
 */
class ProvisionSite extends Command
{
    protected $signature = 'multi-cms:provision-site
        {--domain=}{--name=}{--brand=}{--city=}{--country=}
        {--locales=tr,en}
        {--revalidate-url=}{--revalidate-secret=}
        {--force}';

    protected $description = 'Brand-neutral, locale-aware placeholder içerikli 4 sayfalı site provision et';

    public function handle(): int
    {
        $domain = $this->option('domain') ?: $this->ask('Domain');
        $name = $this->option('name') ?: $this->ask('Site adı', $domain);
        $brand = $this->option('brand') ?: $this->choice('Brand', Site::BRANDS, 0);
        $city = $this->option('city') ?: $this->ask('Şehir (placeholder, sonra değişebilir)', null);
        $country = $this->option('country') ?: $this->ask('Ülke ISO-2', null);
        $locales = array_filter(array_map('trim', explode(',', (string) $this->option('locales'))));
        $revalidateUrl = $this->option('revalidate-url');
        $revalidateSecret = $this->option('revalidate-secret') ?: bin2hex(random_bytes(32));

        $supported = array_keys(config('locales.supported', []));
        foreach ($locales as $loc) if (! in_array($loc, $supported, true)) { $this->error("Locale '$loc' yok"); return self::INVALID; }

        $existing = Site::where('domain', $domain)->first();
        if ($existing && ! $this->option('force')) { $this->error("Site var. --force ile yaz."); return self::FAILURE; }
        if ($existing) $existing->delete();

        $site = Site::create([
            'domain' => $domain, 'name' => $name, 'brand' => $brand, 'city' => $city,
            'country' => strtoupper($country ?: ''),
            'default_locales' => count($locales) === count($supported) ? null : $locales,
            'revalidate_url' => $revalidateUrl, 'revalidate_secret' => $revalidateSecret,
            'theme' => ['primary_color' => '#0F1A3C', 'accent_color' => '#E31837', 'font' => 'Inter'],
        ]);

        $this->info("✓ Site: id={$site->id} {$site->domain}");

        $created = 0;
        foreach ($locales as $locale) {
            $T = $this->translations($locale);
            $header = $this->headerBlock($T);
            $footer = $this->footerBlock($T, $site);

            $pages = [
                ['/', $T['nav_home'], $this->homeBlocks($T, $header, $footer), true],
                ['/destinations', $T['nav_destinations'], $this->destinationsBlocks($T, $header, $footer), true],
                ['/products', $T['nav_products'], $this->productsBlocks($T, $header, $footer), true],
                ['/blog', $T['nav_blog'], $this->blogBlocks($T, $header, $footer), false],
            ];

            foreach ($pages as [$slug, $title, $blocks, $published]) {
                $page = Page::create([
                    'site_id' => $site->id, 'locale' => $locale, 'slug' => $slug,
                    'title' => $title, 'is_published' => $published,
                    'seo' => ['title' => "{$title} — {$site->name}", 'description' => $T['seo_description']],
                ]);
                foreach ($blocks as $i => $block) {
                    Block::create(['page_id' => $page->id, 'type' => $block['type'], 'order' => $i, 'content' => $block['content']]);
                }
                $created++;
            }
        }

        $this->info("✓ {$created} sayfa (".count($locales)." locale × 4 sayfa)");
        $this->info("  Blog default kapalı (Filament'te yayına alınabilir)");
        $this->info("✓ Revalidate secret: {$revalidateSecret}");
        $this->newLine();
        $this->line("Frontend env:");
        $this->line("  SITE_DOMAIN={$site->domain}");
        $this->line("  REVALIDATE_SECRET={$revalidateSecret}");
        $this->newLine();
        $this->line("Admin'in yapacakları:");
        $this->line("  1. Header bloğunda logo URL'i gir");
        $this->line("  2. Hero Video bloğunda YouTube URL'i + headline + stats gir");
        $this->line("  3. Destinations Grid'te 6 placeholder kartı kendi şehirlerinle değiştir");
        $this->line("  4. Diğer locale'ler için de aynı içerikleri çevir");

        return self::SUCCESS;
    }

    /** Locale'e göre placeholder string'leri */
    private function translations(string $locale): array
    {
        $tr = [
            'nav_home' => 'Anasayfa', 'nav_destinations' => 'Destinasyonlar',
            'nav_products' => 'Programlar', 'nav_blog' => 'Blog',
            'nav_contact' => 'İletişim', 'free_consultation' => 'Ücretsiz Danışmanlık',
            'operated_by' => 'Operated by',
            'hero_badge' => '30+ destinasyon',
            'hero_headline' => 'Yaşa, oku, başar',
            'hero_subheadline' => 'Dünyanın en sevilen şehirlerinde dil eğitimi. Buraya kendi başlığınızı yazın.',
            'hero_cta1' => 'Destinasyonları keşfet', 'hero_cta2' => 'Ücretsiz danışmanlık',
            'stat1_value' => '80+', 'stat1_label' => 'Yıllık deneyim',
            'stat2_value' => '30+', 'stat2_label' => 'Destinasyon',
            'stat3_value' => '150+', 'stat3_label' => 'Milliyet',
            'stat4_value' => '50K+', 'stat4_label' => 'Mezun öğrenci',
            'destinations_title' => 'Destinasyonlarımız',
            'destinations_intro' => 'Dünya genelinde 30+ şehir. Sana en uyanı seç.',
            'dest1_city' => '1. Destinasyon', 'dest1_country' => 'Ülke', 'dest1_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'dest2_city' => '2. Destinasyon', 'dest2_country' => 'Ülke', 'dest2_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'dest3_city' => '3. Destinasyon', 'dest3_country' => 'Ülke', 'dest3_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'dest4_city' => '4. Destinasyon', 'dest4_country' => 'Ülke', 'dest4_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'dest5_city' => '5. Destinasyon', 'dest5_country' => 'Ülke', 'dest5_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'dest6_city' => '6. Destinasyon', 'dest6_country' => 'Ülke', 'dest6_desc' => 'Şehir hakkında kısa açıklama buraya gelir.',
            'badge_popular' => 'Popüler',
            'about_title' => 'Bizi Neden Tercih Etmelisiniz', 'about_description' => 'Kurumumuz hakkında kısa bir açıklama buraya gelir. Bu metni admin panelinden değiştirebilirsiniz.',
            'about_feature1_title' => 'Akredite Okullar', 'about_feature1_desc' => 'Tüm okullarımız uluslararası akreditasyona sahip.',
            'about_feature2_title' => 'Uzman Eğitmenler', 'about_feature2_desc' => 'Deneyimli ve sertifikalı dil eğitmenleri.',
            'about_feature3_title' => 'Küresel Ağ', 'about_feature3_desc' => 'Dünya çapında geniş okul ağı.',
            'about_feature4_title' => 'Kanıtlanmış Yöntem', 'about_feature4_desc' => '50+ yıllık dil eğitimi tecrübesi.',
            'products_title' => 'Programlarımız', 'products_intro' => 'Her seviyeye uygun program seçenekleri.',
            'prod1_name' => '1. Program', 'prod1_desc' => 'Programın kısa açıklaması buraya gelir.',
            'prod2_name' => '2. Program', 'prod2_desc' => 'Programın kısa açıklaması buraya gelir.',
            'prod3_name' => '3. Program', 'prod3_desc' => 'Programın kısa açıklaması buraya gelir.',
            'testimonials_title' => 'Öğrenci Yorumları',
            't1_quote' => 'Buradaki deneyimim hayatımı değiştirdi.', 't1_author' => 'Öğrenci 1', 't1_title' => 'Ülke',
            't2_quote' => 'Hocalarımız harika, şehir muhteşem.', 't2_author' => 'Öğrenci 2', 't2_title' => 'Ülke',
            't3_quote' => 'Konaklamadan derslere her şey çok düşünülmüş.', 't3_author' => 'Öğrenci 3', 't3_title' => 'Ülke',
            'cta_banner_headline' => 'Hayalini gerçekleştirmenin tam zamanı',
            'cta_banner_text' => 'Ücretsiz danışmanlık için formu doldur, 24 saat içinde dönüş yapalım.',
            'cta_apply' => 'Hemen başvur',
            'contact_title' => 'İletişim', 'contact_intro' => 'Bilgi al — 24 saat içinde dönelim.',
            'contact_success' => 'Teşekkürler! En kısa sürede dönüş yapacağız.',
            'contact_submit' => 'Gönder', 'contact_quote' => 'Teklif iste',
            'destinations_hero_title' => 'Destinasyonlar', 'destinations_hero_sub' => 'Dünya genelinde okul ağımız.',
            'products_hero_title' => 'Programlar', 'products_hero_sub' => 'Her hedef için doğru program.',
            'blog_hero_title' => 'Blog', 'blog_hero_sub' => 'Şehir rehberleri, öğrenci hayatı, dil ipuçları.',
            'footer_company' => 'Kurum', 'footer_programs' => 'Programlar', 'footer_resources' => 'Kaynaklar',
            'seo_description' => 'Dil eğitimi için doğru adres.',
        ];

        $en = [
            'nav_home' => 'Home', 'nav_destinations' => 'Destinations',
            'nav_products' => 'Products', 'nav_blog' => 'Blog',
            'nav_contact' => 'Contact', 'free_consultation' => 'Free consultation',
            'operated_by' => 'Operated by',
            'hero_badge' => '30+ destinations',
            'hero_headline' => 'Live, study, thrive',
            'hero_subheadline' => 'Language education in the world\'s favorite cities. Replace this with your headline.',
            'hero_cta1' => 'Explore destinations', 'hero_cta2' => 'Free consultation',
            'stat1_value' => '80+', 'stat1_label' => 'Years of experience',
            'stat2_value' => '30+', 'stat2_label' => 'Destinations',
            'stat3_value' => '150+', 'stat3_label' => 'Nationalities',
            'stat4_value' => '50K+', 'stat4_label' => 'Alumni',
            'destinations_title' => 'Our Destinations',
            'destinations_intro' => '30+ cities worldwide. Choose the one that fits you best.',
            'dest1_city' => 'Destination 1', 'dest1_country' => 'Country', 'dest1_desc' => 'Brief description of this destination goes here.',
            'dest2_city' => 'Destination 2', 'dest2_country' => 'Country', 'dest2_desc' => 'Brief description of this destination goes here.',
            'dest3_city' => 'Destination 3', 'dest3_country' => 'Country', 'dest3_desc' => 'Brief description of this destination goes here.',
            'dest4_city' => 'Destination 4', 'dest4_country' => 'Country', 'dest4_desc' => 'Brief description of this destination goes here.',
            'dest5_city' => 'Destination 5', 'dest5_country' => 'Country', 'dest5_desc' => 'Brief description of this destination goes here.',
            'dest6_city' => 'Destination 6', 'dest6_country' => 'Country', 'dest6_desc' => 'Brief description of this destination goes here.',
            'badge_popular' => 'Popular',
            'about_title' => 'Why Choose Us', 'about_description' => 'Brief description about your organization. Replace this from the admin panel.',
            'about_feature1_title' => 'Accredited Schools', 'about_feature1_desc' => 'All schools internationally accredited.',
            'about_feature2_title' => 'Expert Teachers', 'about_feature2_desc' => 'Experienced and certified language teachers.',
            'about_feature3_title' => 'Global Network', 'about_feature3_desc' => 'Wide network of schools worldwide.',
            'about_feature4_title' => 'Proven Method', 'about_feature4_desc' => '50+ years of language education.',
            'products_title' => 'Our Programs', 'products_intro' => 'Programs for every level.',
            'prod1_name' => 'Program 1', 'prod1_desc' => 'Brief program description goes here.',
            'prod2_name' => 'Program 2', 'prod2_desc' => 'Brief program description goes here.',
            'prod3_name' => 'Program 3', 'prod3_desc' => 'Brief program description goes here.',
            'testimonials_title' => 'Student Stories',
            't1_quote' => 'The experience here changed my life.', 't1_author' => 'Student 1', 't1_title' => 'Country',
            't2_quote' => 'Amazing teachers, wonderful city.', 't2_author' => 'Student 2', 't2_title' => 'Country',
            't3_quote' => 'Everything from accommodation to classes is well thought out.', 't3_author' => 'Student 3', 't3_title' => 'Country',
            'cta_banner_headline' => 'Time to make your dream a reality',
            'cta_banner_text' => 'Get a free consultation. We respond within 24 hours.',
            'cta_apply' => 'Apply now',
            'contact_title' => 'Contact', 'contact_intro' => 'Get info — we will respond within 24 hours.',
            'contact_success' => 'Thank you! We will get back to you shortly.',
            'contact_submit' => 'Submit', 'contact_quote' => 'Request quote',
            'destinations_hero_title' => 'Destinations', 'destinations_hero_sub' => 'Our school network worldwide.',
            'products_hero_title' => 'Programs', 'products_hero_sub' => 'The right program for every goal.',
            'blog_hero_title' => 'Blog', 'blog_hero_sub' => 'City guides, student life, language tips.',
            'footer_company' => 'Company', 'footer_programs' => 'Programs', 'footer_resources' => 'Resources',
            'seo_description' => 'Your destination for language education.',
        ];

        return $locale === 'tr' ? $tr : $en;
    }

    private function headerBlock(array $T): array
    {
        return [
            'logo_url' => '',
            'operated_by_text' => $T['operated_by'],
            'operated_by_logo' => '',
            'operated_by_href' => '',
            'cta_label' => $T['free_consultation'],
            'cta_href' => '#contact',
            'links' => [
                ['label' => $T['nav_home'], 'href' => '/'],
                ['label' => $T['nav_destinations'], 'href' => '/destinations'],
                ['label' => $T['nav_products'], 'href' => '/products'],
            ],
        ];
    }

    private function footerBlock(array $T, Site $site): array
    {
        return [
            'logo_url' => '',
            'tagline' => $site->name,
            'columns' => [
                ['title' => $T['footer_company'], 'links' => [
                    ['label' => $T['nav_home'], 'href' => '/'],
                    ['label' => $T['nav_destinations'], 'href' => '/destinations'],
                ]],
                ['title' => $T['footer_programs'], 'links' => [
                    ['label' => $T['nav_products'], 'href' => '/products'],
                ]],
                ['title' => $T['footer_resources'], 'links' => [
                    ['label' => $T['nav_contact'], 'href' => '#contact'],
                ]],
            ],
            'social_links' => [
                ['platform' => 'instagram', 'href' => 'https://instagram.com/'],
                ['platform' => 'facebook', 'href' => 'https://facebook.com/'],
                ['platform' => 'youtube', 'href' => 'https://youtube.com/'],
                ['platform' => 'linkedin', 'href' => 'https://linkedin.com/'],
            ],
            'copyright_text' => '© '.date('Y').' '.$site->name,
            'background_color' => '#0F1A3C', 'text_color' => '#cbd5e1',
        ];
    }

    private function homeBlocks(array $T, array $header, array $footer): array
    {
        return [
            ['type' => 'header', 'content' => $header],
            ['type' => 'hero_video', 'content' => [
                'youtube_url' => '',
                'badge_text' => $T['hero_badge'],
                'headline' => $T['hero_headline'],
                'subheadline' => $T['hero_subheadline'],
                'cta_label' => $T['hero_cta1'], 'cta_href' => '#locations',
                'secondary_cta_label' => $T['hero_cta2'], 'secondary_cta_href' => '#contact',
                'stats' => [
                    ['value' => $T['stat1_value'], 'label' => $T['stat1_label']],
                    ['value' => $T['stat2_value'], 'label' => $T['stat2_label']],
                    ['value' => $T['stat3_value'], 'label' => $T['stat3_label']],
                    ['value' => $T['stat4_value'], 'label' => $T['stat4_label']],
                ],
            ]],
            ['type' => 'destinations_grid', 'content' => [
                'title' => $T['destinations_title'],
                'intro' => $T['destinations_intro'],
                'items' => [
                    ['city' => $T['dest1_city'], 'country' => $T['dest1_country'], 'description' => $T['dest1_desc'], 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'href' => '/destinations/1', 'badge' => $T['badge_popular']],
                    ['city' => $T['dest2_city'], 'country' => $T['dest2_country'], 'description' => $T['dest2_desc'], 'image_url' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600', 'href' => '/destinations/2'],
                    ['city' => $T['dest3_city'], 'country' => $T['dest3_country'], 'description' => $T['dest3_desc'], 'image_url' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600', 'href' => '/destinations/3'],
                    ['city' => $T['dest4_city'], 'country' => $T['dest4_country'], 'description' => $T['dest4_desc'], 'image_url' => 'https://images.unsplash.com/photo-1517090504586-fde19ea6066f?w=600', 'href' => '/destinations/4'],
                    ['city' => $T['dest5_city'], 'country' => $T['dest5_country'], 'description' => $T['dest5_desc'], 'image_url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600', 'href' => '/destinations/5'],
                    ['city' => $T['dest6_city'], 'country' => $T['dest6_country'], 'description' => $T['dest6_desc'], 'image_url' => 'https://images.unsplash.com/photo-1549918864-48ac978761a4?w=600', 'href' => '/destinations/6'],
                ],
            ]],
            ['type' => 'about', 'content' => [
                'title' => $T['about_title'],
                'description' => $T['about_description'],
                'image_url' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=700&h=500&fit=crop',
                'badge_value' => $T['stat1_value'],
                'badge_label' => $T['stat1_label'],
                'features' => [
                    ['icon' => '🏆', 'title' => $T['about_feature1_title'], 'description' => $T['about_feature1_desc']],
                    ['icon' => '👥', 'title' => $T['about_feature2_title'], 'description' => $T['about_feature2_desc']],
                    ['icon' => '🌍', 'title' => $T['about_feature3_title'], 'description' => $T['about_feature3_desc']],
                    ['icon' => '📚', 'title' => $T['about_feature4_title'], 'description' => $T['about_feature4_desc']],
                ],
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => $T['products_title'],
                'intro' => $T['products_intro'],
                'items' => [
                    ['name' => $T['prod1_name'], 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'description' => $T['prod1_desc'], 'href' => '/products/1', 'image_url' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=600'],
                    ['name' => $T['prod2_name'], 'level' => 'b2', 'duration' => '20+ ders/hafta', 'price_from' => '£345/hafta', 'description' => $T['prod2_desc'], 'href' => '/products/2', 'image_url' => 'https://images.unsplash.com/photo-1456406644174-8ddd4cd52a06?w=600'],
                    ['name' => $T['prod3_name'], 'level' => 'b1', 'duration' => '15 ders/hafta', 'price_from' => '£325/hafta', 'description' => $T['prod3_desc'], 'href' => '/products/3', 'image_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600'],
                ],
            ]],
            ['type' => 'testimonials', 'content' => [
                'title' => $T['testimonials_title'],
                'items' => [
                    ['quote' => $T['t1_quote'], 'author' => $T['t1_author'], 'author_title' => $T['t1_title'], 'rating' => 5],
                    ['quote' => $T['t2_quote'], 'author' => $T['t2_author'], 'author_title' => $T['t2_title'], 'rating' => 5],
                    ['quote' => $T['t3_quote'], 'author' => $T['t3_author'], 'author_title' => $T['t3_title'], 'rating' => 5],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => $T['cta_banner_headline'],
                'text' => $T['cta_banner_text'],
                'cta_label' => $T['cta_apply'], 'cta_href' => '#contact',
                'background_color' => '#0F1A3C', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => $T['contact_title'],
                'intro' => $T['contact_intro'],
                'form_type' => 'contact',
                'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true,
                'success_message' => $T['contact_success'],
                'cta_label' => $T['contact_submit'],
            ]],
            ['type' => 'footer_mega', 'content' => $footer],
        ];
    }

    private function destinationsBlocks(array $T, array $header, array $footer): array
    {
        return [
            ['type' => 'header', 'content' => $header],
            ['type' => 'hero_school', 'content' => [
                'headline' => $T['destinations_hero_title'],
                'subheadline' => $T['destinations_hero_sub'],
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'destinations_grid', 'content' => [
                'title' => $T['destinations_title'],
                'items' => [
                    ['city' => $T['dest1_city'], 'country' => $T['dest1_country'], 'description' => $T['dest1_desc'], 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'href' => '/destinations/1'],
                    ['city' => $T['dest2_city'], 'country' => $T['dest2_country'], 'description' => $T['dest2_desc'], 'image_url' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600', 'href' => '/destinations/2'],
                    ['city' => $T['dest3_city'], 'country' => $T['dest3_country'], 'description' => $T['dest3_desc'], 'image_url' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600', 'href' => '/destinations/3'],
                    ['city' => $T['dest4_city'], 'country' => $T['dest4_country'], 'description' => $T['dest4_desc'], 'image_url' => 'https://images.unsplash.com/photo-1517090504586-fde19ea6066f?w=600', 'href' => '/destinations/4'],
                    ['city' => $T['dest5_city'], 'country' => $T['dest5_country'], 'description' => $T['dest5_desc'], 'image_url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600', 'href' => '/destinations/5'],
                    ['city' => $T['dest6_city'], 'country' => $T['dest6_country'], 'description' => $T['dest6_desc'], 'image_url' => 'https://images.unsplash.com/photo-1549918864-48ac978761a4?w=600', 'href' => '/destinations/6'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => $T['cta_banner_headline'],
                'cta_label' => $T['cta_apply'], 'cta_href' => '#contact',
                'background_color' => '#E31837', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => $T['contact_title'],
                'form_type' => 'contact',
                'show_phone' => true, 'show_message' => true, 'show_country' => true,
                'cta_label' => $T['contact_submit'],
            ]],
            ['type' => 'footer_mega', 'content' => $footer],
        ];
    }

    private function productsBlocks(array $T, array $header, array $footer): array
    {
        return [
            ['type' => 'header', 'content' => $header],
            ['type' => 'hero_school', 'content' => [
                'headline' => $T['products_hero_title'],
                'subheadline' => $T['products_hero_sub'],
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => $T['products_title'],
                'items' => [
                    ['name' => $T['prod1_name'], 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'description' => $T['prod1_desc'], 'href' => '/products/1'],
                    ['name' => $T['prod2_name'], 'level' => 'b2', 'duration' => '30 ders/hafta', 'price_from' => '£415/hafta', 'description' => $T['prod2_desc'], 'href' => '/products/2'],
                    ['name' => $T['prod3_name'], 'level' => 'b1', 'duration' => '15 ders/hafta', 'price_from' => '£325/hafta', 'description' => $T['prod3_desc'], 'href' => '/products/3'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => $T['cta_banner_headline'],
                'cta_label' => $T['contact_quote'], 'cta_href' => '#contact',
                'background_color' => '#E31837', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => $T['contact_title'],
                'form_type' => 'price_quote',
                'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true,
                'cta_label' => $T['contact_quote'],
            ]],
            ['type' => 'footer_mega', 'content' => $footer],
        ];
    }

    private function blogBlocks(array $T, array $header, array $footer): array
    {
        return [
            ['type' => 'header', 'content' => $header],
            ['type' => 'hero_school', 'content' => [
                'headline' => $T['blog_hero_title'],
                'subheadline' => $T['blog_hero_sub'],
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'article_list', 'content' => ['title' => '', 'items' => []]],
            ['type' => 'footer_mega', 'content' => $footer],
        ];
    }
}
