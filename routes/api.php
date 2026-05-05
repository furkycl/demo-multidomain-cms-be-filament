<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public read API + lead capture
|--------------------------------------------------------------------------
| Bu router /api prefix'i altında çalışır (bootstrap/app.php).
| Frontends use these endpoints with their SITE_DOMAIN env var.
*/

Route::get('sites/{domain}', [SiteController::class, 'show']);

Route::prefix('sites/{domain}/{locale}')->group(function () {
    Route::get('pages/{slug?}', [SiteController::class, 'showPage'])
        ->where('slug', '.*');
});

Route::post('leads', [LeadController::class, 'store']);
