<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sıralı, tipli içerik bloğu.
 *
 * @property int $id
 * @property int $page_id
 * @property string $type
 * @property int $order
 * @property array $content   Type-specific JSON; bkz. docs/blocks/<type>.md
 * @property int $schema_version
 */
class Block extends Model
{
    use HasFactory;

    /**
     * Bilinen blok tipleri.
     *
     * Genel:
     *   header, hero, rich_text, footer
     *
     * Microsite (Kaplan/Alpadia/Azurlingua) template:
     *   hero_school, course_grid, accommodation_grid, city_highlights,
     *   article_list, pricing_table, contact_form, faq, testimonials,
     *   trust_bar, cta_banner, footer_mega
     */
    public const TYPES = [
        // Generic
        'header',
        'hero',
        'rich_text',
        'footer',
        // School microsite
        'hero_school',
        'course_grid',
        'accommodation_grid',
        'city_highlights',
        'article_list',
        'pricing_table',
        'contact_form',
        'faq',
        'testimonials',
        'trust_bar',
        'cta_banner',
        'footer_mega',
    ];

    protected $fillable = [
        'page_id',
        'type',
        'order',
        'content',
        'schema_version',
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
