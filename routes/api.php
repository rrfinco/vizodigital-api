<?php

use App\Http\Controllers\Api\V1\Auth\ClientCredentialController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\CreditCardBillPaymentController;
use App\Http\Controllers\Api\V1\LeadApiController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlanApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\RechargeController;
use App\Http\Controllers\Api\V1\TaxationController;
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

        Route::post('/payment/create', [PaymentController::class, 'store'])
            ->name('payment.create');

        Route::post('/bill-payment/credit-card/bill-fetch', [CreditCardBillPaymentController::class, 'fetch'])
            ->name('bill-payment.credit-card.fetch');

        Route::post('/bill-payment/credit-card/bill-pay', [CreditCardBillPaymentController::class, 'pay'])
            ->name('bill-payment.credit-card.pay');

        Route::post('/plan/operator-fetch', [PlanApiController::class, 'operatorFetch'])
            ->name('plan.operator-fetch');

        Route::post('/plan/operator-plan-fetch', [PlanApiController::class, 'operatorPlanFetch'])
            ->name('plan.operator-plan-fetch');

        Route::post('/plan/dth-plan-fetch', [PlanApiController::class, 'dthPlanFetch'])
            ->name('plan.dth-plan-fetch');

        Route::post('/plan/dth-info', [PlanApiController::class, 'dthInfo'])
            ->name('plan.dth-info');

        Route::get('/products/categories', [ProductApiController::class, 'categories'])
            ->name('products.categories');

        Route::get('/products', [ProductApiController::class, 'index'])
            ->name('products.index');

        Route::post('/products/details', [ProductApiController::class, 'details'])
            ->name('products.details');

        Route::post('/leads/profile', [LeadApiController::class, 'profile'])
            ->name('leads.profile');

        Route::post('/leads', [LeadApiController::class, 'store'])
            ->name('leads.store');

        Route::get('/leads/status', [LeadApiController::class, 'status'])
            ->name('leads.status');

        Route::get('/taxation/services', [TaxationController::class, 'services'])
            ->name('taxation.services');
        Route::post('/taxation/clients', [TaxationController::class, 'storeClient'])
            ->name('taxation.clients.store');
        Route::get('/taxation/clients', [TaxationController::class, 'clients'])
            ->name('taxation.clients.index');
        Route::post('/taxation/orders', [TaxationController::class, 'storeOrder'])
            ->name('taxation.orders.store');
        Route::get('/taxation/orders', [TaxationController::class, 'orders'])
            ->name('taxation.orders.index');
        Route::get('/taxation/orders/{order}', [TaxationController::class, 'showOrder'])
            ->name('taxation.orders.show');
        Route::post('/taxation/orders/{order}/documents', [TaxationController::class, 'storeDocuments'])
            ->name('taxation.orders.documents.store');
        Route::get('/taxation/orders/{order}/documents', [TaxationController::class, 'documents'])
            ->name('taxation.orders.documents.index');
    });
});
