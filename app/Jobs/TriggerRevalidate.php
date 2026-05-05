<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifies a site's frontend to revalidate one or more paths.
 * Called by BlockObserver / PageObserver. Runs in the queue so admin saves are snappy.
 */
class TriggerRevalidate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    /**
     * @param  array<int, string>  $paths
     */
    public function __construct(
        public int $siteId,
        public array $paths,
    ) {}

    public function handle(): void
    {
        $site = Site::find($this->siteId);
        if (! $site || ! $site->revalidate_url || ! $site->revalidate_secret) {
            Log::info('revalidate.skip', ['site' => $this->siteId, 'reason' => 'no_url_or_secret']);

            return;
        }

        $response = Http::timeout(config('services.revalidate.timeout', 5))
            ->acceptJson()
            ->post($site->revalidate_url, [
                'secret' => $site->revalidate_secret,
                'paths' => $this->paths,
            ]);

        if ($response->failed()) {
            Log::warning('revalidate.failed', [
                'site' => $site->domain,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $response->throw();   // retry via queue
        }
    }
}
