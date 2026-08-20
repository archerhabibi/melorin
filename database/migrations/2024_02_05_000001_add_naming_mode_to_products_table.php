<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طبق درخواست صریح: هر سبد فروش (Product) باید بتواند مشخص کند نام
 * کاربری اکانت‌های ساخته‌شده از آن چگونه تولید شود:
 *
 * - random (پیش‌فرض، رفتار فعلی): نامی خودکار از الگوی
 *   «حروف اول نام سرور»_«حجم به گیگابایت»_«عدد ترتیبی» ساخته می‌شود
 *   (مثلاً ger_30_1، ger_30_2، ...) — به AccountService::randomUsernameBase
 *   نگاه کنید.
 * - custom: هنگام خرید، بعد از انتخاب دسته‌بندی/سبد فروش (و سرور، اگر
 *   دستی بود)، از کاربر یک نام لاتین دلخواه پرسیده می‌شود؛ اگر آن نام
 *   قبلاً استفاده شده بود، یک عدد ترتیبی به انتهایش اضافه می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('naming_mode', ['random', 'custom'])->default('random')->after('sale_limit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('naming_mode');
        });
    }
};
