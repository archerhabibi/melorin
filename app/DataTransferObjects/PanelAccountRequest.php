<?php

namespace App\DataTransferObjects;

/**
 * پارامترهای لازم برای ساخت یک اکانت روی هر پنل، مستقل از نوع پنل.
 * هر Driver این مقادیر عمومی را به فرمت خاص API خودش ترجمه می‌کند.
 */
class PanelAccountRequest
{
    public function __construct(
        public readonly string $username,
        public readonly int $trafficBytes,   // ۰ = نامحدود
        public readonly int $expireTimestamp, // ۰ = بدون انقضا
        public readonly ?string $note = null,
        public readonly array $extra = [],    // پارامترهای اختصاصی پنل (inbound id و ...)
    ) {}
}
