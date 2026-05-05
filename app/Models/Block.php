<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    use HasFactory;

    public const TYPES = [
        'hero', 'hero_school', 'hero_video',
        'rich_text', 'course_grid', 'accommodation_grid', 'destinations_grid',
        'city_highlights', 'article_list', 'about',
        'header', 'footer', 'footer_mega', 'trust_bar',
        'cta_banner', 'contact_form', 'pricing_table', 'faq', 'testimonials',
    ];

    public const CATEGORIES = [
        'Hero (sayfa başı)' => ['hero_video', 'hero_school', 'hero'],
        'İçerik' => ['destinations_grid', 'course_grid', 'about', 'article_list', 'city_highlights', 'accommodation_grid', 'rich_text'],
        'Dönüşüm (lead/CTA)' => ['contact_form', 'cta_banner', 'pricing_table', 'testimonials', 'faq'],
        'Düzen (header/footer)' => ['header', 'footer_mega', 'trust_bar', 'footer'],
    ];

    protected $fillable = ['page_id', 'type', 'order', 'content', 'schema_version'];
    protected $casts = ['content' => 'array', 'order' => 'integer', 'schema_version' => 'integer'];
    protected $attributes = ['schema_version' => 1, 'content' => '{}'];
    public function page(): BelongsTo { return $this->belongsTo(Page::class); }
}
