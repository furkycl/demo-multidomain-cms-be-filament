<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sıralı, tipli içerik bloğu.
 *
 * Block tipleri kategorilere ayrılır (Filament UI'da gruplanır):
 *   Hero       : hero, hero_school, hero_video
 *   Content    : rich_text, course_grid, accommodation_grid, destinations_grid,
 *                city_highlights, article_list, products_grid
 *   Layout     : header, footer, footer_mega, trust_bar
 *   Conversion : cta_banner, contact_form, pricing_table, faq, testimonials
 */
class Block extends Model
{
    use HasFactory;

    public const TYPES = [
        // Hero
        'hero', 'hero_school', 'hero_video',
        // Content
        'rich_text', 'course_grid', 'accommodation_grid', 'destinations_grid',
        'city_highlights', 'article_list',
        // Layout
        'header', 'footer', 'footer_mega', 'trust_bar',
        // Conversion
        'cta_banner', 'contact_form', 'pricing_table', 'faq', 'testimonials',
    ];

    /** Filament UI'da bloklar bu kategoriler altında gösterilir. */
    public const CATEGORIES = [
        'Hero (sayfa başı)' => ['hero_video', 'hero_school', 'hero'],
        'İçerik' => ['destinations_grid', 'course_grid', 'article_list', 'city_highlights', 'accommodation_grid', 'rich_text'],
        'Dönüşüm (lead/CTA)' => ['contact_form', 'cta_banner', 'pricing_table', 'testimonials', 'faq'],
        'Düzen (header/footer)' => ['footer_mega', 'trust_bar', 'header', 'footer'],
    ];

    protected $fillable = [
        'page_id', 'type', 'order', 'content', 'schema_version',
    ];

    protected $casts = [
        'content' => 'array',
        'order' => 'integer',
        'schema_version' => 'integer',
    ];

    protected $attributes = [
        'schema_version' => 1,
        'content' => '{}',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
