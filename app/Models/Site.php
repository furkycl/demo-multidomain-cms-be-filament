<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Site per customer domain.
 *
 * @property int $id
 * @property string $domain         Canonical hostname (no protocol, no www).
 * @property string $name           Display name in admin.
 * @property string|null $revalidate_url    Full URL the frontend exposes for ISR webhook.
 * @property string|null $revalidate_secret Shared secret with that frontend.
 * @property array $theme           Site-wide theme tokens (colors, fonts, logo URL).
 */
class Site extends Model implements HasName
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'name',
        'revalidate_url',
        'revalidate_secret',
        'theme',
    ];

    protected $casts = [
        'theme' => 'array',
    ];

    protected $hidden = [
        'revalidate_secret',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
