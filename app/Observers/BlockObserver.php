<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\TriggerRevalidate;
use App\Models\Block;

class BlockObserver
{
    public function saved(Block $block): void
    {
        $page = $block->page()->with('site')->first();
        if (! $page || ! $page->site) {
            return;
        }

        TriggerRevalidate::dispatch(
            $page->site->id,
            [$page->slug]
        );
    }

    public function deleted(Block $block): void
    {
        $this->saved($block);
    }
}
