<?php

namespace App\Services;

use App\Enums\InsuranceType;
use App\Models\PatientInsurance;
use App\Models\Product;
use App\Models\Visit;

/**
 * Resolve the billable price for a Product against a visit's payer.
 *
 * Resolution priority:
 *   1. Provider-specific price  (product_prices where insurance_provider_id = visit provider)
 *   2. Insurance-type default   (product_prices where provider_id IS NULL, type matches)
 *   3. product.base_price       (cash & carry fallback)
 *
 * UHMS billing rule: the resolved price IS the final amount billed. We do NOT
 * apply a coverage percentage on top. The difference (cash − selected) is shown
 * as `insurance_covered` for display/reporting only — it is never deducted from
 * patient_payable.
 */
class ProductPriceResolver
{
    /**
     * Resolve pricing for a product against a visit's active payer.
     *
     * @return array{
     *   cash_price: float,
     *   selected_price: float,
     *   discount_amount: float,
     *   payer_type: string,
     *   insurance_provider_id: ?int,
     *   insurance_type: ?string,
     *   pricing_source: string
     * }
     */
    public function resolveForVisit(Product $product, Visit $visit): array
    {
        $product->loadMissing('prices');

        $insurance = $visit->visitInsurance;
        $provider  = $insurance?->insuranceProvider;

        $hasInsurance = $insurance
            && $insurance->is_active
            && ! $insurance->is_expired
            && $provider
            && ! $provider->is_default;

        if (! $hasInsurance) {
            return $this->cashSnapshot($product);
        }

        return $this->insuranceSnapshot($product, $insurance, $provider);
    }

    /**
     * Resolve pricing for a product given an explicit PatientInsurance record.
     * Used when the caller already has the insurance object (e.g. dispensing flow).
     */
    public function resolveForInsurance(Product $product, ?PatientInsurance $insurance): array
    {
        $product->loadMissing('prices');
        $provider = $insurance?->insuranceProvider;

        $hasInsurance = $insurance
            && $insurance->is_active
            && ! $insurance->is_expired
            && $provider
            && ! $provider->is_default;

        if (! $hasInsurance) {
            return $this->cashSnapshot($product);
        }

        return $this->insuranceSnapshot($product, $insurance, $provider);
    }

    /* ── Private helpers ──────────────────────────────── */

    private function cashSnapshot(Product $product): array
    {
        $cash = (float) ($product->base_price ?? 0);

        return [
            'cash_price'            => $cash,
            'selected_price'        => $cash,
            'discount_amount'       => 0.0,
            'payer_type'            => 'cash',
            'insurance_provider_id' => null,
            'insurance_type'        => null,
            'pricing_source'        => 'cash_price',
        ];
    }

    private function insuranceSnapshot(
        Product $product,
        PatientInsurance $insurance,
        $provider
    ): array {
        $cash       = (float) ($product->base_price ?? 0);
        $type       = $provider->type instanceof InsuranceType ? $provider->type : null;
        $typeValue  = $type?->value ?? (is_string($provider->type) ? $provider->type : null);
        $providerId = $insurance->insurance_provider_id;

        // Only consider active prices
        $activePrices = $product->prices->where('is_active', true);

        // 1. Provider-specific override
        $providerPrice = $activePrices->first(
            fn ($p) => $p->insurance_type === $typeValue
                && (int) $p->insurance_provider_id === (int) $providerId
        );

        if ($providerPrice) {
            $selected = (float) $providerPrice->price;
            return [
                'cash_price'            => $cash,
                'selected_price'        => $selected,
                'discount_amount'       => max(0.0, round($cash - $selected, 2)),
                'payer_type'            => 'insurance',
                'insurance_provider_id' => $providerId,
                'insurance_type'        => $typeValue,
                'pricing_source'        => 'provider_specific_price',
            ];
        }

        // 2. Insurance-type default
        $typeDefault = $activePrices->first(
            fn ($p) => $p->insurance_type === $typeValue && $p->insurance_provider_id === null
        );

        if ($typeDefault) {
            $selected = (float) $typeDefault->price;
            return [
                'cash_price'            => $cash,
                'selected_price'        => $selected,
                'discount_amount'       => max(0.0, round($cash - $selected, 2)),
                'payer_type'            => 'insurance',
                'insurance_provider_id' => $providerId,
                'insurance_type'        => $typeValue,
                'pricing_source'        => 'insurance_type_default',
            ];
        }

        // 3. Fallback to cash (no matching insurance price configured)
        return [
            'cash_price'            => $cash,
            'selected_price'        => $cash,
            'discount_amount'       => 0.0,
            'payer_type'            => 'insurance',
            'insurance_provider_id' => $providerId,
            'insurance_type'        => $typeValue,
            'pricing_source'        => 'fallback_cash_no_insurance_price',
        ];
    }
}
