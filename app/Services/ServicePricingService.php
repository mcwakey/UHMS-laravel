<?php

namespace App\Services;

use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;

/**
 * ServicePricingService — single source of truth for resolving the billable
 * pricing snapshot of a service against a payer (cash & carry or insurance).
 *
 * Pricing priority:
 *   1) Provider-specific insurance price (service_prices with insurance_provider_id)
 *   2) Insurance-type default price (service_prices with provider_id = null)
 *   3) Base / cash & carry price (service_catalog.price)
 *
 * RESPONSIBILITIES:
 *   This service ONLY resolves prices. It does NOT compute:
 *     - paid_amount
 *     - discount_amount
 *     - patient_payable
 *     - balance
 *   Those are the responsibility of BillingService / PaymentService.
 *
 * Returned shape (canonical):
 *   [
 *     'cash_price'            => float,
 *     'insurance_price'       => ?float,
 *     'selected_price'        => float,
 *     'pricing_source'        => 'provider_specific' | 'insurance_type' | 'cash_and_carry' | 'base_price',
 *     'payment_type'          => 'cash' | 'insurance',
 *     'insurance_type'        => ?string,
 *     'insurance_provider_id' => ?int,
 *   ]
 */
class ServicePricingService
{
    public function __construct(private readonly ServicePriceResolver $resolver)
    {
    }

    /**
     * Resolve pricing for a service against an explicit (optional) insurance.
     *
     * @return array{
     *   cash_price: float,
     *   insurance_price: ?float,
     *   selected_price: float,
     *   pricing_source: string,
     *   payment_type: string,
     *   insurance_type: ?string,
     *   insurance_provider_id: ?int,
     * }
     */
    public function resolvePriceForVisitService(
        ServiceCatalog $service,
        ?PatientInsurance $patientInsurance,
        int $quantity = 1
    ): array {
        $snapshot      = $this->resolver->resolveForInsurance($service, $patientInsurance);
        $cashPrice     = (float) $snapshot['cash_price'];
        $selectedPrice = (float) $snapshot['selected_price'];
        $isCash        = $snapshot['payer_type'] === 'cash';

        return [
            'cash_price'            => $cashPrice,
            'insurance_price'       => $isCash ? null : $selectedPrice,
            'selected_price'        => $selectedPrice,
            'pricing_source'        => $this->normalizeSource($snapshot['pricing_source']),
            'payment_type'          => $isCash ? 'cash' : 'insurance',
            'insurance_type'        => $isCash ? null : $snapshot['insurance_type'],
            'insurance_provider_id' => $isCash ? null : $snapshot['insurance_provider_id'],
        ];
    }

    private function normalizeSource(string $source): string
    {
        return match ($source) {
            'cash_price'                       => 'cash_and_carry',
            'provider_specific_price'          => 'provider_specific',
            'insurance_type_default'           => 'insurance_type',
            'fallback_cash_no_insurance_price' => 'base_price',
            default                            => $source,
        };
    }
}

