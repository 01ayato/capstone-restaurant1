<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\TablesConfigurationsController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/tables_configurations', [TablesConfigurationsController::class, 'index'])
        ->name('page.smoke_n_grill.tables_configurations.index');

    Route::get('/smoke_n_grill/tables_configurations/monitor', [TablesConfigurationsController::class, 'monitor'])
        ->name('page.smoke_n_grill.tables_configurations.monitor');

    Route::post('/smoke_n_grill/tables_configurations/groups', [TablesConfigurationsController::class, 'storeGroup'])
        ->name('page.smoke_n_grill.tables_configurations.groups.store');

    Route::patch('/smoke_n_grill/tables_configurations/groups/{group}', [TablesConfigurationsController::class, 'updateGroup'])
        ->name('page.smoke_n_grill.tables_configurations.groups.update');

    Route::delete('/smoke_n_grill/tables_configurations/groups/{group}', [TablesConfigurationsController::class, 'destroyGroup'])
        ->name('page.smoke_n_grill.tables_configurations.groups.destroy');

    Route::post('/smoke_n_grill/tables_configurations/groups/{group}/map', [TablesConfigurationsController::class, 'uploadMap'])
        ->name('page.smoke_n_grill.tables_configurations.groups.map');

    Route::post('/smoke_n_grill/tables_configurations/groups/{group}/tables', [TablesConfigurationsController::class, 'storeTable'])
        ->name('page.smoke_n_grill.tables_configurations.tables.store');

    Route::patch('/smoke_n_grill/tables_configurations/tables/{table}', [TablesConfigurationsController::class, 'updateTable'])
        ->name('page.smoke_n_grill.tables_configurations.tables.update');

    Route::delete('/smoke_n_grill/tables_configurations/tables/{table}', [TablesConfigurationsController::class, 'destroyTable'])
        ->name('page.smoke_n_grill.tables_configurations.tables.destroy');

    Route::patch('/smoke_n_grill/tables_configurations/tables/{table}/position', [TablesConfigurationsController::class, 'updateTablePosition'])
        ->name('page.smoke_n_grill.tables_configurations.tables.position');

    Route::patch('/smoke_n_grill/tables_configurations/tables/{table}/occupancy', [TablesConfigurationsController::class, 'updateOccupancy'])
        ->name('page.smoke_n_grill.tables_configurations.tables.occupancy');
});
