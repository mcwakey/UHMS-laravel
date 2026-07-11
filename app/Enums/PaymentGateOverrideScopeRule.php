<?php

namespace App\Enums;

/**
 * Which override scopes a registered operation may recognise. Preparatory
 * configuration only in Phase 4. Previous-balance and financial-closure
 * overrides remain SEPARATE and are intentionally not part of this vocabulary.
 */
enum PaymentGateOverrideScopeRule: string
{
    case NONE = 'none';
    case INVOICE_ITEM_ONLY = 'invoice_item_only';
    case SERVICE_OR_ITEM = 'service_or_item';
    case DEPARTMENT_SERVICE_OR_ITEM = 'department_service_or_item';
    case VISIT_WIDE = 'visit_wide';
    case PRESERVE_LEGACY = 'preserve_legacy';

    public function label(): string
    {
        return __('payment_gate.override_scope.'.$this->value);
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
