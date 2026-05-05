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
 * @property string $slug          "/" for home, "/about", "/blog/post-1"
 * @property string $title
 * @property bool $is_published
 * @property array $seo            { title?: string, description?: string, og_image?: string }
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'slug',
        'title',
        'is_published',
        'seo',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'seo' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }
}
