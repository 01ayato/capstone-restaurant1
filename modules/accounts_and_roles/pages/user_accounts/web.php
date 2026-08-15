<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\AccountsAndRoles\UserAccountsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/accounts_and_roles/user_accounts', [UserAccountsController::class, 'index'])
        ->name('page.accounts_and_roles.user_accounts.index');
    Route::post('/accounts_and_roles/user_accounts', [UserAccountsController::class, 'store'])
        ->name('page.accounts_and_roles.user_accounts.store');
    Route::patch('/accounts_and_roles/user_accounts/{user}', [UserAccountsController::class, 'update'])
        ->name('page.accounts_and_roles.user_accounts.update');
    Route::delete('/accounts_and_roles/user_accounts/{user}', [UserAccountsController::class, 'destroy'])
        ->name('page.accounts_and_roles.user_accounts.destroy');
});
