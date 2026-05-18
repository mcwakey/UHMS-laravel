<?php

namespace App\Services;

use App\Models\PatientInsurance;
use App\Models\Product;
use App\Models\Visit;

/**
 * Thin wrapper around ProductPriceResolver that adds derived billing values.
 *
 * Uses ProductPriceResolver for resolution, then enriches the result with
 * quantity-adjusted totals and an insurance_covered value (info-only display).
 *
 * Mirrors ServicePricingService for the Product domain.
 */
class ProductPricingService
{
    public function __construct(
        protected ProductPriceResolver $priceResolver,
    ) {}

    /**
     * Resolve the full billing snapshot for a product + patient insurance combo.
     *
     * @return array{
     *   cash_price: float,
     *   insurance_price: float,
     *   selected_price: float,
     *   discount_amount: float,
     *   quantity: int|float,
     *   total_price: float,
     *   patient_payable: float,
     *   insurance_covered: float,
     *   payer_type: string,
     *   insurance_provider_id: ?int,
     *   insurance_type: ?string,
     *   pricing_source: string
     * }
     */
    public function resolvePriceForProduct(
        Product $product,
        ?PatientInsurance $patientInsurance,
        int|float $quantity = 1,
    ): array {
        $snap = $this->priceResolver->resolveForInsurance($product, $patientInsurance);

        $qty          = max(1, (float) $quantity);
        $cash         = $snap['cash_price'];
        $selected     = $snap['selected_price'];
        $discount     = $snap['discount_amount'];

        // UHMS rule: patient_payable = selected_price × qty − discount
        $patientPayable   = round($selected * $qty - $discount, 2);
        // insurance_covered is info-only: how much the insurer nominally covers per unit
        $insuranceCovered = round(max(0.0, ($cash - $selected)) * $qty, 2);

        return array_merge($snap, [
            'insurance_price'    => $selected !== $cash ? $selected : 0.0,
            'quantity'           => $qty,
            'total_price'        => round($selected * $qty, 2),
            'patient_payable'    => $patientPayable,
            'insurance_covered'  => $insuranceCovered,
            // Normalise source label for display
            'pricing_source_label' => match ($snap['pricing_source']) {
                'provider_specific_price'       => 'Provider Price',
                'insurance_type_default'        => 'Insurance Type Default',
                'fallback_cash_no_insurance_price' => 'Cash (no insurance price set)',
                default                         => 'Cash',
            },
        ]);
    }

    /**
     * Resolve using a Visit's active payer (shortcut for billing flows).
     */
    public function resolvePriceForVisit(
        Product $product,
        Visit $visit,
        int|float $quantity = 1,
    ): array {
        $snap = $this->priceResolver->resolveForVisit($product, $visit);

        $qty          = max(1, (float) $quantity);
        $cash         = $snap['cash_price'];
        $selected     = $snap['selected_price'];
        $discount     = $snap['discount_amount'];

        $patientPayable   = round($selected * $qty - $discount, 2);
        $insuranceCovered = round(max(0.0, ($cash - $selected)) * $qty, 2);

        return array_merge($snap, [
            'insurance_price'    => $selected !== $cash ? $selected : 0.0,
            'quantity'           => $qty,
            'total_price'        => round($selected * $qty, 2),
            'patient_payable'    => $patientPayable,
            'insurance_covered'  => $insuranceCovered,
            'pricing_source_label' => match ($snap['pricing_source']) {
                'provider_specific_price'          => 'Provider Price',
                'insurance_type_default'           => 'Insurance Type Default',
                'fallback_cash_no_insurance_price' => 'Cash (no insurance price set)',
                default                            => 'Cash',
            },
        ]);
    }
}
