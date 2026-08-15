<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\Authorization\ModulePermissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/authorization/module_permissions', [ModulePermissionsController::class, 'index'])
        ->name('page.authorization.module_permissions.index');
    Route::post('/authorization/module_permissions', [ModulePermissionsController::class, 'update'])
        ->name('page.authorization.module_permissions.update');
});
