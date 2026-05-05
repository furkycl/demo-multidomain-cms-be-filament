<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lead'i locale'ine bakıp doğru CRM'e iletir.
 *
 * Routing tablosu: config/locales.php → her locale'in 'crm_target' field'ı.
 *   tr/en/ar  → omnigos  (Topstudy)
 *   fr/es/pt/ko/ja/it/de → linguland
 *
 * CRM endpoint env'leri boşsa "skipped" olarak işaretler — DB'de saklı kalır.
 * Bu, demo modunda ya da CRM erişimi yoksa graceful fallback sağlar.
 */
class CrmRouter
{
    /**
     * Locale'e göre hangi CRM target'ına gideceğini bul.
     */
    public static function targetForLocale(string $locale): string
    {
        $supported = config('locales.supported', []);

        return $supported[$locale]['crm_target'] ?? 'omnigos';
    }

    /**
     * Lead'i CRM'e gönder. Sonuç `Lead.crm_status` + `Lead.crm_response` olarak yazılır.
     */
    public function dispatch(Lead $lead): Lead
    {
        $crmKey = $lead->crm_target;
        $config = config("services.crm.$crmKey");

        if (empty($config['endpoint'])) {
            $lead->crm_status = 'skipped';
            $lead->crm_response = ['reason' => 'crm_endpoint_not_configured', 'crm' => $crmKey];
            $lead->save();
            Log::info('crm.skipped', ['lead' => $lead->id, 'crm' => $crmKey]);

            return $lead;
        }

        try {
            $response = Http::timeout($config['timeout'] ?? 5)
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => 'Bearer '.($config['api_key'] ?? ''),
                ])
                ->post($config['endpoint'], [
                    'source_domain' => $lead->source_domain,
                    'locale' => $lead->locale,
                    'brand' => $lead->brand,
                    'form_type' => $lead->form_type,
                    'payload' => $lead->payload,
                    'utm' => [
                        'source' => $lead->utm_source,
                        'medium' => $lead->utm_medium,
                        'campaign' => $lead->utm_campaign,
                    ],
                    'meta' => [
                        'referrer' => $lead->referrer,
                        'user_agent' => $lead->user_agent,
                    ],
                ]);

            if ($response->successful()) {
                $lead->crm_status = 'sent';
                $lead->crm_response = ['status' => $response->status(), 'body' => $response->json() ?? $response->body()];
            } else {
                $lead->crm_status = 'failed';
                $lead->crm_response = ['status' => $response->status(), 'body' => $response->body()];
            }
        } catch (\Throwable $e) {
            $lead->crm_status = 'failed';
            $lead->crm_response = ['error' => $e->getMessage()];
            Log::warning('crm.dispatch_failed', ['lead' => $lead->id, 'error' => $e->getMessage()]);
        }

        $lead->save();

        return $lead;
    }
}
