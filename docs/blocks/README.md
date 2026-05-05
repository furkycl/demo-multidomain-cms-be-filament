# Block types catalog

Block tipleri `App\Models\Block::TYPES` listesinde; her tipin Filament form
şeması `BlocksRelationManager::blockSchema($type)` içinde.

## Tip listesi

### Generic (legacy, basit kullanım için)
| type | açıklama |
|------|----------|
| `header` | Tek logo + menü + arkaplan rengi |
| `hero` | Basit hero (başlık + CTA) |
| `rich_text` | Markdown bloğu |
| `footer` | Tek metin + arkaplan |

### Microsite template (Kaplan/Alpadia/Azurlingua için)
| type | açıklama |
|------|----------|
| `hero_school` | Tam ekran kapak görseli + headline + 2 CTA + rozet |
| `course_grid` | Kurs kart grid'i (ad, level, süre, fiyat, görsel) |
| `accommodation_grid` | Konaklama tipleri (host family/residence/apartment) |
| `city_highlights` | Şehir hakkında 3-6 öne çıkan özellik |
| `article_list` | Blog/şehir rehberi yazıları listesi |
| `pricing_table` | Fiyat planları (highlighted=önerilen) |
| `contact_form` | Form embed (form_type: contact/brochure/callback/price_quote) |
| `faq` | Soru-cevap accordion |
| `testimonials` | Öğrenci yorumları (quote, author, rating) |
| `trust_bar` | Akreditasyon/partner logoları |
| `cta_banner` | Sayfa içi conversion banner |
| `footer_mega` | Çok sütunlu footer + sosyal + copyright |

## Yeni tip ekleme

1. `App\Models\Block::TYPES`'a ekle
2. `BlocksRelationManager::blockSchema()`'a case ekle
3. `BlocksRelationManager::form()` Select options'ına ekle (label)
4. Frontend: `lib/types.ts` + `components/blocks/<Name>.tsx` + `BlockRenderer` map
5. Bu README'yi güncelle
