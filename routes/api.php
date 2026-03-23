<?php

use App\Http\Controllers\Webhooks\HitPayWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/hitpay', HitPayWebhookController::class)
    ->name('api.webhooks.hitpay');