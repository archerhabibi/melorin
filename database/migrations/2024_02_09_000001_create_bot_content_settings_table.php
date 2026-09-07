<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات تک‌رکوردیِ محتوای متنی ربات — الگوی singleton دقیقاً مثل
 * affiliate_settings و test_account_settings. اولین فیلد: متن «قوانین
 * خرید و آموزش» که قبلاً در MiscHandler::rules() هاردکد بود و هیچ‌جای
 * پنل وب قابل‌تغییر نبود (طبق درخواست صریح).
 *
 * purchase_rules_text عمداً nullable است: تا وقتی ادمین چیزی ذخیره
 * نکرده، BotContentSetting::purchaseRulesText() همان متن پیش‌فرض فعلی
 * را برمی‌گرداند — یعنی رفتار موجود هیچ‌وقت بدون اقدام صریح ادمین عوض
 * نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_content_settings')) {
            return;
        }

        Schema::create('bot_content_settings', function (Blueprint $table) {
            $table->id();
            $table->text('purchase_rules_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_content_settings');
    }
};
