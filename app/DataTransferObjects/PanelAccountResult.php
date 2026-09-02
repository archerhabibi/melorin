<?php

namespace App\DataTransferObjects;

/**
 * درخواست صریح: تحویل به کاربر باید لینک سابسکریپشن باشد، نه کانفیگ خام —
 * پس فیلد قبلی «configLink» به «subscriptionUrl» تغییر نام داد تا در کل
 * کدبیس این تمایز روشن بماند (این یک لینک سابسکریپشن قابل رفرش‌شدن است،
 * نه یک کانفیگ ثابت تک‌پروتکلی).
 *
 * panelExtra → مقادیر اختصاصی پنل که باید روی رکورد Account ذخیره شوند تا
 * بعداً (renew/getAccount) دوباره قابل استفاده باشند، اما در PanelAccountRequest
 * جایی ندارند چون خروجی عملیات‌اند نه ورودی. مثال: subscription_id برای
 * Sanaei (همان subId کلاینت روی 3X-UI).
 */
class PanelAccountResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?array $rawResponse = null,
        public readonly ?string $subscriptionUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly array $panelExtra = [],
    ) {}

    public static function fail(string $message, ?array $raw = null): self
    {
        return new self(success: false, rawResponse: $raw, errorMessage: $message);
    }

    public static function ok(?array $raw = null, ?string $subscriptionUrl = null, array $panelExtra = []): self
    {
        return new self(success: true, rawResponse: $raw, subscriptionUrl: $subscriptionUrl, panelExtra: $panelExtra);
    }
}
