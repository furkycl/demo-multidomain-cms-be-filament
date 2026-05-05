<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Yeni bir mikro site (Kaplan/Alpadia/Azurlingua tarzı) için:
 *  - Site kaydı oluşturur
 *  - 5 standart sayfa (anasayfa, courses, accommodation, city-guide, pricing)
 *  - Her sayfayı her locale için açar (default: tr,en)
 *  - Her sayfaya iskelet bloklar koyar (admin daha sonra içeriklerini düzenler)
 *
 * Kullanım:
 *   php artisan multi-cms:provision-site \
 *       --domain=kaplan-london.com \
 *       --name="Kaplan London" \
 *       --brand=kaplan \
 *       --city=London --country=GB \
 *       --locales=tr,en \
 *       --revalidate-url=https://kaplan-london.com/api/revalidate
 *
 * Sonra frontend tarafında:
 *   1. Bir frontend-template clone'u aç (yeni repo)
 *   2. SITE_DOMAIN env'i bu site'ın domain'iyle aynı yap
 *   3. Vercel'e bağla, deploy et
 */
class ProvisionSite extends Command
{
    protected $signature = 'multi-cms:provision-site
        {--domain= : Site\'ın domain\'i (örn kaplan-london.com)}
        {--name= : Görüntülenen ad (örn "Kaplan London")}
        {--brand= : kaplan|alpadia|azurlingua}
        {--city= : Şehir (London, Paris, Nice ...)}
        {--country= : ISO-2 ülke kodu (GB, FR, ES ...)}
        {--locales=tr,en : Açılacak locale\'ler virgülle ayrılmış (default: tr,en)}
        {--revalidate-url= : Frontend\'in /api/revalidate URL\'i (boş bırakılabilir)}
        {--revalidate-secret= : Secret (boş bırakılırsa otomatik üretilir)}
        {--force : Mevcut site varsa yeniden oluştur}';

    protected $description = 'Yeni bir mikro site provision et: Site + 5 standart sayfa × locale\'ler + iskelet bloklar';

    public function handle(): int
    {
        $domain = $this->option('domain') ?: $this->ask('Domain (örn kaplan-london.com)');
        $name = $this->option('name') ?: $this->ask('Görüntülenen ad', $domain);
        $brand = $this->option('brand') ?: $this->choice('Brand', Site::BRANDS, 0);
        $city = $this->option('city') ?: $this->ask('Şehir', null);
        $country = $this->option('country') ?: $this->ask('Ülke (ISO-2: GB/FR/ES)', null);
        $locales = explode(',', $this->option('locales'));
        $locales = array_filter(array_map('trim', $locales));
        $revalidateUrl = $this->option('revalidate-url');
        $revalidateSecret = $this->option('revalidate-secret') ?: bin2hex(random_bytes(32));

        $supported = array_keys(config('locales.supported', []));
        foreach ($locales as $loc) {
            if (! in_array($loc, $supported, true)) {
                $this->error("Locale '$loc' desteklenmiyor. Desteklenen: ".implode(',', $supported));

                return self::INVALID;
            }
        }

        $existing = Site::where('domain', $domain)->first();
        if ($existing && ! $this->option('force')) {
            $this->error("Site zaten var: $domain (id={$existing->id}). --force ile üzerine yaz.");

            return self::FAILURE;
        }

        if ($existing && $this->option('force')) {
            $this->warn("Mevcut site siliniyor (force): $domain");
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
            'theme' => [
                'primary_color' => '#0f1e3d',
                'accent_color' => '#ff6b35',
                'font' => 'Inter',
            ],
        ]);

        $this->info("✓ Site oluşturuldu: id={$site->id}, domain={$site->domain}");

        $standardPages = [
            ['/', 'Ana Sayfa', $this->homeBlocks($site)],
            ['/courses', 'Kurslar', $this->coursesBlocks($site)],
            ['/accommodation', 'Konaklama', $this->accommodationBlocks($site)],
            ['/city-guide', 'Şehir Rehberi', $this->cityGuideBlocks($site)],
            ['/pricing', 'Fiyat & İletişim', $this->pricingBlocks($site)],
        ];

        $created = 0;
        foreach ($locales as $locale) {
            foreach ($standardPages as [$slug, $title, $blocks]) {
                $page = Page::create([
                    'site_id' => $site->id,
                    'locale' => $locale,
                    'slug' => $slug,
                    'title' => $this->translate($title, $locale),
                    'is_published' => true,
                    'seo' => [
                        'title' => "{$this->translate($title, $locale)} — {$site->name}",
                        'description' => "{$site->name} {$this->translate($title, $locale)}",
                    ],
                ]);

                foreach ($blocks as $i => $block) {
                    Block::create([
                        'page_id' => $page->id,
                        'type' => $block['type'],
                        'order' => $i,
                        'content' => $this->localizeContent($block['content'], $locale),
                    ]);
                }

                $created++;
            }
        }

        $this->info("✓ {$created} sayfa oluşturuldu (".count($standardPages).' sayfa × '.count($locales).' locale)');
        $this->info("✓ Revalidate secret: {$revalidateSecret}");
        $this->newLine();
        $this->line('  Frontend tarafında yapacakların:');
        $this->line("    1. frontend-template'i clone'la, yeni repo aç");
        $this->line('    2. .env.local: NEXT_PUBLIC_API_URL='.config('app.url'));
        $this->line('                   SITE_DOMAIN='.$site->domain);
        $this->line('                   REVALIDATE_SECRET='.$revalidateSecret);
        $this->line('    3. Vercel\'e import + env\'leri set et + custom domain ekle');
        $this->line('    4. Deploy URL\'i hazır olunca Filament\'te bu site\'ın revalidate_url\'ini güncelle');

        return self::SUCCESS;
    }

    private function homeBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'badge_text' => 'Yeni dönem kayıtları açıldı',
                'headline' => "{$site->city}'da İngilizce Öğrenin",
                'subheadline' => "{$site->name} ile dünyanın en sevilen şehirlerinden birinde yaşa, yerinde öğren, yeni dostluklar kur.",
                'cta_label' => 'Hemen başvur',
                'cta_href' => '#contact',
                'secondary_cta_label' => 'Kursları gör',
                'secondary_cta_href' => '/courses',
                'background_image' => 'https://images.unsplash.com/photo-1496564203457-11bb12075d90?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.6)',
            ]],
            ['type' => 'trust_bar', 'content' => [
                'title' => 'Akreditasyonlarımız',
                'logos' => [
                    ['name' => 'British Council', 'image_url' => 'https://via.placeholder.com/120x40?text=British+Council'],
                    ['name' => 'English UK', 'image_url' => 'https://via.placeholder.com/120x40?text=English+UK'],
                    ['name' => 'Eaquals', 'image_url' => 'https://via.placeholder.com/120x40?text=Eaquals'],
                    ['name' => 'IALC', 'image_url' => 'https://via.placeholder.com/120x40?text=IALC'],
                ],
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Popüler Kurslarımız',
                'intro' => 'Tüm seviyelere ve hedeflere uygun kurs seçenekleri.',
                'items' => [
                    ['name' => 'General English', 'level' => 'a1', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'href' => '/courses/general-english'],
                    ['name' => 'Intensive English', 'level' => 'b1', 'duration' => '30 ders/hafta', 'price_from' => '£415/hafta', 'href' => '/courses/intensive-english'],
                    ['name' => 'IELTS Preparation', 'level' => 'b2', 'duration' => '20+ ders/hafta', 'price_from' => '£345/hafta', 'href' => '/courses/ielts'],
                ],
            ]],
            ['type' => 'city_highlights', 'content' => [
                'title' => "Neden {$site->city}?",
                'intro' => "Tarih, kültür ve kariyer fırsatlarının buluştuğu nokta.",
                'highlights' => [
                    ['icon' => '🎓', 'title' => 'Dünya çapında üniversiteler', 'description' => 'Top 100\'de yer alan kampüslere yakın.'],
                    ['icon' => '🌍', 'title' => 'Çok kültürlü ortam', 'description' => '100+ ülkeden öğrencilerle aynı sınıfta.'],
                    ['icon' => '💼', 'title' => 'Kariyer imkanları', 'description' => 'Staj ve iş deneyimi destekli programlar.'],
                ],
            ]],
            ['type' => 'testimonials', 'content' => [
                'title' => 'Öğrencilerimiz Anlatıyor',
                'items' => [
                    ['quote' => 'Buraya gelmek hayatımı değiştirdi. 6 ayda B2\'den C1\'e çıktım.', 'author' => 'Elena R.', 'author_title' => 'İspanya, B2 → C1', 'rating' => 5],
                    ['quote' => 'Konaklamadan sınıfa kadar her şey çok düşünülmüş. Tavsiye ederim.', 'author' => 'Yuki M.', 'author_title' => 'Japonya, A2 → B1', 'rating' => 5],
                    ['quote' => 'Hocalarımız harika, şehir muhteşem. Pişman olmadım.', 'author' => 'Ahmet K.', 'author_title' => 'Türkiye, B1', 'rating' => 5],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hayalindeki diploma için ilk adım',
                'text' => 'Ücretsiz danışmanlık için hemen formu doldur, 24 saat içinde sana dönelim.',
                'cta_label' => 'Ücretsiz danışmanlık al',
                'cta_href' => '#contact',
                'background_color' => '#0f1e3d',
                'text_color' => '#ffffff',
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'Bize ulaşın',
                'intro' => 'Bilgi almak istediğin kursu seç, formu doldur. 24 saat içinde dönelim.',
                'form_type' => 'contact',
                'show_phone' => true,
                'show_message' => true,
                'show_course_interest' => true,
                'success_message' => 'Teşekkürler! Sana 24 saat içinde dönüş yapacağız.',
                'cta_label' => 'Gönder',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function coursesBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Tüm Kurslarımız',
                'subheadline' => 'A1 başlangıç seviyesinden C2 ileri seviyeye, her hedef için doğru program.',
                'background_image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Genel İngilizce Programları',
                'items' => [
                    ['name' => 'General English (Standard)', 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£295/hafta', 'description' => 'Günlük iletişim ve gramer ağırlıklı.'],
                    ['name' => 'General English (Intensive)', 'level' => 'mixed', 'duration' => '30 ders/hafta', 'price_from' => '£415/hafta', 'description' => 'Hızlı ilerleme isteyenler için.'],
                    ['name' => 'Mini Group Premium', 'level' => 'mixed', 'duration' => '20 ders/hafta', 'price_from' => '£695/hafta', 'description' => 'Maksimum 6 öğrenci, kişiye özel ilgi.'],
                ],
            ]],
            ['type' => 'course_grid', 'content' => [
                'title' => 'Sınav Hazırlık Kursları',
                'items' => [
                    ['name' => 'IELTS Preparation', 'level' => 'b2', 'duration' => '20-30 ders/hafta', 'price_from' => '£345/hafta', 'description' => 'IELTS Academic & General Training.'],
                    ['name' => 'Cambridge Exam Prep', 'level' => 'c1', 'duration' => '30 ders/hafta', 'price_from' => '£425/hafta', 'description' => 'FCE, CAE, CPE.'],
                    ['name' => 'TOEFL', 'level' => 'b2', 'duration' => '20 ders/hafta', 'price_from' => '£365/hafta', 'description' => 'Amerika üniversite başvuruları için.'],
                ],
            ]],
            ['type' => 'faq', 'content' => [
                'title' => 'Sıkça Sorulan Sorular',
                'items' => [
                    ['question' => 'Hangi seviyeden başlamalıyım?', 'answer' => 'Kayıt sırasında ücretsiz online seviye testi yapıyoruz. Sonuca göre size uygun sınıfa yerleştiriyoruz.'],
                    ['question' => 'Kurs en kısa kaç hafta?', 'answer' => 'Genel kurslar 1 haftadan başlar. Sınav hazırlık programları en az 4 hafta önerilir.'],
                    ['question' => 'Sertifika veriyor musunuz?', 'answer' => 'Tüm kurs sonunda akredite kurumlardan onaylı katılım sertifikası veriyoruz.'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Hangi kurs sana uygun?',
                'text' => 'Ücretsiz seviye testi yap, eğitim danışmanımız sana özel program önersin.',
                'cta_label' => 'Seviye testi yap',
                'cta_href' => '#contact',
                'background_color' => '#ff6b35',
                'text_color' => '#ffffff',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function accommodationBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Konaklama Seçenekleri',
                'subheadline' => 'Aile yanı, öğrenci yurdu veya bağımsız apartman — sana uyan rahat ve güvenli seçenekler.',
                'background_image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'accommodation_grid', 'content' => [
                'title' => "{$site->city}'da Konaklama Seçenekleri",
                'items' => [
                    ['name' => 'Aile Yanı (Host Family)', 'type' => 'host_family', 'price_per_week' => '£185/hafta', 'description' => 'İngiliz aile ile yaşa, dili günlük hayatın içinde öğren.', 'features' => ['Kahvaltı dahil', 'Wi-Fi', 'Çamaşır servisi']],
                    ['name' => 'Öğrenci Yurdu', 'type' => 'residence', 'price_per_week' => '£245/hafta', 'description' => 'Diğer öğrencilerle modern yurt ortamı, merkezi konum.', 'features' => ['7/24 güvenlik', 'Mutfak', 'Ortak alanlar']],
                    ['name' => 'Paylaşımlı Apartman', 'type' => 'shared_apartment', 'price_per_week' => '£215/hafta', 'description' => 'Bağımsız yaşam, paylaşımlı mutfak.', 'features' => ['2-4 yatak odası', 'Donanımlı mutfak', 'Wi-Fi']],
                    ['name' => 'Özel Apartman', 'type' => 'private_apartment', 'price_per_week' => '£385/hafta', 'description' => 'Tam bağımsızlık, tam donanımlı.', 'features' => ['Stüdyo veya 1+1', 'Mutfak', 'Klima/ısıtma']],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => 'Sana en uygun konaklamayı birlikte bulalım',
                'cta_label' => 'Bilgi al',
                'cta_href' => '#contact',
                'background_color' => '#0f1e3d',
                'text_color' => '#ffffff',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function cityGuideBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => "{$site->city} Şehir Rehberi",
                'subheadline' => 'Yaşam, eğlence, ulaşım ve kültür hakkında bilmen gereken her şey.',
                'background_image' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'rich_text', 'content' => [
                'markdown' => "## Yeni başlayanlar için {$site->city}\n\n{$site->city}, dünyanın dört bir yanından öğrencileri ağırlayan canlı bir kültür merkezi. Burada her zaman görecek, yapacak, deneyimlecek yeni şeyler var.\n\n### Ulaşım\n\nMetro, otobüs ve bisiklet yollarıyla şehir içi ulaşım çok kolay. Öğrenci kartı ile %30 indirim mevcut.\n\n### Yemek & Yaşam\n\nDünyanın her köşesinden mutfak burada — Türk mantısından Japon ramen'ine, her bütçeye uygun seçenek.",
            ]],
            ['type' => 'article_list', 'content' => [
                'title' => 'Faydalı Yazılar',
                'items' => [
                    ['title' => "{$site->city}'da Mutlaka Görmeniz Gereken 10 Yer", 'excerpt' => 'Tarihten doğaya, modern sanattan gece hayatına önerilerimiz.', 'image_url' => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600', 'date' => now()->subDays(5)->format('Y-m-d'), 'href' => '/city-guide/top-10-places'],
                    ['title' => 'Öğrenci Bütçesiyle Yaşamak', 'excerpt' => 'Aylık masraflarını planlamana yardımcı pratik öneriler.', 'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=600', 'date' => now()->subDays(12)->format('Y-m-d'), 'href' => '/city-guide/student-budget'],
                    ['title' => 'Halk Taşımacılığı: Adım Adım Rehber', 'excerpt' => "Metro, otobüs, bisiklet — {$site->city}'da nasıl gidersin?", 'image_url' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600', 'date' => now()->subDays(20)->format('Y-m-d'), 'href' => '/city-guide/public-transport'],
                ],
            ]],
            ['type' => 'cta_banner', 'content' => [
                'headline' => "{$site->city}'a hazır mısın?",
                'cta_label' => 'Kayıt ol',
                'cta_href' => '#contact',
                'background_color' => '#ff6b35',
                'text_color' => '#ffffff',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function pricingBlocks(Site $site): array
    {
        return [
            ['type' => 'hero_school', 'content' => [
                'headline' => 'Fiyat ve Paketler',
                'subheadline' => 'Sana uygun süreyi ve paketi birlikte seçelim. Erken kayıt indirimleri için iletişime geç.',
                'background_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1920',
                'overlay_color' => 'rgba(15,30,61,0.55)',
            ]],
            ['type' => 'pricing_table', 'content' => [
                'title' => 'Standart Paketler',
                'intro' => 'Kurs + konaklama + havalimanı transferi dahil paketlerimiz.',
                'plans' => [
                    ['name' => '4 Hafta — Başlangıç', 'price' => '£1.380', 'period' => '4 hafta', 'features' => ['20 ders/hafta', 'Aile yanı konaklama', 'Yarım pansiyon', 'Sertifika', 'Ücretsiz Wi-Fi'], 'cta_label' => 'Bilgi al', 'cta_href' => '#contact'],
                    ['name' => '12 Hafta — Yoğun', 'price' => '£3.890', 'period' => '12 hafta', 'features' => ['30 ders/hafta', 'Öğrenci yurdu konaklama', 'IELTS hazırlık seçeneği', 'Şehir kartı', 'Geziler dahil'], 'cta_label' => 'Hemen başvur', 'cta_href' => '#contact', 'highlighted' => true],
                    ['name' => '24 Hafta — Akademik Yıl', 'price' => '£6.450', 'period' => '24 hafta', 'features' => ['Sınırsız ders saati', 'Premium konaklama', 'Üniversite başvuru desteği', 'Staj imkanı', 'Vize asistanı'], 'cta_label' => 'Detaylı bilgi', 'cta_href' => '#contact'],
                ],
            ]],
            ['type' => 'contact_form', 'content' => [
                'title' => 'Sana özel teklif al',
                'intro' => 'Hangi kurs, hangi süre, hangi konaklama — formu doldur, kişisel teklifimizi sana 24 saat içinde gönderelim.',
                'form_type' => 'price_quote',
                'show_phone' => true,
                'show_message' => true,
                'show_course_interest' => true,
                'success_message' => 'Teşekkürler! Kişisel teklifimizi e-postana 24 saat içinde göndereceğiz.',
                'cta_label' => 'Teklif iste',
            ]],
            ['type' => 'footer_mega', 'content' => $this->footerContent($site)],
        ];
    }

    private function footerContent(Site $site): array
    {
        $brandConfig = config("brands.{$site->brand}", []);

        return [
            'tagline' => "{$site->name} — dünyanın dilini öğret, dünyanın kapısını aç.",
            'columns' => [
                ['title' => 'Programlar', 'links' => [
                    ['label' => 'Genel İngilizce', 'href' => '/courses'],
                    ['label' => 'Sınav Hazırlık', 'href' => '/courses'],
                    ['label' => 'Yaz Programları', 'href' => '/courses'],
                ]],
                ['title' => 'Konaklama', 'links' => [
                    ['label' => 'Aile Yanı', 'href' => '/accommodation'],
                    ['label' => 'Öğrenci Yurdu', 'href' => '/accommodation'],
                    ['label' => 'Apartman', 'href' => '/accommodation'],
                ]],
                ['title' => 'Şehir', 'links' => [
                    ['label' => "{$site->city} Rehberi", 'href' => '/city-guide'],
                    ['label' => 'Fiyatlar', 'href' => '/pricing'],
                    ['label' => 'İletişim', 'href' => '#contact'],
                ]],
            ],
            'social_links' => [
                ['platform' => 'instagram', 'href' => 'https://instagram.com/'.($brandConfig['parent_domain'] ?? 'kaplan')],
                ['platform' => 'facebook', 'href' => 'https://facebook.com/'.($brandConfig['parent_domain'] ?? 'kaplan')],
                ['platform' => 'youtube', 'href' => 'https://youtube.com/'.($brandConfig['parent_domain'] ?? 'kaplan')],
            ],
            'copyright_text' => '© '.date('Y').' '.$site->name.'. Tüm hakları saklıdır.',
            'background_color' => '#0f1e3d',
            'text_color' => '#cbd5e1',
        ];
    }

    /**
     * Demo amaçlı bare-bones translation. Production'da bunu i18n
     * service'i devralır. Şimdilik sadece "Ana Sayfa" gibi temel
     * label'ları en'e çevirir; diğer locale'ler default'a düşer.
     */
    private function translate(string $text, string $locale): string
    {
        if ($locale === 'tr') {
            return $text;
        }

        $map = [
            'en' => [
                'Ana Sayfa' => 'Home',
                'Kurslar' => 'Courses',
                'Konaklama' => 'Accommodation',
                'Şehir Rehberi' => 'City Guide',
                'Fiyat & İletişim' => 'Pricing & Contact',
            ],
        ];

        return $map[$locale][$text] ?? $text;
    }

    /**
     * Locale'e göre bazı içerik string'lerini çevirir.
     * Çok basit — admin sonradan kendi diline manuel yazacak.
     */
    private function localizeContent(array $content, string $locale): array
    {
        if ($locale === 'tr') {
            return $content;
        }

        // Sadece en için kabataslak çeviri. Diğer dillerde admin elle yazar.
        $en = [
            'Hemen başvur' => 'Apply now',
            'Kursları gör' => 'See courses',
            'Hemen başla' => 'Get started',
            'Bize ulaşın' => 'Contact us',
            'Bilgi al' => 'Get info',
            'Gönder' => 'Submit',
            'Kayıt ol' => 'Enroll',
            'Detaylı bilgi' => 'More info',
            'Teklif iste' => 'Request quote',
            'Seviye testi yap' => 'Take placement test',
            'Ücretsiz danışmanlık al' => 'Get free consultation',
            'Sınav Hazırlık' => 'Exam prep',
            'Yaz Programları' => 'Summer programs',
            'Genel İngilizce' => 'General English',
        ];

        $trans = function ($v) use ($en, $locale) {
            if ($locale !== 'en' || ! is_string($v)) {
                return $v;
            }

            return $en[$v] ?? $v;
        };

        $walk = function ($node) use (&$walk, $trans) {
            if (is_array($node)) {
                $out = [];
                foreach ($node as $k => $v) {
                    $out[$k] = $walk($v);
                }

                return $out;
            }

            return $trans($node);
        };

        return $walk($content);
    }
}
