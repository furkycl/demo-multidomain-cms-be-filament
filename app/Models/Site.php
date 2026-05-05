<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One Site per location-based microsite.
 *
 * @property int $id
 * @property string $domain               Canonical hostname (no protocol, no www).
 * @property string $name                 Display name in admin.
 * @property string|null $brand           'kaplan' | 'alpadia' | 'azurlingua'
 * @property string|null $city            Şehir (lokal SEO için)
 * @property string|null $country         ISO 3166-1 alpha-2 (US, GB, FR, ES…)
 * @property array|null $default_locales  Bu sitede aktif locale listesi
 * @property string|null $revalidate_url
 * @property string|null $revalidate_secret
 * @property array $theme
 */
class Site extends Model implements HasName
{
    use HasFactory;

    public const BRANDS = ['kaplan', 'alpadia', 'azurlingua'];

    protected $fillable = [
        'domain',
        'name',
        'brand',
        'city',
        'country',
        'default_locales',
        'revalidate_url',
        'revalidate_secret',
        'theme',
    ];

    protected $casts = [
        'theme' => 'array',
        'default_locales' => 'array',
    ];

    protected $hidden = [
        'revalidate_secret',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Bu site için aktif locale listesi.
     * Site'a özel default_locales tanımlıysa onu, yoksa config'in supported listesini döner.
     *
     * @return array<int, string>
     */
    public function activeLocales(): array
    {
        if (! empty($this->default_locales)) {
            return $this->default_locales;
        }

        return array_keys(config('locales.supported', []));
    }
}
