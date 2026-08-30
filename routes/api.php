<?php

use App\Http\Controllers\Webhooks\RazorpayWebhookController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        //
    });
});

Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handlePlatform'])->name('webhooks.razorpay');
Route::post('/webhooks/razorpay/{spa:razorpay_webhook_token}', [RazorpayWebhookController::class, 'handleForSpa'])->name('webhooks.razorpay.spa');

// One URL per spa (built from their own whatsapp_webhook_token), pasted into their own Meta App
// dashboard — see WhatsAppWebhookController's class docblock for why there's no shared endpoint.
Route::get('/webhooks/whatsapp/{spa:whatsapp_webhook_token}', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp/{spa:whatsapp_webhook_token}', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');
