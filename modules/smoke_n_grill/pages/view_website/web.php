<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\ViewWebsiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/view_website', [ViewWebsiteController::class, 'index'])
        ->name('page.smoke_n_grill.view_website.index');
});
