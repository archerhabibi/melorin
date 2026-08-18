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
    ) {
    }

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
            . "وضعیت: {$statusFa}\n"
            . 'تاریخ انقضا: ' . $account->expires_at->format('Y-m-d') . "\n"
            . ($account->traffic_gb ? "حجم کل: {$account->traffic_gb} گیگ" . ($account->traffic_used_gb ? " (مصرف‌شده: {$account->traffic_used_gb} گیگ)" : '') . "\n" : '');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => Keyboards::accountActions($account->id),
        ]);
    }

    public function sendConfig(int $chatId, User $user, int $accountId): void
    {
        $account = $user->accounts()->findOrFail($accountId);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🔗 لینک سابسکریپشن:\n`" . $this->qr->scannableTextFor($account) . '`',
            'parse_mode' => 'Markdown',
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

        if ($this->walletService->balance($user) < (float) $product->price) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'برای تمدید، ابتدا کیف پول خود را شارژ کنید. هزینه‌ی تمدید: ' . number_format((float) $product->price) . ' تومان',
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

        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => '✅ اکانت شما با موفقیت تمدید شد.']);
        $this->sendSummary($chatId, $account->fresh());
    }
}
