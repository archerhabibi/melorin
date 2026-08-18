<?php

namespace App\Services\Core\Panels\Concerns;

use App\Models\ServerPanel;

/**
 * باگ واقعی که در تست روی سرور رخ داد (و اول در SanaeiDriver پیدا و رفع
 * شد): وقتی ادمین آدرس پنل را با پورت/مسیر پایه‌ی داخل‌شده وارد می‌کرد
 * (مثلاً «https://ss.ak47.help:2053/kharej» — دقیقاً شکلی که 3X-UI و
 * ربات میرزا هم توصیه می‌کنند)، متد قبلیِ baseUrl() همیشه «:{$panel->port}»
 * را هم به انتهای host اضافه می‌کرد و نتیجه می‌شد
 * «https://ss.ak47.help:2053/kharej:2053» — یک URL نامعتبر که هیچ
 * درخواستی به پنل نمی‌رساند.
 *
 * این باگ در MarzbanDriver و PasarGuardDriver هم عیناً تکرار شده بود
 * (هرکدام نسخه‌ی جدای خودشان از همین متد را داشتند) — اینجا در یک Trait
 * مشترک جمع شده تا سه بار جداگانه رفع/فراموش نشود.
 *
 * منطق: با parse_url بررسی می‌شود که آیا پورت از قبل داخل خودِ آدرس
 * هست؛ اگر هست، دوباره اضافه نمی‌شود. اگر نیست و فیلد port پر شده،
 * پورت را قبل از مسیر (نه بعدش) درج می‌کنیم تا با/بدون basePath هر دو
 * درست کار کنند.
 */
trait BuildsPanelBaseUrl
{
    protected function baseUrl(ServerPanel $panel): string
    {
        $raw = trim($panel->host);

        if (! preg_match('#^https?://#i', $raw)) {
            $raw = 'https://' . $raw;
        }

        $parsed = parse_url(rtrim($raw, '/'));

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $portInAddress = $parsed['port'] ?? null;

        $port = $portInAddress ?: ($panel->port ?: null);

        return $port
            ? "{$scheme}://{$host}:{$port}{$path}"
            : "{$scheme}://{$host}{$path}";
    }

    /**
     * آدرس عمومیِ سرویس Subscription. در بسیاری از نصب‌ها این سرویس روی
     * پورت/مسیر کاملاً جدایی از API مدیریت پنل سرو می‌شود (مثلاً 3X-UI
     * به‌صورت پیش‌فرض API روی پورت پنل و Subscription روی پورت ۲۰۹۶ است)
     * و ممکن است پشت دامنه/CDN دیگری هم باشد. اگر ادمین صریحاً
     * extra_settings['sub_base_url'] را برای این سرور ثبت کرده باشد،
     * همان استفاده می‌شود؛ در غیر این صورت میزبان مدیریت پنل (baseUrl)
     * به‌عنوان پیش‌فرض معقول (نه همیشه درست) به کار می‌رود — برای
     * Marzban/PasarGuard که معمولاً Subscription روی همان دامنه سرو
     * می‌شود، این پیش‌فرض معمولاً کافی است.
     */
    protected function subBaseUrl(ServerPanel $panel): string
    {
        return rtrim($panel->extra_settings['sub_base_url'] ?? $this->baseUrl($panel), '/');
    }

    /**
     * subscription_url ای که خودِ پنل برمی‌گرداند را به یک URL کامل و
     * قابل‌تحویل تبدیل می‌کند — چون Marzban/PasarGuard معمولاً فقط مسیر
     * نسبی («/sub/username/token») برمی‌گردانند، نه آدرس کامل.
     */
    protected function toAbsoluteSubscriptionUrl(ServerPanel $panel, ?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return $this->subBaseUrl($panel) . '/' . ltrim($url, '/');
    }
}
