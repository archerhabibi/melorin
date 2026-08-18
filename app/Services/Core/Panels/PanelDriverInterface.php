<?php

namespace App\Services\Core\Panels;

use App\DataTransferObjects\PanelAccountRequest;
use App\DataTransferObjects\PanelAccountResult;
use App\Models\ServerPanel;

/**
 * قرارداد مشترک همه‌ی درایورهای پنل (Marzban, Sanaei, PasarGuard, ...).
 *
 * طبق بند ۳۲ سند نیازمندی («امکان اضافه کردن پنل جدید بدون تغییرات اساسی
 * در هسته»)، افزودن یک پنل جدید یعنی صرفاً ساخت یک کلاس تازه که این
 * Interface را پیاده‌سازی کند و ثبت آن در PanelDriverFactory — بدون
 * لمس AccountService یا هیچ بخش دیگر از هسته.
 */
interface PanelDriverInterface
{
    /** ساخت اکانت جدید روی پنل */
    public function createAccount(ServerPanel $panel, PanelAccountRequest $request): PanelAccountResult;

    /** دریافت اطلاعات فعلی اکانت (حجم مصرفی، وضعیت، ...) */
    public function getAccount(ServerPanel $panel, string $username): PanelAccountResult;

    /** ویرایش اکانت موجود (تمدید، تغییر حجم و ...) */
    public function updateAccount(ServerPanel $panel, string $username, PanelAccountRequest $request): PanelAccountResult;

    /** حذف اکانت از پنل */
    public function deleteAccount(ServerPanel $panel, string $username): PanelAccountResult;

    /** صفر کردن حجم مصرفی اکانت */
    public function resetUsage(ServerPanel $panel, string $username): PanelAccountResult;
}
