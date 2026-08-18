<?php

namespace App\Channels\TelegramBot\Handlers;

use App\Channels\TelegramBot\Support\ConversationState;
use App\Models\AffiliateSetting;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Telegram\Bot\Api;

/**
 * پیاده‌سازی سبک‌وزنِ باقی‌مانده‌ی منوی اصلی (بند ۳.۱): زیرمجموعه‌گیری،
 * پشتیبانی و قوانین. این‌ها MVP هستند؛ گزارش کامل کمیسیون (بند ۱۳) و
 * تیکتینگ کامل (بند ۱۶) باید در فاز بعدی تکمیل شوند — فعلاً فقط ثبت اولیه.
 */
class MiscHandler
{
    public function __construct(protected Api $telegram, protected ConversationState $state)
    {
    }

    public function referral(int $chatId, User $user): void
    {
        $settings = AffiliateSetting::current();
        $botUsername = config('telegram.bots.main.username', 'MelorinBot');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎁 لینک دعوت اختصاصی شما:\nhttps://t.me/{$botUsername}?start={$user->id}\n\n"
                . "تعداد زیرمجموعه‌ها: {$user->referredUsers()->count()}\n"
                . "پاداش خرید اول: " . number_format((float) $settings->customer_bonus_amount) . " تومان به شما، "
                . number_format((float) $settings->referrer_bonus_amount) . " تومان به معرف\n"
                . "کمیسیون خریدهای بعدی: {$settings->commission_percent}٪",
        ]);
    }

    public function rules(int $chatId): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📜 قوانین خرید:\n\n۱. پس از خرید امکان بازگشت وجه وجود ندارد مگر در صورت خرابی سرویس.\n۲. اکانت‌ها فقط برای استفاده‌ی شخصی هستند.\n۳. برای هرگونه مشکل با پشتیبانی در تماس باشید.",
        ]);
    }

    public function testAccount(int $chatId): void
    {
        // TODO(بند ۱۵): جدول/مدل تنظیمات اکانت تست (فعال/غیرفعال، حجم،
        // مدت، محدودیت تعداد و فاصله‌ی زمانی) در دیتابیس فعلی وجود ندارد
        // و باید قبل از پیاده‌سازی کامل این بخش اضافه شود.
        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => 'دریافت اکانت تست به‌زودی فعال می‌شود.']);
    }

    public function supportStart(int $chatId, User $user): void
    {
        $this->state->set($chatId, ConversationState::SUPPORT_AWAITING_MESSAGE, [], $user);
        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => '🎧 پیام خود را برای پشتیبانی بنویسید:']);
    }

    public function supportSubmit(int $chatId, User $user, string $text): void
    {
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => mb_substr($text, 0, 60),
            'status' => 'open',
            'priority' => 'normal',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'message' => $text,
        ]);

        $this->state->reset($chatId);
        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "✅ تیکت شما با شماره‌ی #{$ticket->id} ثبت شد. پشتیبانی به‌زودی پاسخ می‌دهد."]);
    }
}
