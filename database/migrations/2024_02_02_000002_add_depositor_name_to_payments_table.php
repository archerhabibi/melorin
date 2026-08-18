<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست واقعی: کاربر ربات ممکن است از کارتِ شخص دیگری (خانواده/دوست)
 * واریز کارت‌به‌کارت انجام دهد؛ اسم کاربر تلگرام او لزوماً همان اسم روی
 * صورتحساب بانکی نیست. بدون این فیلد، ادمین هنگام تایید رسید هیچ راهی
 * برای تطبیق واریزی با اسم واقعی روی حساب بانکی نداشت.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('depositor_name')->nullable()->after('receipt_image');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('depositor_name');
        });
    }
};
