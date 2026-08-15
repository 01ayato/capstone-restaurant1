<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\UserSettings\RolesManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/user_settings/roles_management', [RolesManagementController::class, 'index'])
        ->name('page.user_settings.roles_management.index');
    Route::post('/user_settings/roles_management', [RolesManagementController::class, 'store'])
        ->name('page.user_settings.roles_management.store');
    Route::patch('/user_settings/roles_management/{role}', [RolesManagementController::class, 'update'])
        ->name('page.user_settings.roles_management.update');
    Route::delete('/user_settings/roles_management/{role}', [RolesManagementController::class, 'destroy'])
        ->name('page.user_settings.roles_management.destroy');
});
