<?php

use App\Http\Controllers\Api\V1\Auth\ClientCredentialController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/token', [TokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.token');

    Route::post('/auth/client-credentials', [ClientCredentialController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('auth.client-credentials');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [TokenController::class, 'me'])->name('auth.me');
        Route::delete('/auth/token', [TokenController::class, 'destroy'])->name('auth.token.destroy');
        Route::post('/auth/credentials/check', [ClientCredentialController::class, 'check'])
            ->name('auth.credentials.check');
    });
});
