<?php

namespace App\Services\Core\Panels;

use App\Models\ServerPanel;

/**
 * قرارداد اختیاری (نه بخشی از PanelDriverInterface اصلی، تا درایورهایی
 * که هنوز پیاده‌سازی نکرده‌اند مجبور به تغییر نشوند — بند ۳۲ سند).
 * هر درایوری که این را پیاده‌سازی کند، دکمه‌ی «👁 وضعیت سرور» در پنل
 * سرورها/پنل‌ها برایش فعال می‌شود.
 */
interface SupportsServerStatus
{
    /**
     * خلاصه‌ی وضعیت لحظه‌ای سرور (CPU، رم، دیسک، ترافیک، وضعیت Xray و…)
     * به‌صورت آرایه‌ی ساده‌ی key => value برای نمایش مستقیم در پنل.
     */
    public function getServerStatus(ServerPanel $panel): array;
}
