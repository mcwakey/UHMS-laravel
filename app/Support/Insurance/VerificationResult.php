<?php

namespace App\Support\Insurance;

use App\Enums\VerificationStatus;
use Illuminate\Support\Carbon;

/**
 * Driver output DTO. Provider-agnostic.
 */
class VerificationResult
{
    public function __construct(
        public readonly VerificationStatus $status,
        public readonly ?string $referenceCode = null,
        public readonly ?string $memberName = null,
        public readonly ?Carbon $expiresAt = null,
        public readonly ?string $message = null,
        public readonly array $payload = [],
        public readonly bool $requiresManualEntry = false,
    ) {}

    public static function valid(?string $reference = null, array $extra = []): self
    {
        return new self(
            status: VerificationStatus::VALID,
            referenceCode: $reference,
            memberName: $extra['member_name'] ?? null,
            expiresAt: $extra['expires_at'] ?? null,
            message: $extra['message'] ?? null,
            payload: $extra['payload'] ?? [],
        );
    }

    public static function pending(string $message = 'Awaiting reference code', bool $requiresManual = true): self
    {
        return new self(
            status: VerificationStatus::PENDING,
            message: $message,
            requiresManualEntry: $requiresManual,
        );
    }

    public static function invalid(string $message, array $payload = []): self
    {
        return new self(VerificationStatus::INVALID, message: $message, payload: $payload);
    }

    public static function expired(string $message = 'Insurance expired'): self
    {
        return new self(VerificationStatus::EXPIRED, message: $message);
    }

    public static function error(string $message, array $payload = []): self
    {
        return new self(VerificationStatus::ERROR, message: $message, payload: $payload);
    }

    public static function notRequired(): self
    {
        return new self(VerificationStatus::NOT_REQUIRED, message: 'Provider does not require verification');
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'reference_code' => $this->referenceCode,
            'member_name' => $this->memberName,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'message' => $this->message,
            'requires_manual_entry' => $this->requiresManualEntry,
            'acceptable' => $this->status->isAcceptable(),
        ];
    }
}
