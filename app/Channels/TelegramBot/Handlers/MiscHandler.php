<?php

namespace App\Channels\TelegramBot\Handlers;

use App\Channels\TelegramBot\Support\ConversationState;
use App\Events\TicketCreated;
use App\Models\Account;
use App\Models\AffiliateSetting;
use App\Models\BotContentSetting;
use App\Models\TestAccountSetting;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\Core\AccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $bonus = (float) $settings->referrer_bonus_amount;

        // قبلاً این پیام وعده‌ی «پاداش خرید اول» و «کمیسیون خریدهای بعدی» را
        // هم می‌داد، در حالی که هیچ‌کدام پرداخت نمی‌شدند (Commission هیچ‌جا
        // create نمی‌شد). فقط همان پاداشی که واقعاً در StartHandler پرداخت
        // می‌شود اینجا تبلیغ می‌شود.
        $bonusLine = $bonus > 0
            ? '🎁 با عضویت هر نفر از طریق این لینک، '.number_format($bonus)." تومان به کیف پول شما اضافه می‌شود.\n"
            : '';

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => '🎁 لینک دعوت اختصاصی شما:'."\n".'<code>https://t.me/'.htmlspecialchars($botUsername, ENT_QUOTES)."?start={$user->id}</code>\n\n"
                ."تعداد زیرمجموعه‌ها: {$user->referredUsers()->count()}\n"
                .$bonusLine,
            'parse_mode' => 'HTML',
        ]);
    }

    public function rules(int $chatId): void
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => BotContentSetting::current()->purchaseRulesText(),
        ]);
    }

    /**
     * «🤖 درخواست ربات نماینده و همکاری» — طبق درخواست صریح، دو مسیر
     * کاملاً جدا دارد:
     *
     * - ادمین ربات (isBotAdmin — یعنی خودِ صاحب/اپراتور همین استقرار
     *   Melorin): وارد جریان درخواست نمی‌شود، چون او که ربات را نصب
     *   کرده نیازی به «درخواست» ربات نماینده از خودش ندارد؛ چیزی که
     *   نیاز دارد نسخه‌ی Pro برای فعال‌سازی امکان ربات نماینده است — یک
     *   پیام ثابت با لینک نشان داده می‌شود.
     * - کاربر عادی: منتظر توضیحات می‌ماند (ConversationState) تا در
     *   resellerRequestSubmit برای همه‌ی ادمین‌های ربات فوروارد شود.
     */
    public function resellerRequestStart(int $chatId, User $user): void
    {
        if ($user->isBotAdmin()) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "برای دریافت و فعال‌سازی ربات نماینده نیاز به نسخه‌ی پرو می‌باشد.\nhttps://t.me/melorinpro\nبرای اطلاعات بیشتر به لینک بالا مراجعه شود.",
                ]);

            return;
        }

        $this->state->set($chatId, ConversationState::RESELLER_REQUEST_AWAITING_DESCRIPTION, [], $user);
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => '📌 توضیحات خود را برای ثبت درخواست نمایندگی ارسال نمایید.',
        ]);
    }

    /**
     * توضیحاتی که کاربر بعد از resellerRequestStart فرستاده را برای
     * همه‌ی config('telegram.admin_ids') فوروارد می‌کند — دقیقاً همان
     * الگویی که NotifyAdminsOfNewTicket برای تیکت‌های پشتیبانی از قبل
     * دارد (broadcast به همه‌ی ادمین‌های ربات، نه یک نفر مشخص)، طبق
     * تصمیم صریح که این درخواست‌ها به همه برسند نه فقط یک نفر. اگر هیچ
     * admin_id ای تنظیم نشده باشد، درخواست کاربر را بی‌صدا گم نمی‌کنیم —
     * در لاگ ثبت می‌شود تا لااقل قابل پیگیری دستی باشد، و به خودِ کاربر
     * همچنان پیام تأیید داده می‌شود (چون از دید او، او کارش را انجام
     * داده است).
     */
    public function resellerRequestSubmit(int $chatId, User $user, string $text): void
    {
        $this->state->reset($chatId);

        $adminIds = config('telegram.admin_ids', []);

        if ($adminIds) {
            foreach ($adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                    'text' => "🤖 درخواست جدید ربات نماینده و همکاری\n\n"
                        ."از: {$user->full_name} (telegram_id: {$user->telegram_id})\n\n"
                        ."توضیحات:\n{$text}",
                ]);
            }
        } else {
            Log::warning('Reseller request received but no TELEGRAM_ADMIN_IDS are configured.', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'description' => $text,
            ]);
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => '✅ درخواست شما ثبت و برای بررسی ارسال شد. به‌زودی با شما تماس گرفته می‌شود.',
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

        // طبق درخواست صریح: نام اکانت تست باید آیدی تلگرام یا نام تلگرام
        // کاربر باشد — مستقل از naming_mode سبد فروش (که برای خرید واقعی
        // است). آیدی عددی تلگرام همیشه در دسترس و همیشه یکتاست؛ نام
        // کامل فقط به‌عنوان fallback اگر به هر دلیلی خالی باشد.
        $testUsername = $user->telegram_id
            ? 'tg'.$user->telegram_id
            : Str::slug($user->full_name ?: 'test', '_');

        try {
            $account = $this->accountService->purchase(
                $user,
                $product,
                salesChannel: 'test_account',
                customUsername: $testUsername,
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
