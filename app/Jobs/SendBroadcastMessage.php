<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

/**
 * پیام همگانی (بند ۲۲ سند: «ارسال پیام» از پنل مدیریت). عمداً روی صف
 * اجرا می‌شود (نه synchronous داخل درخواست ادمین در
 * BroadcastMessage::send) — برای تعداد زیاد کاربر، ارسال synchronous
 * می‌تواند دقیقه‌ها طول بکشد و درخواست HTTP ادمین را timeout بزند.
 * Supervisor از قبل یک queue worker برای این پروژه اجرا می‌کند
 * (melorin-worker، ر.ک. install.sh)، پس نیازی به تنظیم جدیدی روی
 * سرور نیست.
 *
 * بین هر پیام یک وقفه‌ی کوتاه هست تا از محدودیت نرخِ ارسال API تلگرام
 * (حدود ۳۰ پیام در ثانیه در کل) رد نشویم. خطای یک کاربر (مثلاً بلاک‌
 * کردن ربات یا chat_id نامعتبر) کل ارسال را متوقف نمی‌کند — دقیقاً
 * همان الگوی try/catch داخل حلقه که در NotifyAdminsOfNewTicket و
 * NotifyAdminsOfTicketReply هم هست.
 */
class SendBroadcastMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    // ممکن است برای تعداد زیاد کاربر چند دقیقه طول بکشد؛ timeout
    // پیش‌فرض صف (معمولاً ۶۰ ثانیه) برای این کار مناسب نیست.
    public int $timeout = 3600;

    public function __construct(public readonly string $text) {}

    public function handle(Api $telegram): void
    {
        $sent = 0;
        $failed = 0;

        User::query()
            ->whereNotNull('telegram_id')
            ->select(['id', 'telegram_id'])
            ->cursor()
            ->each(function (User $user) use ($telegram, &$sent, &$failed) {
                try {
                    $telegram->sendMessage([
                        'chat_id' => $user->telegram_id,
                        'text' => $this->text,
                    ]);
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('ارسال پیام همگانی به یک کاربر ناموفق بود.', [
                        'user_id' => $user->id,
                        'telegram_id' => $user->telegram_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // ~۲۵ پیام در ثانیه — زیر سقف نرخِ تلگرام.
                usleep(40000);
            });

        Log::info("پیام همگانی ارسال شد: {$sent} موفق، {$failed} ناموفق.");
    }
}
