<?php

namespace App\Services\Core\Payments;

use App\DataTransferObjects\GatewayInitiationResult;
use App\DataTransferObjects\GatewayVerificationResult;
use App\Models\Payment;

/**
 * قرارداد مشترک همه‌ی درگاه‌های پرداخت (کارت‌به‌کارت، Zarinpal، آینده:
 * Aqayepardakht، IranPay، NowPayments و ...).
 *
 * طبق بند ۳۲ سند نیازمندی («امکان اضافه کردن روش پرداخت جدید بدون تغییرات
 * اساسی در هسته»)، افزودن یک درگاه جدید یعنی صرفاً ساخت یک کلاس تازه که
 * این Interface را پیاده‌سازی کند و ثبت آن در PaymentGatewayFactory —
 * بدون لمس PaymentService یا هیچ بخش دیگر از هسته.
 */
interface PaymentGatewayInterface
{
    /**
     * true اگر این درگاه نیازمند بررسی و تایید دستی ادمین است (مثل
     * کارت‌به‌کارت که رسید واریزی بررسی می‌شود)، false اگر آنلاین است و
     * از طریق callback خودِ درگاه تایید می‌شود (مثل Zarinpal).
     */
    public function isManual(): bool;

    /** شروع فرآیند پرداخت؛ برای درگاه آنلاین یعنی گرفتن لینک پرداخت */
    public function initiate(Payment $payment): GatewayInitiationResult;

    /**
     * تایید پرداخت با داده‌ی callback درگاه. فقط برای درگاه‌های آنلاین
     * (isManual() === false) فراخوانی می‌شود؛ درگاه‌های دستی به‌جایش با
     * PaymentService::confirmManual() توسط ادمین تایید می‌شوند.
     */
    public function verify(Payment $payment, array $callbackData): GatewayVerificationResult;
}
