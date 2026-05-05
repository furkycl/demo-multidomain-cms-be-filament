<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteController
{
    /**
     * GET /api/sites/{domain}/{locale}/pages/{slug?}
     *
     * Yanıt: { site, page, blocks, alternates }
     *   - site         : { domain, name, brand, city, country, theme }
     *   - page         : { slug, title, locale, seo }
     *   - blocks       : Block[]
     *   - alternates   : { [locale]: url-path }   ← hreflang için
     *   - locales      : aktif locale listesi (language switcher için)
     */
    public function showPage(Request $request, string $domain, string $locale, ?string $slug = null): JsonResponse
    {
        $domain = $this->normalizeDomain($domain);
        $slug = $this->normalizeSlug($slug);

        $supported = config('locales.supported', []);
        if (! isset($supported[$locale])) {
            return response()->json(['error' => 'unsupported_locale'], Response::HTTP_BAD_REQUEST);
        }

        $site = Site::where('domain', $domain)->first();
        if (! $site) {
            return response()->json(['error' => 'site_not_found'], Response::HTTP_NOT_FOUND);
        }

        $page = Page::where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('blocks')
            ->first();

        if (! $page) {
            return response()->json(['error' => 'page_not_found'], Response::HTTP_NOT_FOUND);
        }

        // Diğer dillerdeki aynı slug'lı yayınlanmış sayfalar — hreflang
        $alternates = Page::where('site_id', $site->id)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->get(['locale', 'slug'])
            ->mapWithKeys(fn ($p) => [$p->locale => '/'.$p->locale.($p->slug === '/' ? '' : $p->slug)])
            ->all();

        return response()->json([
            'site' => [
                'domain' => $site->domain,
                'name' => $site->name,
                'brand' => $site->brand,
                'city' => $site->city,
                'country' => $site->country,
                'theme' => $site->theme ?? [],
            ],
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'locale' => $page->locale,
                'seo' => $page->seo ?? [],
            ],
            'blocks' => $page->blocks->map(fn ($b) => [
                'id' => $b->id,
                'type' => $b->type,
                'order' => $b->order,
                'content' => $b->content,
                'schema_version' => $b->schema_version,
            ])->values(),
            'alternates' => $alternates,
            'locales' => $site->activeLocales(),
        ]);
    }

    /**
     * GET /api/sites/{domain}
     * Site bilgisini ve aktif locale'leri döner — language switcher / sitemap için.
     */
    public function show(string $domain): JsonResponse
    {
        $domain = $this->normalizeDomain($domain);
        $site = Site::where('domain', $domain)->first();
        if (! $site) {
            return response()->json(['error' => 'site_not_found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'domain' => $site->domain,
            'name' => $site->name,
            'brand' => $site->brand,
            'city' => $site->city,
            'country' => $site->country,
            'theme' => $site->theme ?? [],
            'locales' => $site->activeLocales(),
            'default_locale' => config('locales.default'),
        ]);
    }

    private function normalizeDomain(string $domain): string
    {
        return preg_replace('/^www\./', '', strtolower(trim($domain)));
    }

    private function normalizeSlug(?string $slug): string
    {
        if ($slug === null || $slug === '' || $slug === '/') {
            return '/';
        }

        return '/'.trim($slug, '/');
    }
}
