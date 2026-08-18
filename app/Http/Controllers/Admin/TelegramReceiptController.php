<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;

/**
 * پروکسی نمایش تصویر رسید کارت‌به‌کارت. چون receipt_image فقط یک
 * file_id تلگرام است (نه URL عمومی)، پنل Filament نمی‌تواند مستقیم
 * <img src="..."> بدهد — این route فایل را از Telegram Bot API واکشی و
 * به مرورگر ادمین استریم می‌کند.
 *
 * باگ نسخه‌ی قبلی: از یک متد $telegram->download() استفاده می‌کرد که
 * اصلاً در irazasyed/telegram-bot-sdk وجود ندارد (خطای واقعی روی سرور:
 * «Method [download] does not exist»). روش درست طبق خودِ SDK:
 * getFile() فقط file_path را برمی‌گرداند؛ دانلود واقعی باید با یک
 * درخواست HTTP جدا به https://api.telegram.org/file/bot<TOKEN>/<path>
 * انجام شود.
 *
 * محافظت‌شده با میدل‌ور auth:admin (در routes/admin.php ثبت شده).
 */
class TelegramReceiptController
{
    public function __invoke(Payment $payment, Api $telegram): Response
    {
        abort_if(! $payment->receipt_image, 404);

        $file = $telegram->getFile(['file_id' => $payment->receipt_image]);
        $filePath = $file->getFilePath() ?? null;

        abort_if(! $filePath, 404, 'فایل رسید روی سرورهای تلگرام پیدا نشد (ممکن است منقضی شده باشد).');

        $token = config('telegram.bots.main.token');
        $downloadUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";

        $response = Http::timeout(15)->get($downloadUrl);

        abort_if(! $response->successful(), 502, 'دانلود رسید از تلگرام ناموفق بود.');

        return response(
            $response->body(),
            200,
            ['Content-Type' => $response->header('Content-Type') ?: 'image/jpeg']
        );
    }
}
