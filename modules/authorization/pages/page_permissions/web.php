<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\Authorization\PagePermissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/authorization/page_permissions', [PagePermissionsController::class, 'index'])
        ->name('page.authorization.page_permissions.index');
    Route::post('/authorization/page_permissions', [PagePermissionsController::class, 'update'])
        ->name('page.authorization.page_permissions.update');
});
