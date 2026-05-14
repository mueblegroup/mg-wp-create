<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\InvoiceController;
use App\Http\Controllers\SuperAdmin\PaymentAttemptController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\ProvisioningLogController;
use App\Http\Controllers\SuperAdmin\SiteController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\ThemeController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('users', UserController::class);
Route::resource('sites', SiteController::class);
Route::resource('plans', PlanController::class);
Route::resource('themes', ThemeController::class);
Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'show', 'edit', 'update']);
Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
Route::resource('payment-attempts', PaymentAttemptController::class)->only(['index', 'show']);
Route::resource('provisioning-logs', ProvisioningLogController::class)->only(['index', 'show']);

Route::post('sites/{site}/retry-provisioning', [SiteController::class, 'retryProvisioning'])
    ->name('sites.retry-provisioning');

Route::post('sites/{site}/suspend', [SiteController::class, 'suspend'])
    ->name('sites.suspend');

Route::post('sites/{site}/unsuspend', [SiteController::class, 'unsuspend'])
    ->name('sites.unsuspend');