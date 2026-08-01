<?php

use App\Http\Controllers\Webhooks\RazorpayWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        //
    });
});

Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handlePlatform'])->name('webhooks.razorpay');
Route::post('/webhooks/razorpay/{spa:razorpay_webhook_token}', [RazorpayWebhookController::class, 'handleForSpa'])->name('webhooks.razorpay.spa');
