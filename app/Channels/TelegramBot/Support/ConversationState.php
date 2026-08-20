<?php

namespace App\Channels\TelegramBot\Support;

use App\Models\TelegramConversationState;
use App\Models\User;

/**
 * مدیریت وضعیت مکالمه‌ی هر چت تلگرام. این کلاس تنها یک لایه‌ی نازک روی
 * جدول telegram_conversation_states است و هیچ منطق کسب‌وکاری در آن نیست —
 * منطق واقعی همیشه داخل Core Services انجام می‌شود (بند ۳۴ سند).
 */
class ConversationState
{
    public const IDLE = 'idle';

    public const BUY_CHOOSE_CATEGORY = 'buy:choose_category';

    public const BUY_CHOOSE_PRODUCT = 'buy:choose_product';

    public const BUY_CHOOSE_SERVER = 'buy:choose_server';

    // فقط وقتی سبد فروش روی naming_mode=custom تنظیم شده باشد؛ بعد از
    // انتخاب محصول (و سرور، اگر انتخاب سرور دستی بود) و قبل از خرید
    // نهایی، منتظر یک پیام متنیِ نام دلخواه از کاربر می‌ماند.
    public const BUY_AWAITING_CUSTOM_NAME = 'buy:awaiting_custom_name';

    public const WALLET_AWAITING_AMOUNT = 'wallet:awaiting_amount';

    public const WALLET_CHOOSE_METHOD = 'wallet:choose_method';

    public const WALLET_AWAITING_RECEIPT = 'wallet:awaiting_receipt';

    // بعد از عکس رسید، نام صاحب کارتِ واریزکننده پرسیده می‌شود — چون
    // ممکن است کاربر از کارت شخص دیگری (خانواده/دوست) واریز کرده باشد
    // و ادمین برای تطبیق با صورتحساب بانکی به این اسم نیاز دارد (فرق
    // دارد با نام کاربر تلگرام).
    public const WALLET_AWAITING_DEPOSITOR_NAME = 'wallet:awaiting_depositor_name';

    public const SUPPORT_AWAITING_MESSAGE = 'support:awaiting_message';

    public function find(int $chatId): TelegramConversationState
    {
        return TelegramConversationState::firstOrCreate(
            ['telegram_chat_id' => $chatId],
            ['step' => self::IDLE, 'payload' => []]
        );
    }

    public function set(int $chatId, string $step, array $payload = [], ?User $user = null): TelegramConversationState
    {
        $state = $this->find($chatId);
        $state->update([
            'step' => $step,
            'payload' => $payload,
            'user_id' => $user?->id ?? $state->user_id,
        ]);

        return $state;
    }

    public function reset(int $chatId): void
    {
        $this->find($chatId)->update(['step' => self::IDLE, 'payload' => []]);
    }

    public function mergePayload(int $chatId, array $extra): TelegramConversationState
    {
        $state = $this->find($chatId);
        $state->update(['payload' => array_merge($state->payload ?? [], $extra)]);

        return $state;
    }
}
