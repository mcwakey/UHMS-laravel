<?php

namespace App\Http\Requests\Billing;

/**
 * Validates updates to an existing patient financial-risk classification
 * (Payment Timing Policy Phase 5). Same field rules as creation.
 */
class UpdatePatientFinancialRiskRequest extends StorePatientFinancialRiskRequest
{
}
