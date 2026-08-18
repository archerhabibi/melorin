<?php

use App\Http\Controllers\Admin\TelegramReceiptController;
use Illuminate\Support\Facades\Route;

/*
 * این فایل باید در AdminPanelProvider یا bootstrap/app.php لود شود
 * (مثلاً: $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php')
 * در boot() همان‌جایی که Filament ثبت می‌شود).
 */
Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/admin/payments/{payment}/receipt', TelegramReceiptController::class)
        ->name('admin.payments.receipt');
});
