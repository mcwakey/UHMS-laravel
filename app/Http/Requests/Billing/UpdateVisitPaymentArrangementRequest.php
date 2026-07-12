<?php

namespace App\Http\Requests\Billing;

/**
 * Validates updates to a pending per-visit payment-arrangement request (Payment
 * Timing Policy Phase 7). Same field rules as the initial request.
 */
class UpdateVisitPaymentArrangementRequest extends StoreVisitPaymentArrangementRequest
{
}
