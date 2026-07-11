<?php

namespace App\Enums;

/**
 * How a registered operation should treat a missing billing context (e.g. no
 * invoice or no invoice item). Phase 4 defines configuration vocabulary only —
 * it must NOT override existing production behaviour, and `block` must not become
 * operational for unwired workflows in this phase.
 */
enum MissingBillingContextPolicy: string
{
    case PRESERVE_LEGACY = 'preserve_legacy';
    case ALLOW = 'allow';
    case BLOCK = 'block';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return __('payment_gate.missing_context.'.$this->value);
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
