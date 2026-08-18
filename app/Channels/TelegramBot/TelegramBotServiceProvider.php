<?php

namespace App\Channels\TelegramBot;

use Illuminate\Support\ServiceProvider;

/**
 * Provider اختصاصیِ ربات اصلی (Channel\TelegramBot طبق سند معماری، بخش ۲).
 * این کلاس صرفاً یک Adapter است؛ هیچ منطق کسب‌وکاری اینجا نیست — طبق اصل
 * معماری بند ۳۴، فقط درخواست را به Core Services پاس می‌دهد.
 */
class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // عمداً اینجا Api::class را دستی bind نمی‌کنیم. پکیج
        // irazasyed/telegram-bot-sdk خودش به‌صورت خودکار (Laravel package
        // discovery) یک Provider دارد که Api::class را از طریق BotsManager
        // و کلید config('telegram.default') می‌سازد. قبلاً اینجا یک
        // singleton دستیِ موازی هم ساخته می‌شد که با آن Provider سر
        // Api::class رقابت می‌کرد — نتیجه‌اش دقیقاً همان خطای واقعیِ
        // «Bot [mybot] is not configured» بود که در تست روی سرور رخ داد.
        // با حذف این binding و تنظیم صحیح 'default' => 'main' در
        // config/telegram.php، فقط یک مسیر resolve باقی می‌ماند.
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../../routes/telegram.php');

        $this->mergeConfigFrom(__DIR__ . '/../../../config/telegram.php', 'telegram');
    }
}
