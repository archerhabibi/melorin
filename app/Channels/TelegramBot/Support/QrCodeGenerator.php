<?php

namespace App\Channels\TelegramBot\Support;

use App\Models\Account;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * تولید QR Code برای تحویل کانفیگ اکانت (همان قابلیتی که در mirzabot برای
 * راحتیِ اسکن کردن کانفیگ توسط کاربر داخل اپ‌های VPN وجود دارد).
 *
 * نیازمند پکیج composer require endroid/qr-code.
 *
 * توجه — چرا endroid و نه simplesoftwareio/simple-qrcode:
 * simplesoftwareio/simple-qrcode برای خروجی PNG صرفاً از بک‌اند Imagick
 * پشتیبانی می‌کند (نه GD)، و چون install.sh/update.sh فقط php-gd را نصب
 * می‌کنند (نه php-imagick)، در عمل با خطای
 * «You need to install the imagick extension to use this back end»
 * کرش می‌کرد — یعنی پیام متنیِ خرید با موفقیت می‌رسید ولی sendPhoto بعدی
 * هرگز اجرا نمی‌شد (چون Exception قبلش پرتاب می‌شد) و کاربر هیچ‌وقت
 * عکس QR را دریافت نمی‌کرد. endroid/qr-code دقیقاً همان پکیجی است که
 * خودِ ربات میرزا هم استفاده می‌کند و PngWriter آن با GD کار می‌کند —
 * اکستنشنی که از قبل روی سرور نصب است، بدون نیاز به نصب Imagick.
 */
class QrCodeGenerator
{
    /**
     * متن قابل‌اسکن اکانت را برمی‌گرداند — طبق درخواست صریح، این همیشه
     * لینک سابسکریپشن است (ستون subscription_url روی Account)، نه کانفیگ
     * خام تک‌پروتکلی؛ چون کلاینت‌های VPN از روی لینک سابسکریپشن خودشان
     * را به‌روز نگه می‌دارند. برای اکانت‌های قدیمی‌تر که قبل از این تغییر
     * ساخته شده‌اند و subscription_url ندارند، چند fallback از فرمت‌های
     * قدیمی‌تر config_data هم امتحان می‌شود تا کاربر کاملاً بی‌جواب نماند.
     */
    public function scannableTextFor(Account $account): string
    {
        if ($account->subscription_url) {
            return $account->subscription_url;
        }

        $raw = is_string($account->config_data)
            ? json_decode($account->config_data, true)
            : $account->config_data;

        return $raw['link']
            ?? $raw['configLink']
            ?? $raw['raw']['configLink'] ?? $raw['raw']['link'] ?? $raw['raw']['subscription_url']
            ?? (string) $account->config_data;
    }

    /** خروجی PNG باینری QR Code، آماده‌ی ارسال با sendPhoto */
    public function pngFor(Account $account): string
    {
        $result = (new Builder(
            writer: new PngWriter,
            data: $this->scannableTextFor($account),
            size: 400,
            margin: 10,
        ))->build();

        return $result->getString();
    }
}
