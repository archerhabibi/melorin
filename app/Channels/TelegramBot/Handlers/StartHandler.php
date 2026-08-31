<?php

namespace App\Channels\TelegramBot\Handlers;

use App\Channels\TelegramBot\Support\ConversationState;
use App\Channels\TelegramBot\Support\Keyboards;
use App\Models\TestAccountSetting;
use App\Models\User;
use Telegram\Bot\Api;

/**
 * دستور /start و بازگشت به منوی اصلی. طبق بند ۱۳ سند، اگر کاربر از طریق
 * لینک دعوت (deep-link payload = آیدی معرف) وارد شده و قبلاً معرفی‌شده
 * نبوده، referrer_id ثبت می‌شود.
 */
class StartHandler
{
    public function __construct(protected Api $telegram, protected ConversationState $state)
    {
    }

    public function handle(int $chatId, User $user, ?string $payload): void
    {
        $this->state->reset($chatId);

        if ($payload && is_numeric($payload) && ! $user->referrer_id && (int) $payload !== $user->id) {
            $referrer = User::find((int) $payload);
            if ($referrer) {
                $user->update(['referrer_id' => $referrer->id]);
            }
        }

        $this->showMainMenu($chatId);
    }

    public function showMainMenu(int $chatId): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'به ربات ملورین خوش آمدید 🌐',
            'reply_markup' => Keyboards::mainMenu(TestAccountSetting::current()->isUsable()),
        ]);
    }
}
