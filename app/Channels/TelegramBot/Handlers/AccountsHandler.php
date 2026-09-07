<?php

namespace App\Channels\TelegramBot\Handlers;

use App\Channels\TelegramBot\Support\Keyboards;
use App\Channels\TelegramBot\Support\QrCodeGenerator;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\User;
use App\Services\Core\AccountService;
use App\Services\Core\WalletService;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

/**
 * جریان «🔍 استعلام و تمدید اکانت» (بند ۱۰ سند): مشاهده‌ی اکانت‌های
 * فعال، تاریخ انقضا، حجم باقی‌مانده، وضعیت، تمدید، و دریافت مجدد کانفیگ.
 */
class AccountsHandler
{
    public function __construct(
        protected Api $telegram,
        protected AccountService $accountService,
        protected WalletService $walletService,
        protected QrCodeGenerator $qr,
    ) {}

    public function list(int $chatId, User $user): void
    {
        $accounts = $user->accounts()->with('product')->latest()->limit(10)->get();

        if ($accounts->isEmpty()) {
            $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => 'شما هنوز هیچ اکانتی خریداری نکرده‌اید.']);

            return;
        }

        foreach ($accounts as $account) {
            $this->sendSummary($chatId, $account);
        }
    }

    protected function sendSummary(int $chatId, Account $account): void
    {
        $statusFa = match ($account->status) {
            'active' => $account->isExpired() ? '⚠️ منقضی‌شده' : '✅ فعال',
            'expired' => '⚠️ منقضی‌شده',
            'disabled' => '⛔️ غیرفعال',
            'suspended' => '⏸ تعلیق‌شده',
            default => $account->status,
        };

        $text = "🔹 {$account->product?->name}\n"
            .'نام اکانت: '.htmlspecialchars((string) $account->panel_username, ENT_QUOTES)."\n"
            ."وضعیت: {$statusFa}\n"
            .'تاریخ انقضا: '.$account->expires_at->format('Y-m-d')."\n";

        if ($account->traffic_gb !== null) {
            $text .= 'حجم کل: '.number_format((float) $account->traffic_gb, 2)." گیگ\n";
            $text .= 'حجم باقی‌مانده: '.number_format((float) $account->remainingTrafficGb(), 2)." گیگ\n";
        } else {
            $text .= "حجم کل: نامحدود\n";
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => Keyboards::accountActions($account->id),
        ]);
    }

    public function sendConfig(int $chatId, User $user, int $accountId): void
    {
        $account = $user->accounts()->findOrFail($accountId);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => '🔗 لینک سابسکریپشن:'."\n".'<code>'.htmlspecialchars($this->qr->scannableTextFor($account), ENT_QUOTES).'</code>',
            'parse_mode' => 'HTML',
        ]);

        $this->telegram->sendPhoto([
            'chat_id' => $chatId,
            'photo' => InputFile::createFromContents($this->qr->pngFor($account), 'subscription.png'),
            'caption' => '📱 این QR را در اپلیکیشن VPN خود اسکن کنید تا سابسکریپشن به‌صورت خودکار اضافه شود.',
        ]);
    }

    /**
     * تمدید با همان مدت/حجم تعرفه‌ی فعلی و به قیمت فعلی همان محصول
     * (خرید حجم/مدت متفاوت در نسخه‌های بعدی اضافه می‌شود — بند ۱۰).
     *
     * نکته‌ی مهم: کسر کیف پول و تمدید روی پنل دو عملیات جدا هستند (برخلاف
     * AccountService::purchase که هر دو را در یک تراکنش انجام می‌دهد)،
     * چون renew() یک اکانت از قبل موجود را ویرایش می‌کند نه اکانت تازه
     * می‌سازد. اگر تمدید روی پنل شکست بخورد، باید مبلغ کسرشده را دستی
     * برگردانیم — وگرنه کاربر بدون دریافت خدمت، پول از دست داده است.
     */
    public function renew(int $chatId, User $user, int $accountId): void
    {
        $account = $user->accounts()->with('product')->findOrFail($accountId);
        $product = $account->product;

        $balanceBeforeRenewal = $this->walletService->balance($user);

        if ($balanceBeforeRenewal < (float) $product->price) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'برای تمدید، ابتدا کیف پول خود را شارژ کنید. هزینه‌ی تمدید: '.number_format((float) $product->price).' تومان',
            ]);

            return;
        }

        try {
            $this->walletService->purchase($user, (float) $product->price, $account, "تمدید اکانت #{$account->id}");
        } catch (InsufficientBalanceException) {
            $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => 'موجودی کیف پول کافی نیست.']);

            return;
        }

        try {
            $this->accountService->renew($account, $product->duration_days, $product->traffic_gb ? (int) $product->traffic_gb : null);
        } catch (\RuntimeException $e) {
            // تمدید روی پنل شکست خورد — مبلغی که همین الان کسر شد را برمی‌گردانیم
            // تا کاربر بدون دریافت خدمت متضرر نشود (همان تضمینی که در خرید اولیه هست).
            $this->walletService->refund($user, (float) $product->price, $account, 'بازگشت به دلیل خطای تمدید اکانت');

            $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "تمدید ناموفق بود: {$e->getMessage()}\nمبلغ به کیف پول شما بازگشت داده شد."]);

            return;
        }

        // طبق درخواست صریح: موجودی کیف پول قبل و بعد از تمدید نمایش داده
        // شود — balanceBeforeRenewal همان مبلغی است که هنوز کسر نشده بود
        // (نه یک پیام جدا قبل از انجام عملیات)، و موجودی فعلی را دوباره
        // می‌خوانیم چون purchase() همین الان آن را تغییر داده است.
        $balanceAfterRenewal = $this->walletService->balance($user);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ اکانت شما با موفقیت تمدید شد.\n\n"
                .'موجودی کیف پول قبل از تمدید: '.number_format($balanceBeforeRenewal)." تومان\n"
                .'موجودی کیف پول بعد از تمدید: '.number_format($balanceAfterRenewal).' تومان',
        ]);
        $this->sendSummary($chatId, $account->fresh());
    }
}
