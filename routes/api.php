<?php

use App\Http\Controllers\Api\V1\Auth\ClientCredentialController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\RechargeController;
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

        Route::post('/recharge', [RechargeController::class, 'store'])
            ->name('recharge');

        Route::post('/payment/create', [\App\Http\Controllers\Api\V1\PaymentController::class, 'store'])
            ->name('payment.create');

        Route::post('/bill-payment/credit-card/bill-fetch', [\App\Http\Controllers\Api\V1\CreditCardBillPaymentController::class, 'fetch'])
            ->name('bill-payment.credit-card.fetch');

        Route::post('/bill-payment/credit-card/bill-pay', [\App\Http\Controllers\Api\V1\CreditCardBillPaymentController::class, 'pay'])
            ->name('bill-payment.credit-card.pay');

        Route::post('/plan/operator-fetch', [\App\Http\Controllers\Api\V1\PlanApiController::class, 'operatorFetch'])
            ->name('plan.operator-fetch');

        Route::post('/plan/operator-plan-fetch', [\App\Http\Controllers\Api\V1\PlanApiController::class, 'operatorPlanFetch'])
            ->name('plan.operator-plan-fetch');

        Route::post('/plan/dth-plan-fetch', [\App\Http\Controllers\Api\V1\PlanApiController::class, 'dthPlanFetch'])
            ->name('plan.dth-plan-fetch');

        Route::post('/plan/dth-info', [\App\Http\Controllers\Api\V1\PlanApiController::class, 'dthInfo'])
            ->name('plan.dth-info');

        Route::get('/products/categories', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'categories'])
            ->name('products.categories');

        Route::get('/products', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'index'])
            ->name('products.index');

        Route::post('/products/details', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'details'])
            ->name('products.details');

        Route::post('/leads', [\App\Http\Controllers\Api\V1\LeadApiController::class, 'store'])
            ->name('leads.store');

        Route::get('/leads/status', [\App\Http\Controllers\Api\V1\LeadApiController::class, 'status'])
            ->name('leads.status');
    });
});
