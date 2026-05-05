<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Block;
use App\Models\Page;
use App\Observers\BlockObserver;
use App\Observers\PageObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Block::observe(BlockObserver::class);

        if (class_exists(PageObserver::class)) {
            Page::observe(PageObserver::class);
        }
    }
}
