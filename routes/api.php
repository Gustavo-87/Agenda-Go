<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/availability', [AppointmentController::class, 'availability']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
});

Route::get('/webhooks/whatsapp',
    [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);

Route::post('/webhooks/whatsapp',
    [\App\Http\Controllers\WhatsAppWebhookController::class, 'receive']);
