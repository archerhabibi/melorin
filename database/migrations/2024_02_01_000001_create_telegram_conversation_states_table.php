<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * چون هر Webhook یک درخواست HTTP جدا و بی‌حالت (stateless) است، برای
 * پیاده‌سازی جریان‌های چندمرحله‌ای ربات (مثل «خرید: انتخاب دسته → انتخاب
 * محصول → پرداخت») باید مرحله‌ی فعلی هر کاربر را جایی نگه داریم. این جدول
 * همان نقش را بازی می‌کند (معادل چیزی که در mirzabot با فایل/کش موقت انجام
 * می‌شود، اینجا در دیتابیس مرکزی ملورین ذخیره می‌شود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_conversation_states', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_chat_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('step')->default('idle');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_conversation_states');
    }
};
