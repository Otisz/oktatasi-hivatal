<?php

declare(strict_types=1);

use App\Http\Controllers\ApplicantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{applicant}/score', [ApplicantController::class, 'score']);
});
