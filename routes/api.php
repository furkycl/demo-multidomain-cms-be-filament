<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public read-only API consumed by every frontend project.
|--------------------------------------------------------------------------
| Each frontend hits these endpoints with its SITE_DOMAIN env var.
*/

Route::prefix('sites/{domain}')->group(function () {
    Route::get('pages/{slug?}', [SiteController::class, 'showPage'])
        ->where('slug', '.*');
});
