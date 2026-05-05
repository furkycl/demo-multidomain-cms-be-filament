<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lead capture record. Form gönderildiğinde önce burada saklanır
 * (audit + offline backup) sonra CrmRouter ile dış CRM'e iletilir.
 */
class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'source_domain',
        'locale',
        'brand',
        'crm_target',
        'form_type',
        'payload',
        'crm_status',
        'crm_response',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'user_agent',
        'ip',
    ];

    protected $casts = [
        'payload' => 'array',
        'crm_response' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
