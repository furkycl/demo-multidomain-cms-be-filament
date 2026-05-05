<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\TriggerRevalidate;
use App\Models\Page;

class PageObserver
{
    public function saved(Page $page): void
    {
        if (! $page->site_id) {
            return;
        }

        TriggerRevalidate::dispatch($page->site_id, [$page->slug]);
    }

    public function deleted(Page $page): void
    {
        $this->saved($page);
    }
}
