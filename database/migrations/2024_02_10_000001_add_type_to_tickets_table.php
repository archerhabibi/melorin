<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ستون جدید و افزودنی (نه ویرایش migration قبلی، طبق قرارداد پروژه):
 * «🤖 درخواست ربات نماینده و همکاری» از این نسخه به بعد دیگر یک پیام
 * خام broadcast‌شده به ادمین‌ها نیست، بلکه یک تیکتِ واقعی است — طبق
 * درخواست صریح («می‌توان آن را نوعی تیکت در نظر گرفت») — تا از همان
 * پنل تیکت‌ها و همان زیرساخت گفتگوی دوطرفه قابل‌پاسخ باشد. این ستون
 * فقط برای تفکیک نمایش/فیلتر در پنل است.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('type', ['support', 'reseller_request'])
                ->default('support')
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
