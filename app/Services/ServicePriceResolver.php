<?php

namespace App\Services;

use App\Enums\InsuranceType;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\Visit;

/**
 * Resolve the billable price for a service against a visit's payer.
 *
 * UHMS billing rule:
 *   - Each service stores a base "cash & carry" price plus payer-specific
 *     overrides in the `service_prices` table (per insurance type, optionally
 *     per provider).
 *   - The selected payer's price is the FINAL billable amount. We do NOT
 *     re-apply an insurance coverage percentage on top of it.
 *   - The difference between the cash price and the selected price is shown
 *     as the patient's benefit / discount, for display & reporting only.
 */
class ServicePriceResolver
{
    /**
     * Resolve pricing for a service against a visit's active payer.
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
    public function resolveForVisit(ServiceCatalog $service, Visit $visit): array
    {
        $service->loadMissing('prices');

        $insurance = $visit->visitInsurance;
        $provider  = $insurance?->insuranceProvider;

        $hasInsurance = $insurance
            && $insurance->is_active
            && ! $insurance->is_expired
            && $provider
            && ! $provider->is_default;

        if (! $hasInsurance) {
            return $this->cashSnapshot($service);
        }

        return $this->insuranceSnapshot($service, $insurance, $provider);
    }

    /**
     * Resolve pricing for a service given an explicit insurance + provider.
     */
    public function resolveForInsurance(
        ServiceCatalog $service,
        ?PatientInsurance $insurance
    ): array {
        $service->loadMissing('prices');
        $provider = $insurance?->insuranceProvider;

        $hasInsurance = $insurance
            && $insurance->is_active
            && ! $insurance->is_expired
            && $provider
            && ! $provider->is_default;

        if (! $hasInsurance) {
            return $this->cashSnapshot($service);
        }

        return $this->insuranceSnapshot($service, $insurance, $provider);
    }

    private function cashSnapshot(ServiceCatalog $service): array
    {
        $cash = (float) $service->price;

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
        ServiceCatalog $service,
        PatientInsurance $insurance,
        $provider
    ): array {
        $cash       = (float) $service->price;
        $type       = $provider->type instanceof InsuranceType ? $provider->type : null;
        $typeValue  = $type?->value ?? (is_string($provider->type) ? $provider->type : null);
        $providerId = $insurance->insurance_provider_id;

        // Provider-specific override?
        $providerPrice = $service->prices->first(
            fn ($p) => $p->insurance_type === $typeValue && (int) $p->insurance_provider_id === (int) $providerId
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

        // Insurance-type default?
        $typeDefault = $service->prices->first(
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

        // No payer-specific price configured — fall back to cash price but flag it.
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
