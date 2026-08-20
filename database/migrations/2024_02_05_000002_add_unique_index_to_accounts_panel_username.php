<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دفاع در عمق برای منطق یکتاسازی نام کاربری در AccountService (چه
 * حالت random چه custom): چک "آیا این نام قبلاً استفاده شده؟" و درج
 * رکورد جدید دو عملیات جدا هستند، پس تئوریاً در بار همزمانِ خیلی بالا
 * دو خرید هم‌زمان می‌توانند هر دو یک نام را «آزاد» ببینند. این unique
 * index تضمین می‌کند حتی در آن حالت نادر، دومی با خطای DB شکست بخورد
 * (که مسیر موجود جبران/بازگشت وجه AccountService::purchase آن را
 * می‌گیرد) به‌جای این‌که دو اکانت با یک نام روی دیتابیس ثبت شوند.
 *
 * توجه: اگر روی دیتابیس فعلی از قبل دو یا چند اکانت با panel_username
 * یکسان ثبت شده باشد، این migration با خطای duplicate entry شکست
 * می‌خورد — قبل از اجرا با کوئری زیر چک کنید:
 *   SELECT panel_username, COUNT(*) c FROM accounts GROUP BY panel_username HAVING c > 1;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unique('panel_username');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['panel_username']);
        });
    }
};
