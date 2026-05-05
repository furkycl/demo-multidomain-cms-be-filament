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
     * GET /api/sites/{domain}/pages/{slug?}
     *
     * Returns the page (with ordered blocks) for the given domain.
     * Public, read-only. Cached at edge by Next.js (revalidate: 60).
     */
    public function showPage(Request $request, string $domain, ?string $slug = null): JsonResponse
    {
        $domain = $this->normalize($domain);
        $slug = $this->normalizeSlug($slug);

        $site = Site::where('domain', $domain)->first();
        if (! $site) {
            return response()->json(['error' => 'site_not_found'], Response::HTTP_NOT_FOUND);
        }

        $page = Page::where('site_id', $site->id)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('blocks')
            ->first();

        if (! $page) {
            return response()->json(['error' => 'page_not_found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'site' => [
                'domain' => $site->domain,
                'name' => $site->name,
                'theme' => $site->theme ?? [],
            ],
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'seo' => $page->seo ?? [],
            ],
            'blocks' => $page->blocks->map(fn ($b) => [
                'id' => $b->id,
                'type' => $b->type,
                'order' => $b->order,
                'content' => $b->content,
                'schema_version' => $b->schema_version,
            ])->values(),
        ]);
    }

    private function normalize(string $domain): string
    {
        $domain = strtolower(trim($domain));

        return preg_replace('/^www\./', '', $domain);
    }

    private function normalizeSlug(?string $slug): string
    {
        if ($slug === null || $slug === '' || $slug === '/') {
            return '/';
        }

        return '/'.trim($slug, '/');
    }
}
