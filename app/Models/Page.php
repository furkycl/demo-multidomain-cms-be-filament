<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $site_id
 * @property string $locale            ISO 639-1 (tr, en, ar …)
 * @property string $slug              "/" for home, "/courses", "/city-guide/london"
 * @property string $title
 * @property bool $is_published
 * @property array $seo                { title?: string, description?: string, og_image?: string }
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'locale',
        'slug',
        'title',
        'is_published',
        'seo',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'seo' => 'array',
    ];

    protected $attributes = [
        'locale' => 'tr',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    /**
     * Bu page'in diğer locale'deki sibling'leri — hreflang için frontend kullanır.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public function siblings()
    {
        return self::where('site_id', $this->site_id)
            ->where('slug', $this->slug)
            ->where('id', '!=', $this->id)
            ->where('is_published', true)
            ->get();
    }
}
