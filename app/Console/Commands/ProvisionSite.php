<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;

class ProvisionSite extends Command
{
    protected $signature = 'multi-cms:provision-site
        {--domain=}{--name=}{--brand=}{--city=}{--country=}
        {--locales=tr,en}
        {--revalidate-url=}{--revalidate-secret=}
        {--force}{--demo}';

    protected $description = 'Boş Kaplan TV iskelet site oluştur (4 sayfa × locale, blog default kapalı)';

    public function handle(): int
    {
        $domain = $this->option('domain') ?: $this->ask('Domain');
        $name = $this->option('name') ?: $this->ask('Ad', $domain);
        $brand = $this->option('brand') ?: $this->choice('Brand', Site::BRANDS, 0);
        $city = $this->option('city') ?: $this->ask('Şehir', null);
        $country = $this->option('country') ?: $this->ask('Ülke', null);
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

        $footerContent = $this->footer();
        $headerContent = $this->header();

        $pages = [
            ['/', 'Home', $this->homeBlocks($headerContent, $footerContent), true],
            ['/destinations', 'Destinations', $this->destinationsBlocks($headerContent, $footerContent), true],
            ['/products', 'Products', $this->productsBlocks($headerContent, $footerContent), true],
            ['/blog', 'Blog', $this->blogBlocks($headerContent, $footerContent), false], // blog default kapalı
        ];

        $created = 0;
        foreach ($locales as $locale) {
            foreach ($pages as [$slug, $title, $blocks, $published]) {
                $page = Page::create([
                    'site_id' => $site->id, 'locale' => $locale, 'slug' => $slug,
                    'title' => $title, 'is_published' => $published,
                    'seo' => ['title' => "{$title} — {$site->name}"],
                ]);
                foreach ($blocks as $i => $block) {
                    Block::create(['page_id' => $page->id, 'type' => $block['type'], 'order' => $i, 'content' => $block['content']]);
                }
                $created++;
            }
        }

        $this->info("✓ {$created} sayfa (".count($pages)." × ".count($locales)." locale)");
        $this->info("  Blog yayın dışı. Admin Filament > Pages > /blog'u açar.");
        $this->info("✓ Revalidate secret: {$revalidateSecret}");
        $this->newLine();
        $this->line('SITE_DOMAIN='.$site->domain);
        $this->line('REVALIDATE_SECRET='.$revalidateSecret);

        return self::SUCCESS;
    }

    private function header(): array
    {
        return [
            'logo_url' => '',
            'operated_by_text' => 'Operated by',
            'operated_by_logo' => '',
            'operated_by_href' => '',
            'cta_label' => 'Free consultation',
            'cta_href' => '#contact',
            'links' => [
                ['label' => 'Home', 'href' => '/'],
                ['label' => 'Destinations', 'href' => '/destinations'],
                ['label' => 'Products', 'href' => '/products'],
            ],
        ];
    }

    private function footer(): array
    {
        return [
            'logo_url' => '', 'tagline' => '',
            'columns' => [
                ['title' => 'Company', 'links' => []],
                ['title' => 'Programs', 'links' => []],
                ['title' => 'Resources', 'links' => []],
            ],
            'social_links' => [],
            'copyright_text' => '',
            'background_color' => '#0F1A3C', 'text_color' => '#cbd5e1',
        ];
    }

    private function homeBlocks(array $h, array $f): array
    {
        return [
            ['type' => 'header', 'content' => $h],
            ['type' => 'hero_video', 'content' => [
                'youtube_url' => '',
                'badge_text' => '',
                'headline' => 'Your headline here',
                'subheadline' => 'Add a subtitle from the admin panel.',
                'cta_label' => 'Explore destinations', 'cta_href' => '#locations',
                'secondary_cta_label' => 'Free consultation', 'secondary_cta_href' => '#contact',
                'stats' => [],
            ]],
            ['type' => 'destinations_grid', 'content' => ['title' => 'Our Destinations', 'intro' => '', 'items' => []]],
            ['type' => 'course_grid', 'content' => ['title' => 'Our Programs', 'intro' => '', 'items' => []]],
            ['type' => 'testimonials', 'content' => ['title' => 'Student Stories', 'items' => []]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => '', 'text' => '',
                'cta_label' => 'Apply now', 'cta_href' => '#contact',
                'background_color' => '#0F1A3C', 'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'Get in touch', 'intro' => '',
                'form_type' => 'contact',
                'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true,
                'success_message' => 'Thank you! We will get back to you within 24 hours.',
                'cta_label' => 'Submit',
            ]],
            ['type' => 'footer_mega', 'content' => $f],
        ];
    }

    private function destinationsBlocks(array $h, array $f): array
    {
        return [
            ['type' => 'header', 'content' => $h],
            ['type' => 'hero_school', 'content' => ['headline' => 'Destinations', 'overlay_color' => 'rgba(15,30,61,0.55)']],
            ['type' => 'destinations_grid', 'content' => ['title' => '', 'items' => []]],
            ['type' => 'cta_banner', 'content' => ['headline' => 'Need help choosing?', 'cta_label' => 'Get advice', 'cta_href' => '#contact', 'background_color' => '#E31837', 'text_color' => '#ffffff']],
            ['type' => 'contact_form', 'content' => ['title' => 'Get in touch', 'form_type' => 'contact', 'show_phone' => true, 'show_message' => true, 'show_country' => true, 'cta_label' => 'Submit']],
            ['type' => 'footer_mega', 'content' => $f],
        ];
    }

    private function productsBlocks(array $h, array $f): array
    {
        return [
            ['type' => 'header', 'content' => $h],
            ['type' => 'hero_school', 'content' => ['headline' => 'Programs', 'overlay_color' => 'rgba(15,30,61,0.55)']],
            ['type' => 'course_grid', 'content' => ['title' => '', 'items' => []]],
            ['type' => 'cta_banner', 'content' => ['headline' => 'Find the right program', 'cta_label' => 'Get a quote', 'cta_href' => '#contact', 'background_color' => '#E31837', 'text_color' => '#ffffff']],
            ['type' => 'contact_form', 'content' => ['title' => 'Get a quote', 'form_type' => 'price_quote', 'show_phone' => true, 'show_message' => true, 'show_country' => true, 'show_course_interest' => true, 'cta_label' => 'Request quote']],
            ['type' => 'footer_mega', 'content' => $f],
        ];
    }

    private function blogBlocks(array $h, array $f): array
    {
        return [
            ['type' => 'header', 'content' => $h],
            ['type' => 'hero_school', 'content' => ['headline' => 'Blog', 'overlay_color' => 'rgba(15,30,61,0.55)']],
            ['type' => 'article_list', 'content' => ['title' => '', 'items' => []]],
            ['type' => 'footer_mega', 'content' => $f],
        ];
    }
}
