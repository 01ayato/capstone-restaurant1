<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\AccountsAndRoles\RolesManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/accounts_and_roles/roles_management', [RolesManagementController::class, 'index'])
        ->name('page.accounts_and_roles.roles_management.index');
    Route::post('/accounts_and_roles/roles_management', [RolesManagementController::class, 'store'])
        ->name('page.accounts_and_roles.roles_management.store');
    Route::patch('/accounts_and_roles/roles_management/{role}', [RolesManagementController::class, 'update'])
        ->name('page.accounts_and_roles.roles_management.update');
    Route::delete('/accounts_and_roles/roles_management/{role}', [RolesManagementController::class, 'destroy'])
        ->name('page.accounts_and_roles.roles_management.destroy');
});
