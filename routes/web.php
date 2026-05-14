<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\WordPressSsoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Sites
    |--------------------------------------------------------------------------
    */
    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [SiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}', [SiteController::class, 'show'])->name('sites.show');

    Route::post('/sites/{site}/retry-provisioning', [SiteController::class, 'retryProvisioning'])
        ->name('sites.retry-provisioning');

    Route::delete('/sites/{site}', [SiteController::class, 'destroy'])->name('sites.destroy');

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | WordPress SSO
    |--------------------------------------------------------------------------
    */
    Route::get('/sites/{site}/wordpress-sso', [WordPressSsoController::class, 'redirect'])
        ->name('sites.wordpress-sso');

    Route::get('/sites/{site}/wp-admin-login', [WordPressSsoController::class, 'redirect'])
        ->name('sites.wp-admin-login');
});

require __DIR__ . '/auth.php';