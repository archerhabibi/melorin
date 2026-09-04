<?php

namespace App\Channels\TelegramBot\Handlers;

use App\Channels\TelegramBot\Support\ConversationState;
use App\Events\TicketCreated;
use App\Models\Account;
use App\Models\AffiliateSetting;
use App\Models\TestAccountSetting;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Core\AccountService;
use Telegram\Bot\Api;

/**
 * پیاده‌سازی سبک‌وزنِ باقی‌مانده‌ی منوی اصلی (بند ۳.۱): زیرمجموعه‌گیری،
 * پشتیبانی و قوانین. این‌ها MVP هستند؛ گزارش کامل کمیسیون (بند ۱۳) و
 * تیکتینگ کامل (بند ۱۶) باید در فاز بعدی تکمیل شوند — فعلاً فقط ثبت اولیه.
 */
class MiscHandler
{
    public function __construct(
        protected Api $telegram,
        protected ConversationState $state,
        protected AccountService $accountService,
        // برای reuse مستقیم deliverConfig() به‌جای کپی/تکرار منطق تحویل
        // کانفیگ+QR که در BuyAccountHandler از قبل تست‌شده وجود دارد
        protected BuyAccountHandler $buyAccountHandler,
    ) {}

    public function referral(int $chatId, User $user): void
    {
        $settings = AffiliateSetting::current();
        $botUsername = config('telegram.bots.main.username', 'MelorinBot');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🎁 لینک دعوت اختصاصی شما:\nhttps://t.me/{$botUsername}?start={$user->id}\n\n"
                ."تعداد زیرمجموعه‌ها: {$user->referredUsers()->count()}\n"
                .'پاداش خرید اول: '.number_format((float) $settings->customer_bonus_amount).' تومان به شما، '
                .number_format((float) $settings->referrer_bonus_amount)." تومان به معرف\n"
                ."کمیسیون خریدهای بعدی: {$settings->commission_percent}٪",
        ]);
    }

    public function rules(int $chatId): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📜 قوانین خرید:\n\n۱. پس از خرید امکان بازگشت وجه وجود ندارد مگر در صورت خرابی سرویس.\n۲. اکانت‌ها فقط برای استفاده‌ی شخصی هستند.\n۳. برای هرگونه مشکل با پشتیبانی در تماس باشید.",
        ]);
    }

    /**
     * پیاده‌سازی کامل «اکانت تست» (طبق سند ۰۷: قابلیت باید یا کامل باشد
     * یا از منو مخفی — نه پیام «به‌زودی» دائمی). دکمه‌ی منو خودش هم فقط
     * وقتی نشان داده می‌شود که تنظیمات usable باشد (ر.ک.
     * Keyboards::mainMenu)، پس رسیدن به اینجا با تنظیمات غیرفعال باید
     * نادر باشد — ولی برای اطمینان دوباره چک می‌شود، چون کاربر می‌تواند
     * متن دکمه را دستی هم بفرستد.
     */
    public function testAccount(int $chatId, User $user): void
    {
        $settings = TestAccountSetting::current();

        if (! $settings->isUsable()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'دریافت اکانت تست در حال حاضر غیرفعال است.',
            ]);

            return;
        }

        $usedCount = Account::query()
            ->where('user_id', $user->id)
            ->where('is_test', true)
            ->count();

        if ($usedCount >= $settings->max_per_user) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "شما قبلاً از سهمیه‌ی اکانت تست خود استفاده کرده‌اید (حداکثر مجاز: {$settings->max_per_user} بار).",
            ]);

            return;
        }

        $product = $settings->product;

        if (! $product || $product->status !== 'active') {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'دریافت اکانت تست موقتاً در دسترس نیست — لطفاً بعداً تلاش کنید.',
            ]);

            return;
        }

        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => '🧪 در حال ساخت اکانت تست شما...']);

        try {
            $account = $this->accountService->purchase(
                $user,
                $product,
                salesChannel: 'test_account',
                isTest: true,
                testTrafficMb: $settings->traffic_mb,
                testDurationHours: $settings->duration_hours,
            );
        } catch (\RuntimeException $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "دریافت اکانت تست ناموفق بود: {$e->getMessage()}\nلطفاً بعداً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.",
            ]);

            return;
        }

        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => '✅ اکانت تست شما ساخته شد.']);
        $this->buyAccountHandler->deliverConfig($chatId, $account);
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

        // پیش از این نسخه، تیکت فقط در دیتابیس ثبت می‌شد و هیچ‌کس مطلع
        // نمی‌شد (بند «پشتیبانی نیمه‌کاره»). این رویداد به ادمین‌های
        // تلگرام اطلاع می‌دهد که پاسخ باید از پنل داده شود.
        TicketCreated::dispatch($ticket->fresh());
    }
}
