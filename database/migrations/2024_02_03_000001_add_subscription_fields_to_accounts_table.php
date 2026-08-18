<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست صریح: به‌جای تحویل خودِ کانفیگ (لینک‌های تک‌تک vless/vmess/...)،
 * برای همه‌ی خریدها باید لینک سابسکریپشن (و QR همان لینک) تحویل داده شود —
 * چون کلاینت‌های VPN از روی این لینک می‌توانند خودشان را به‌روز نگه دارند
 * (تغییر حجم/انقضا/inbound بدون نیاز به دادن کانفیگ تازه به کاربر).
 *
 * subscription_id → فقط برای پنل‌هایی مثل Sanaei/3X-UI پر می‌شود (همان
 *                   مقدار subId کلاینت که سرویس Subscription پنل با آن
 *                   کار می‌کند). برای Marzban/PasarGuard که پنل خودش یک
 *                   subscription_url کامل برمی‌گرداند، این ستون خالی
 *                   می‌ماند.
 * subscription_url → لینک نهایی و آماده‌ی تحویل به کاربر (برای QR Code و
 *                   نمایش متنی) — مستقل از این‌که پنل چطور آن را ساخته.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('subscription_id')->nullable()->after('panel_client_uuid');
            $table->text('subscription_url')->nullable()->after('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['subscription_id', 'subscription_url']);
        });
    }
};
