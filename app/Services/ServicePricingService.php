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
 * Cash & carry rule:
 *   - When the resolved insurance is missing, inactive, expired, or the
 *     provider is the platform's default ("cash & carry") provider, the
 *     cash price is used.
 *   - cash_and_carry pricing applies no discount: insurance_covered = 0,
 *     patient_payable = total_price.
 *
 * Insurance pricing rule:
 *   - The resolved insurance price IS the final billable amount per unit.
 *   - insurance_covered = (cash_price - insurance_price) * quantity
 *     (the "discount" the insurance provides off cash rate)
 *   - patient_payable = total_price (the patient pays the insurance rate
 *     unless the back-end constraint engine reduces it further at billing
 *     time via InsuranceService::evaluateCoverage()).
 */
class ServicePricingService
{
    public function __construct(private readonly ServicePriceResolver $resolver)
    {
    }

    /**
     * @return array{
     *   unit_price: float,
     *   insurance_price: ?float,
     *   total_price: float,
     *   insurance_covered: float,
     *   patient_payable: float,
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
        $quantity = max(1, $quantity);
        $snapshot = $this->resolver->resolveForInsurance($service, $patientInsurance);

        $unitPrice  = $snapshot['cash_price'];
        $totalPrice = round($snapshot['selected_price'] * $quantity, 2);
        $isCash     = $snapshot['payer_type'] === 'cash';

        if ($isCash) {
            return [
                'unit_price'            => $unitPrice,
                'insurance_price'       => null,
                'total_price'           => $totalPrice,
                'insurance_covered'     => 0.0,
                'patient_payable'       => $totalPrice,
                'pricing_source'        => $this->normalizeSource($snapshot['pricing_source']),
                'payment_type'          => 'cash',
                'insurance_type'        => null,
                'insurance_provider_id' => null,
            ];
        }

        $insurancePrice    = $snapshot['selected_price'];
        $insuranceCovered  = round($snapshot['discount_amount'] * $quantity, 2);
        $patientPayable    = $totalPrice; // before constraint engine

        return [
            'unit_price'            => $unitPrice,
            'insurance_price'       => $insurancePrice,
            'total_price'           => $totalPrice,
            'insurance_covered'     => $insuranceCovered,
            'patient_payable'       => $patientPayable,
            'pricing_source'        => $this->normalizeSource($snapshot['pricing_source']),
            'payment_type'          => 'insurance',
            'insurance_type'        => $snapshot['insurance_type'],
            'insurance_provider_id' => $snapshot['insurance_provider_id'],
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
