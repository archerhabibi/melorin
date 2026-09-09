<?php

namespace Tests\Concerns;

use Mockery\MockInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

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

    /**
     * وقتی تست به‌جای fakeTelegram() یک Mockery::mock(Api::class) دستی
     * می‌سازد (چون می‌خواهد روی متن هر پیام withArgs() جداگانه بگذارد)،
     * sendMessage()/sendPhoto() نباید null برگردانند — امضای واقعی SDK
     * این متدها را ': Message' تایپ کرده، پس null باعث TypeError واقعی
     * می‌شود (نه فقط در تست). این متد یک نمونه‌ی حداقلی و معتبر از
     * Message برمی‌گرداند تا ->andReturn(...) همیشه type-safe بماند.
     */
    protected function fakeMessage(): Message
    {
        return new Message([
            'message_id' => 1,
            'date' => time(),
            'chat' => ['id' => 1, 'type' => 'private'],
            'text' => 'test',
        ]);
    }
}
