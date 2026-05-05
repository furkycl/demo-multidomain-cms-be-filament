# Multi-Locale (Çoklu Dil) Yönetim Rehberi

Bu dokümantasyon multi-cms'in 10 dilli yapısının nasıl yönetileceğini detaylı anlatır.

## Mimari Özeti

```
1 Site (kaplan-london.com)
├── Page locale=tr, slug=/         (Türkçe ana sayfa)
├── Page locale=tr, slug=/destinations
├── Page locale=tr, slug=/products
├── Page locale=tr, slug=/blog
├── Page locale=en, slug=/         (İngilizce ana sayfa — aynı slug, farklı dil)
├── Page locale=en, slug=/destinations
├── Page locale=en, slug=/products
└── Page locale=en, slug=/blog
```

**Kural**: Aynı `(site_id, slug)` çiftine birden fazla locale ile farklı Page kayıtları düşer. Her biri o dilin "çevirisi" gibi davranır.

## Desteklenen 10 Dil

| Code | Dil | Yön |
|------|-----|-----|
| tr | Türkçe | LTR |
| en | English | LTR |
| ar | العربية | **RTL** |
| fr | Français | LTR |
| es | Español | LTR |
| pt | Português | LTR |
| ko | 한국어 | LTR |
| ja | 日本語 | LTR |
| it | Italiano | LTR |
| de | Deutsch | LTR |

Tek kaynak: `config/locales.php`. Frontend ve backend bu listeyi paylaşır.

## URL Yapısı

```
/                  → /tr (default locale, 308 redirect)
/tr/               → Türkçe ana sayfa
/tr/destinations   → Türkçe destinasyonlar
/en/destinations   → İngilizce destinasyonlar
/ar/               → Arapça ana sayfa (RTL)
```

## Yeni Site Provision Etmek

```bash
php artisan multi-cms:provision-site \
  --domain=kaplan-london.com \
  --name="Kaplan London" \
  --brand=kaplan \
  --city=London --country=GB \
  --locales=tr,en  \  # İlk başta TR + EN ile başla
  --force
```

Bu komut:
- TR locale için 4 sayfa açar (tr placeholder içerikleriyle)
- EN locale için 4 sayfa açar (en placeholder içerikleriyle)
- Toplam 8 sayfa

## Yeni Locale Eklemek

Site açıldıktan sonra Türkçe + İngilizce dışında yeni dil eklemek için:

### Yöntem 1 — Filament admin (tek tek)

1. Filament → **Sites** → Site'a tıkla → düzenle
2. **Default locales** alanına yeni dil ekle (örn `fr`)
3. Save
4. **Pages** → **Create**:
   - Site = bu site
   - Locale = `fr`
   - Slug = `/`
   - Title = "Accueil"
   - Bloklar sekmesinden manuel blok ekle (TR sayfasını referans alarak çevir)
5. Aynısını `/destinations`, `/products`, `/blog` için tekrarla

### Yöntem 2 — Provision tekrar çalıştır (--force ile)

⚠ **DİKKAT**: Bu mevcut tüm Page+Block kayıtlarını **siler ve yeniden yaratır**. Admin'in yaptığı çevirileri kaybedersin.

Bu yöntemi sadece **yeni site açılırken** kullan, sonra manuel git.

### Yöntem 3 (önerilen) — "Page'i diğer dillere kopyala" Action

Bu özellik henüz yok, ileride eklenecek:
- Filament → Pages → bir page'e tıkla
- "Diğer dillere kopyala" action'ı: bu page'in tüm bloklarını seçilen locale'lere boş içerik kopyaları olarak çoğaltır
- Admin sonradan o sayfalara girip içerikleri çevirir

## Çeviri Akışı (Önerilen)

1. **TR sayfasını eksiksiz doldur** (referans dil)
2. **EN sayfasını TR'den manuel çevir**
3. Diğer 8 dili EN üzerinden çevir (TR daha az evrensel)
4. Çevirmen veya Google Translate ile her dil için manuel page editle

**İpucu**: Bir Excel sheet'te tüm metin alanlarını TR | EN | AR | FR ... şeklinde paralel kolonlarda tut. Çeviri tamamlanınca admin'e tek tek gir.

## hreflang Otomatik

Backend `GET /api/sites/{domain}/{locale}/pages/{slug}` çağrıldığında yanıtta:

```json
{
  "alternates": {
    "tr": "/tr/destinations",
    "en": "/en/destinations",
    "fr": "/fr/destinations"
  }
}
```

Frontend bu listeyi `<link rel="alternate" hreflang="..." />` olarak head'e basar. Google bu sayfaların aynı içeriğin farklı dilleri olduğunu anlar.

**Gereklilik**: Bir slug için **hangi locale'de yayınlanmış sayfa varsa** o dil hreflang'a girer. Eğer FR sayfası `/products` için açılmamışsa, o slug'da FR alternate'i görünmez.

## Language Switcher (Frontend)

`components/LanguageSwitcher.tsx` — header'da Globe + locale code dropdown. Dropdown alternates listesini kullanır:

```tsx
<a href={alternates['fr']}>Français</a>
```

Eğer mevcut sayfada `fr` çevirisi yoksa, o dil dropdown'da çıkmaz (404'e gitmek yerine).

## Form'lar — Locale-aware Lead Routing

Contact form `POST /api/leads` çağrısında:
- `locale: 'tr'` → backend `crm_target = omnigos`
- `locale: 'fr'` → backend `crm_target = omnigos` (şu an hepsi Omnigos)

config/locales.php → her locale'in `crm_target` field'ı bu kararı verir.

## RTL (Arapça) Desteği

`app/[locale]/layout.tsx`:

```tsx
const dir = LOCALE_INFO[params.locale].direction;
return <html lang={params.locale} dir={dir}>{children}</html>;
```

Tailwind RTL utility'leri (logical properties: `me-`, `ms-`, `ps-`, `pe-`) kullanılır. Block component'leri çoğunlukla LTR/RTL'e duyarsız (centered text, flex justify) — ama özel komponent yazarken `text-start` yerine `text-left` yazma.

## SEO Best Practices

- Her page'in **kendi `seo.title`** ve `seo.description`'ı admin paneli SEO sekmesinden girilir
- `og_image` her dil için ayrı verilebilir (yerel pazara hitap eden görsel)
- Sitemap.xml her locale için ayrı satırlar üretir (`app/sitemap.ts`)

## Pratik Senaryolar

### "TR ana sayfada video değiştirdim, EN sayfası da güncellensin mi?"

**Hayır.** Her locale ayrı page kayıtları, ayrı blok kayıtları. TR'deki değişiklik sadece TR'yi etkiler. EN için de aynı bloğu güncellemen gerekir.

### "Bütün dillerde aynı YouTube videosunu kullanmak istiyorum"

Şu an manuel — her locale'in hero_video bloğuna aynı URL'i yapıştır. İleride "global asset library" eklenebilir (admin asset'i bir kez upload eder, tüm dillerde referansla).

### "Yeni şehir eklemek için"

`destinations_grid` bloğunu admin paneli üzerinden TR sayfasında düzenle, kart ekle. Sonra EN ve diğer dillerde aynı kartı çevirip ekle.

### "Site açıldı ama 1 dil eksik kaldı, sonra eklemek için"

Filament → Pages → Create → Site + locale + slug + bloklar. Mevcut TR sayfasını başka tab'da açıp blok by blok çevirerek ekle. Saatler içinde manuel ama tek seferlik bir iş.

## Lokal Test

3 site lokal'de farklı port'larda çalıştığında:
- `localhost:8001/tr` → site-a TR
- `localhost:8001/en` → site-a EN (aynı site, dil farkı)
- `localhost:8002/tr` → site-b TR (farklı site)

Her site bağımsız `Site` kaydına bağlı, kendi domain'iyle backend'den içerik çeker.

## Production'a Geçiş

Production'da her site Vercel'de kendi domain'iyle (örn `kaplan-london.com`):
- `kaplan-london.com/` → /tr (default redirect)
- `kaplan-london.com/tr/` → TR ana sayfa
- `kaplan-london.com/en/` → EN ana sayfa
- `kaplan-london.com/fr/` → FR ana sayfa (varsa)

Frontend `SITE_DOMAIN=kaplan-london.com` env'iyle backend'e bağlı. Backend bu domain'in `Site` kaydını bulup ilgili locale'ün page'lerini döner.
