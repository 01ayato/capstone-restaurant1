<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\UserSettings\UsersListsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/user_settings/users_lists', [UsersListsController::class, 'index'])
        ->name('page.user_settings.users_lists.index');
    Route::post('/user_settings/users_lists', [UsersListsController::class, 'store'])
        ->name('page.user_settings.users_lists.store');
    Route::patch('/user_settings/users_lists/{user}', [UsersListsController::class, 'update'])
        ->name('page.user_settings.users_lists.update');
    Route::delete('/user_settings/users_lists/{user}', [UsersListsController::class, 'destroy'])
        ->name('page.user_settings.users_lists.destroy');
});
