<?php

namespace Tests\Concerns;

use Mockery\MockInterface;
use Telegram\Bot\Api;

/**
 * پکیج irazasyed/telegram-bot-sdk برای درخواست‌های خودش (sendMessage,
 * sendPhoto و...) یک کلاینت Guzzle مستقل و داخلی می‌سازد — نه از طریق
 * Illuminate\Support\Facades\Http. یعنی Http::fake() هیچ اثری روی این
 * درخواست‌ها ندارد؛ بدون این trait، تست‌هایی که از هندلرهای تلگرام
 * عبور می‌کنند واقعاً به سرورهای تلگرام وصل می‌شوند و چون chat_id های
 * تست واقعی نیستند، با TelegramResponseException («Bad Request: chat
 * not found») شکست می‌خورند (باگ واقعی‌ای که در تست روی ویندوز دیده شد).
 *
 * این trait به‌جای آن، خودِ Telegram\Bot\Api::class را در کانتینر موک
 * می‌کند تا هیچ درخواست شبکه‌ای واقعی رخ ندهد. هیچ‌کدام از تست‌های
 * فعلی به مقدار بازگشتیِ sendMessage/sendPhoto وابسته نیستند، پس
 * shouldIgnoreMissing() (بازگرداندن null برای هر متد) کافی است.
 */
trait FakesTelegram
{
    protected function fakeTelegram(): MockInterface
    {
        return $this->mock(Api::class, function (MockInterface $mock) {
            $mock->shouldIgnoreMissing();
        });
    }
}
