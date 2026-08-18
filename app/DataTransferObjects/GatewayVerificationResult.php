<?php

namespace App\DataTransferObjects;

class GatewayVerificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $trackingCode = null,
        public readonly ?array $rawResponse = null,
        public readonly ?string $errorMessage = null,
    ) {
    }

    public static function ok(?string $trackingCode = null, ?array $raw = null): self
    {
        return new self(success: true, trackingCode: $trackingCode, rawResponse: $raw);
    }

    public static function fail(string $message, ?array $raw = null): self
    {
        return new self(success: false, rawResponse: $raw, errorMessage: $message);
    }
}
