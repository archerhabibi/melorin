<?php

namespace App\DataTransferObjects;

/**
 * نتیجه‌ی شروع پرداخت (بند ۱۲: «ایجاد پرداخت»).
 *
 * برای درگاه‌های آنلاین (Zarinpal و مشابه): redirectUrl پر می‌شود تا کاربر
 * به آن هدایت شود و gatewayReference (Authority) برای مرحله‌ی verify ذخیره
 * می‌شود.
 *
 * برای درگاه‌های دستی (کارت‌به‌کارت): redirectUrl خالی است و instructions
 * شامل اطلاعاتی است که باید به کاربر نشان داده شود (شماره کارت، نام صاحب
 * حساب و...) تا واریز و رسید را ثبت کند.
 */
class GatewayInitiationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $gatewayReference = null,
        public readonly array $instructions = [],
        public readonly ?array $rawResponse = null,
        public readonly ?string $errorMessage = null,
    ) {
    }

    public static function manual(array $instructions, ?array $raw = null): self
    {
        return new self(success: true, instructions: $instructions, rawResponse: $raw);
    }

    public static function redirect(string $url, string $reference, ?array $raw = null): self
    {
        return new self(success: true, redirectUrl: $url, gatewayReference: $reference, rawResponse: $raw);
    }

    public static function fail(string $message, ?array $raw = null): self
    {
        return new self(success: false, rawResponse: $raw, errorMessage: $message);
    }
}
