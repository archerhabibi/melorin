<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درگاه‌های آنلاین (Zarinpal و مشابه) برای مرحله‌ی verify به یک شناسه‌ی
 * تراکنش (Authority) نیاز دارند که باید بین initiate() و callback حفظ
 * شود؛ gateway_response هم پاسخ خام آخرین تماس با درگاه را برای دیباگ و
 * سوابق نگه می‌دارد (بند ۳۱: ثبت سوابق عملیات حساس).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_reference')->nullable()->after('receipt_image')->index();
            $table->json('gateway_response')->nullable()->after('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_reference', 'gateway_response']);
        });
    }
};
