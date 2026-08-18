<?php

use App\Channels\TelegramBot\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
 * مسیر webhook ربات اصلی. توصیه: خود آدرس شامل یک توکن تصادفی هم باشد
 * (نه فقط secret header) تا حدس زدنش سخت‌تر شود؛ در واقعیت این را از
 * .env بخوانید و در artisan command ثبت وبهوک استفاده کنید.
 */
Route::post('/telegram/webhook/{token}', WebhookController::class)
    ->middleware('throttle:60,1')
    ->name('telegram.webhook');
