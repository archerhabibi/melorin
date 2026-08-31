<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رفع باگ واقعی: MiscHandler::testAccount() هنگام خرید اکانت تست،
 * AccountService::purchase(..., salesChannel: 'test_account', ...) را
 * صدا می‌زند که در نهایت 'test_account' را در ستون orders.sales_channel
 * درج می‌کند. اما این ستون یک ENUM سخت‌گیرانه با مقادیر
 * ('main_bot', 'reseller_bot', 'website', 'panel') است (نگاه کنید:
 * 2024_01_01_000016_create_orders_table.php) — پس هر تلاش برای دریافت
 * اکانت تست با خطای دیتابیس («Data truncated for column
 * 'sales_channel'») شکست می‌خورد، دقیقاً همان لحظه‌ای که Order::create()
 * داخل AccountService::purchase() اجرا می‌شود.
 *
 * چون migration اجراشده‌ی create_orders_table را دستکاری نمی‌کنیم،
 * مقدار جدید را با یک migration مجزا به ENUM اضافه می‌کنیم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('sales_channel', ['main_bot', 'reseller_bot', 'website', 'panel', 'test_account'])
                ->default('main_bot')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('sales_channel', ['main_bot', 'reseller_bot', 'website', 'panel'])
                ->default('main_bot')
                ->change();
        });
    }
};
