<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Lead;
use App\Models\Site;
use App\Services\CrmRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadController
{
    public function __construct(private CrmRouter $crm) {}

    /**
     * POST /api/leads
     *
     * Body:
     * {
     *   "source_domain": "site-a.com",
     *   "locale": "tr",
     *   "form_type": "contact",
     *   "payload": { name, email, phone, message, ...arbitrary fields },
     *   "utm": { source?, medium?, campaign? },
     *   "referrer": "...",
     * }
     *
     * Yanıt: { ok: true, lead_id, crm_status }
     * Hata:  4xx/5xx + { error: "..." }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_domain' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'size:2'],
            'form_type' => ['nullable', 'string', 'max:64'],
            'payload' => ['required', 'array'],
            'payload.email' => ['required', 'string', 'email'],
            'payload.name' => ['nullable', 'string', 'max:255'],
            'utm' => ['nullable', 'array'],
            'utm.source' => ['nullable', 'string', 'max:255'],
            'utm.medium' => ['nullable', 'string', 'max:255'],
            'utm.campaign' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:500'],
        ]);

        $supported = array_keys(config('locales.supported', []));
        if (! in_array($data['locale'], $supported, true)) {
            return response()->json(['error' => 'unsupported_locale'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $domain = strtolower(preg_replace('/^www\./', '', trim($data['source_domain'])));
        $site = Site::where('domain', $domain)->first();

        $lead = Lead::create([
            'site_id' => $site?->id,
            'source_domain' => $domain,
            'locale' => $data['locale'],
            'brand' => $site?->brand,
            'crm_target' => CrmRouter::targetForLocale($data['locale']),
            'form_type' => $data['form_type'] ?? 'contact',
            'payload' => $data['payload'],
            'utm_source' => $data['utm']['source'] ?? null,
            'utm_medium' => $data['utm']['medium'] ?? null,
            'utm_campaign' => $data['utm']['campaign'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        // Senkron — basit. Volume artarsa dispatchSync yerine queue'ya alınır.
        $this->crm->dispatch($lead);

        return response()->json([
            'ok' => true,
            'lead_id' => $lead->id,
            'crm_target' => $lead->crm_target,
            'crm_status' => $lead->crm_status,
        ], Response::HTTP_CREATED);
    }
}
