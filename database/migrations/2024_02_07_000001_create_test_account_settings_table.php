<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات تک‌رکوردی «اکانت تست» (سند 07 — بند «Incomplete Feature»:
 * این قابلیت باید یا کامل باشد یا از منو مخفی، نه پیام «به‌زودی» دائمی).
 *
 * به‌جای پیاده‌سازی یک مسیر ساخت اکانت جداگانه، همان AccountService::purchase()
 * موجود با یک Product با قیمت صفر (که ادمین همین‌جا انتخاب می‌کند) استفاده
 * می‌شود — یعنی انتخاب سرور، تحویل کانفیگ/QR و ثبت اکانت همگی از همان مسیر
 * تست‌شده‌ی خرید واقعی عبور می‌کنند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('test_account_settings')) {
            return;
        }
        Schema::create('test_account_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            // nullable چون تا وقتی ادمین یک محصول تست انتخاب نکرده، این
            // قابلیت باید غیرفعال بماند، نه با خطا مواجه شود
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('max_per_user')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_account_settings');
    }
};
