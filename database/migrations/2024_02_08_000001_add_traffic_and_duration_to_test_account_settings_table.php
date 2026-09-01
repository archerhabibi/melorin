<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * افزودن کنترل مستقیمِ حجم و مدت اعتبار اکانت تست، طبق بندهای «حجم
 * تست» و «مدت اعتبار» در سند نیازمندی (بخش ۱۵). تا قبل از این، حجم و
 * مدت اکانت تست از روی traffic_gb/duration_days همان محصولِ انتخاب‌شده
 * خوانده می‌شد؛ یعنی ادمین برای تغییر حجم/مدت اکانت تست مجبور بود
 * برود محصول را ویرایش کند (که روی محصولات دیگر هم اثر می‌گذاشت اگر
 * دوباره استفاده می‌شد). حالا این دو مستقیماً و مستقل در همین صفحه‌ی
 * تنظیمات اکانت تست قابل تعیین‌اند؛ محصول فقط برای تعیین دسته‌بندی/
 * پروتکل/سرورهای مجاز استفاده می‌شود.
 *
 * migration جدا (نه ویرایش 2024_02_07_000001) چون آن migration ممکن
 * است روی نصب‌های موجود از قبل اجرا شده باشد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_account_settings', function (Blueprint $table) {
            $table->unsignedInteger('traffic_mb')->default(500)->after('product_id');
            $table->unsignedInteger('duration_hours')->default(1)->after('traffic_mb');
        });
    }

    public function down(): void
    {
        Schema::table('test_account_settings', function (Blueprint $table) {
            $table->dropColumn(['traffic_mb', 'duration_hours']);
        });
    }
};
