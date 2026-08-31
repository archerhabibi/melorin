<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اصلاح جای فیلد طبق بازخورد صریح: naming_mode باید ویژگیِ سبد فروش
 * (Category — دقیقاً همان چیزی که در کل کدبیس، از جمله متن پیام‌های
 * خودِ ربات و عنوان CategoryResource «مدیریت دسته‌بندی‌ها / سبد فروش»،
 * به این اسم شناخته می‌شود) باشد، نه محصول/تعرفه (Product).
 *
 * چون migration قبلی (afzoodan naming_mode به products) از قبل روی
 * دیتابیس production اجرا شده، آن را ویرایش نمی‌کنیم (migrationِ
 * اجراشده را دستکاری نکردن یک قاعده‌ی مهم است) و به‌جایش این
 * migrationِ جدید ستون را جابه‌جا می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('naming_mode', ['random', 'custom'])->default('random')->after('server_selection_mode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('naming_mode');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('naming_mode', ['random', 'custom'])->default('random')->after('sale_limit');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('naming_mode');
        });
    }
};
