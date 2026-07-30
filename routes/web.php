<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\ProfileController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Docs\OverviewController;
use App\Http\Controllers\Web\Onboarding\KycController;
use App\Http\Controllers\Web\Docs\PreviewDocumentationPageController;
use App\Http\Controllers\Web\Docs\PreviewEndpointController;
use App\Http\Controllers\Web\Docs\SearchController;
use App\Http\Controllers\Web\Docs\ShowCategoryController;
use App\Http\Controllers\Web\Docs\ShowChangelogController;
use App\Http\Controllers\Web\Docs\ShowChangelogEntryController;
use App\Http\Controllers\Web\Docs\ShowDocumentationPageController;
use App\Http\Controllers\Web\Docs\ShowEndpointController;
use App\Http\Controllers\Web\Docs\ShowExplorerController;
use App\Http\Controllers\Web\Docs\ShowFaqsController;
use App\Http\Controllers\Web\Docs\ShowGroupController;
use App\Http\Controllers\Web\Docs\ShowSdkHubController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Middleware\SetPortalContext;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/register/thanks', [RegisterController::class, 'thanks'])->name('register.thanks');
});

Route::get('/onboarding/kyc/submitted', [KycController::class, 'submitted'])->name('onboarding.kyc.submitted');
Route::get('/onboarding/kyc/{token}', [KycController::class, 'show'])->name('onboarding.kyc.show');
Route::post('/onboarding/kyc/{token}', [KycController::class, 'store'])->name('onboarding.kyc.store');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::post('/payment/initiate', [\App\Http\Controllers\Web\Payment\PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('/payment/redirect', [\App\Http\Controllers\Web\Payment\PaymentController::class, 'callback'])->name('payment.redirect');
});

Route::post('/payment/webhook', [\App\Http\Controllers\Web\Payment\PaymentController::class, 'webhook'])->name('payment.webhook');

Route::prefix('docs')->name('docs.')->middleware(SetPortalContext::class)->group(function (): void {
    Route::get('/', OverviewController::class)->name('overview');

    Route::get('/search', SearchController::class)
        ->middleware('throttle:60,1')
        ->name('search');

    Route::middleware(['auth', 'permission:docs.preview'])->group(function (): void {
        Route::get('/preview/endpoints/{endpoint}', PreviewEndpointController::class)
            ->name('preview.endpoints.show');
        Route::get('/preview/pages/{page}', PreviewDocumentationPageController::class)
            ->name('preview.pages.show');
    });

    Route::get('/{version}/explorer', ShowExplorerController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('explorer');

    Route::get('/{version}/faqs', ShowFaqsController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('faqs.index');

    Route::get('/{version}/changelog', ShowChangelogController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('changelog.index');

    Route::get('/{version}/changelog/{entry}', ShowChangelogEntryController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->where('entry', '[A-Za-z0-9._-]+')
        ->name('changelog.show');

    Route::get('/{version}/sdk', ShowSdkHubController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->name('sdk.index');

    Route::get('/{version}/categories/{category}', ShowCategoryController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->where('category', '[A-Za-z0-9._-]+')
        ->name('categories.show');

    Route::get('/{version}/groups/{group}', ShowGroupController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->where('group', '[A-Za-z0-9._-]+')
        ->name('groups.show');

    Route::get('/{version}/pages/{page}', ShowDocumentationPageController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->where('page', '[A-Za-z0-9._-]+')
        ->name('pages.show');

    Route::get('/{version}/endpoints/{endpoint}', ShowEndpointController::class)
        ->where('version', '[A-Za-z0-9._-]+')
        ->where('endpoint', '[A-Za-z0-9._-]+')
        ->name('endpoints.show');
});
