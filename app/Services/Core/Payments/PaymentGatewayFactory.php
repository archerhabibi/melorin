<?php

namespace App\Services\Core\Payments;

use App\Models\PaymentMethod;
use InvalidArgumentException;

/**
 * افزودن درگاه جدید (بند ۳۲ سند) فقط نیازمند یک خط جدید در $map است —
 * بدون تغییر در PaymentService یا هر بخش دیگر از هسته.
 *
 * کلید نگاشت از payment_methods.settings['driver'] خوانده می‌شود؛ اگر
 * تنظیم نشده باشد، از payment_methods.type استفاده می‌شود (کافی برای
 * card_to_card که هم type و هم driver یکی است).
 */
class PaymentGatewayFactory
{
    protected static array $map = [
        'card_to_card' => CardToCardGateway::class,
        'zarinpal' => ZarinpalGateway::class,
    ];

    public static function make(PaymentMethod $method): PaymentGatewayInterface
    {
        $driver = $method->settings['driver'] ?? $method->type;

        $gatewayClass = self::$map[$driver] ?? null;

        if (! $gatewayClass) {
            throw new InvalidArgumentException("درگاهی برای driver '{$driver}' ثبت نشده است.");
        }

        return app($gatewayClass);
    }
}
