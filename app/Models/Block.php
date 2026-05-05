<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sıralı, tipli içerik bloğu. content her tip için farklı JSON şeması — docs/blocks/ altında.
 *
 * @property int $id
 * @property int $page_id
 * @property string $type           'header' | 'hero' | 'rich_text' | 'footer' | ...
 * @property int $order
 * @property array $content         Type-specific JSON; see docs/blocks/<type>.md
 * @property int $schema_version
 */
class Block extends Model
{
    use HasFactory;

    /** Known block types. Genişledikçe buraya ekle ve docs/blocks/ altında spec yaz. */
    public const TYPES = [
        'header',
        'hero',
        'rich_text',
        'footer',
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
