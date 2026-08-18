<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رفع یک خلأ در طراحی اولیه‌ی جدول accounts: تا پیش از این، یوزرنیمی که
 * موقع ساخت اکانت روی پنل واقعی تولید می‌شد هیچ‌جا ذخیره نمی‌شد، بنابراین
 * AccountService::renew()/getAccount()/deleteAccount() امکان اشاره‌ی
 * دقیق به همان اکانت روی پنل را نداشتند.
 *
 * panel_username   → شناسه‌ی اصلی روی پنل (Marzban/PasarGuard: username،
 *                    Sanaei: مقدار email کلاینت که همان username ماست)
 * panel_client_uuid→ فقط پنل‌هایی مثل Sanaei/X-UI لازم دارند (id کلاینت
 *                    داخل inbound)؛ برای Marzban/PasarGuard همیشه null
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('panel_username')->nullable()->after('protocol_id');
            $table->string('panel_client_uuid')->nullable()->after('panel_username');

            $table->unique(['server_panel_id', 'panel_username']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['server_panel_id', 'panel_username']);
            $table->dropColumn(['panel_username', 'panel_client_uuid']);
        });
    }
};
