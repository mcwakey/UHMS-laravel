<?php

namespace App\Http\Controllers\Billing;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\Billing\PatientPaymentAllocationService;
use App\Services\Billing\PreviousBalanceOverrideService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * HTTP surface for the previous-visit outstanding balance policy:
 *   - approving the OPD "proceed despite old debt" override
 *   - collecting a patient tender and allocating it across visits
 */
class PreviousBalanceController extends Controller
{
    public function __construct(
        protected PreviousBalanceOverrideService $overrideService,
        protected PatientPaymentAllocationService $allocationService,
    ) {}

    /**
     * Approve a previous-balance override for an OPD visit so non-emergency
     * service may proceed despite the patient's old debt.
     */
    public function override(Request $request, Visit $visit)
    {
        abort_unless($request->user()?->can('billing.previous_balance.override'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->overrideService->approveOverride($visit, $request->user(), $data['reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('billing.previous_balance_override_active'));
    }

    /**
     * Collect a patient tender and allocate it across their open invoices.
     * Modes: oldest_first (default), current_visit, manual.
     */
    public function allocate(Request $request, Patient $patient)
    {
        abort_unless($request->user()?->can('billing.payment.allocate_cross_visit'), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(array_map(fn ($m) => $m->value, PaymentMethod::cases()))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'mode' => ['nullable', Rule::in([
                PatientPaymentAllocationService::MODE_OLDEST_FIRST,
                PatientPaymentAllocationService::MODE_CURRENT_VISIT,
                PatientPaymentAllocationService::MODE_MANUAL,
            ])],
            'visit_id' => ['nullable', 'integer', 'exists:visits,id'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'integer', 'exists:invoices,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'gt:0'],
        ]);

        $mode = $data['mode'] ?? config('billing.previous_balance_policy.default_payment_allocation', 'oldest_first');
        $currentVisit = ! empty($data['visit_id']) ? Visit::find($data['visit_id']) : null;

        $tender = [
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if ($mode === PatientPaymentAllocationService::MODE_MANUAL) {
            abort_unless($request->user()?->can('billing.payment.allocate_manual'), 403);
        }

        try {
            $payments = match ($mode) {
                PatientPaymentAllocationService::MODE_MANUAL => $this->allocationService->allocatePaymentManually(
                    $patient, $data['allocations'] ?? [], $tender, $currentVisit,
                ),
                PatientPaymentAllocationService::MODE_CURRENT_VISIT => $this->allocationService->allocatePaymentToCurrentVisit(
                    $currentVisit ?? abort(422, __('billing.pay_current_visit_only')), $tender,
                ),
                default => $this->allocationService->allocatePaymentOldestFirst($patient, $tender, $currentVisit),
            };
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.payments.recorded', [
            'number' => $payments->pluck('payment_number')->implode(', '),
            'amount' => '₵' . number_format((float) $payments->sum('amount'), 2),
        ]));
    }
}
