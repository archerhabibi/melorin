<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ منسوخ: این migration برای معماری «سرویس Subscription خودِ ملورین»
 * (مسیر عمومی /sub/{token}) نوشته شده بود. آن معماری برگردانده شد،
 * چون در عمل لینکی که تحویل مشتری می‌داد همیشه روی دامنه‌ی خودِ ملورین
 * بود (مثلاً https://mel.ak47.help/sub/...) در حالی که فقط لینک واقعیِ
 * سرویس Subscription خودِ پنل (مثلاً https://ss.ak47.help:2096/sub/...
 * — همان الگویی که ربات میرزا با linksubx/subId هر پنل استفاده می‌کند)
 * روی کلاینت‌های VPN درست کار می‌کند. الان دوباره از extra_settings['sub_base_url']
 * هر پنل + subId استفاده می‌شود (به SanaeiDriver/BuildsPanelBaseUrl نگاه کنید).
 *
 * ستون subscription_token همچنان اینجا نگه داشته شده (فقط حذف نشده)
 * چون روی سروری که قبلاً این migration را اجرا کرده، حذفش نیازی ندارد
 * و بی‌ضرر است — دیگر توسط هیچ کدی خوانده/نوشته نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('subscription_token', 64)->nullable()->unique()->after('subscription_url');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['subscription_token']);
            $table->dropColumn('subscription_token');
        });
    }
};
