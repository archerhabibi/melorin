<?php

namespace App\Services\Core\Panels;

use InvalidArgumentException;

/**
 * افزودن پنل جدید (بند ۳۲ سند) فقط نیازمند یک خط جدید در $map است —
 * بدون تغییر در AccountService یا هر بخش دیگر از هسته.
 */
class PanelDriverFactory
{
    /*
     * توجه: مرزبان از منوی انتخاب پنل در پنل مدیریت (ServerPanelResource)
     * حذف شده چون فعلاً روی سرور واقعی تست نشده — اما اینجا در map ثبت
     * می‌ماند چون AccountServiceTest کامل به آن وابسته است (کد و تست‌های
     * مرزبان کاملاً درست کار می‌کنند؛ فقط ادمین فعلاً نمی‌تواند پنل جدید
     * از این نوع از داخل رابط کاربری اضافه کند).
     */
    protected static array $map = [
        'marzban' => MarzbanDriver::class,
        'pasarguard' => PasarGuardDriver::class,
        'sanaei' => SanaeiDriver::class,
    ];

    public static function make(string $panelType): PanelDriverInterface
    {
        $driverClass = self::$map[$panelType] ?? null;

        if (! $driverClass) {
            throw new InvalidArgumentException("درایوری برای نوع پنل '{$panelType}' ثبت نشده است.");
        }

        return app($driverClass);
    }
}
