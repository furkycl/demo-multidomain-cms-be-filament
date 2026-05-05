# Lead Capture & CRM Routing

## Akış

```
[Frontend form submit]
        │ POST /api/leads
        │ { source_domain, locale, form_type, payload, utm, referrer }
        ▼
[LeadController::store]
   1. Validate
   2. Resolve Site by domain
   3. Compute crm_target (config/locales.php → crm_target)
   4. Insert into leads table (status=pending)
   5. CrmRouter::dispatch($lead)
        │
        ├── endpoint configured  → POST to CRM, status=sent | failed
        └── endpoint empty       → status=skipped (demo modu)
        ▼
[200 Created]
{ ok, lead_id, crm_target, crm_status }
```

## Routing tablosu

| Locale grubu | CRM | Env |
|--------------|-----|-----|
| tr, en, ar | Omnigos | `OMNIGOS_LEAD_ENDPOINT`, `OMNIGOS_API_KEY` |
| fr, es, pt, ko, ja, it, de | Linguland | `LINGULAND_LEAD_ENDPOINT`, `LINGULAND_API_KEY` |

Routing kuralı `config/locales.php`'deki `crm_target` field'ından okunur. Tek bir locale'in CRM'ini değiştirmek isterseniz config'i edit edip deploy yeterli.

## CRM endpoint sözleşmesi

POST request body:

```json
{
  "source_domain": "kaplan-london.com",
  "locale": "en",
  "brand": "kaplan",
  "form_type": "contact",
  "payload": {
    "name": "John Doe",
    "email": "...",
    "phone": "...",
    "course_interest": "general_english",
    "message": "..."
  },
  "utm": { "source": "google", "medium": "cpc", "campaign": "uk_jan" },
  "meta": { "referrer": "...", "user_agent": "..." }
}
```

Headers: `Authorization: Bearer ${API_KEY}`.

Beklenen yanıt: 2xx (sent), 4xx (failed). Detay `lead.crm_response`'da saklanır.

## Audit

Tüm lead'ler `leads` tablosunda. Filament admin → CRM → Leads (read-only).
Filtre: `crm_status` (sent/pending/failed/skipped), `crm_target` (omnigos/linguland).

## Volume artarsa

Şu anda dispatch senkron. CRM yavaş olur ya da volume yükselirse:
1. `CrmRouter::dispatch` job içine taşı
2. `LeadController` sadece DB'ye yaz, `dispatch` queue'ya at
3. Render queue worker zaten supervisor'da yapılandırılmış
