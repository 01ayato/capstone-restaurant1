<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/feedback', [FeedbackController::class, 'index'])
        ->name('page.smoke_n_grill.feedback.index');
    Route::delete('/smoke_n_grill/feedback/{feedback}', [FeedbackController::class, 'destroy'])
        ->name('page.smoke_n_grill.feedback.destroy');
});
