<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\ProcedureRequest;
use App\Models\ProcedureStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates procedure lifecycle transitions: accept, reject, bill, cancel, complete.
 */
class ProcedureWorkflowService
{
    public function __construct(
        protected BillingService $billingService,
    ) {}

    public function acceptProcedure(ProcedureRequest $request, User $user, ?string $notes = null): ProcedureRequest
    {
        $this->assertCurrentStatus($request, ProcedureStatus::REQUESTED, 'accept');

        return DB::transaction(function () use ($request, $user, $notes) {
            $request->forceFill([
                'status'           => ProcedureStatus::ACCEPTED,
                'accepted_by'      => $user->id,
                'accepted_at'      => now(),
                'acceptance_notes' => $notes,
            ])->save();

            $this->logStatusChange($request, ProcedureStatus::REQUESTED, ProcedureStatus::ACCEPTED, $user, $notes);

            return $request->fresh();
        });
    }

    public function rejectProcedure(ProcedureRequest $request, User $user, string $reason): ProcedureRequest
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Rejection reason is required.');
        }
        $this->assertCurrentStatus($request, ProcedureStatus::REQUESTED, 'reject');

        return DB::transaction(function () use ($request, $user, $reason) {
            $from = $request->status;
            $request->forceFill([
                'status'           => ProcedureStatus::REJECTED,
                'rejected_by'      => $user->id,
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->logStatusChange($request, $from, ProcedureStatus::REJECTED, $user, $reason);

            return $request->fresh();
        });
    }

    /**
     * Generate billing for an accepted procedure.
     * Creates an invoice item on the visit's single invoice via BillingService.
     */
    public function generateBilling(ProcedureRequest $request, User $user): ProcedureRequest
    {
        if ($request->status !== ProcedureStatus::ACCEPTED) {
            throw new \RuntimeException("Cannot bill a procedure with status '{$request->status->value}'. Must be ACCEPTED.");
        }
        if ($request->billing_item_id) {
            throw new \RuntimeException('Procedure has already been billed.');
        }
        if (! $request->service_catalog_id) {
            throw new \RuntimeException('Cannot bill: no billable service is linked to this procedure request.');
        }

        return DB::transaction(function () use ($request, $user) {
            $request->loadMissing(['visit', 'service']);
            $visit = $request->visit;
            $service = $request->service;

            if (! $visit || ! $service) {
                throw new \RuntimeException('Invalid visit or service linkage on procedure request.');
            }

            $item = $this->billingService->addItemToVisitInvoice(
                visit: $visit,
                service: $service,
                sourceType: 'procedure_request',
                sourceId: $request->id,
                quantity: 1,
                departmentId: $request->department_id,
                description: 'Procedure: ' . $service->name,
            );

            $from = $request->status;
            $request->forceFill([
                'status'          => ProcedureStatus::BILLED,
                'billing_item_id' => $item->id,
                'billed_at'       => now(),
                'billed_by'       => $user->id,
            ])->save();

            $this->logStatusChange($request, $from, ProcedureStatus::BILLED, $user, 'Procedure billed on visit invoice.');

            return $request->fresh(['billingItem']);
        });
    }

    public function cancelProcedure(ProcedureRequest $request, User $user, string $reason): ProcedureRequest
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Cancellation reason is required.');
        }
        if ($request->status === ProcedureStatus::COMPLETED) {
            throw new \RuntimeException('Cannot cancel a completed procedure.');
        }
        if ($request->status === ProcedureStatus::CANCELLED) {
            throw new \RuntimeException('Procedure is already cancelled.');
        }

        return DB::transaction(function () use ($request, $user, $reason) {
            $from = $request->status;

            // If the procedure was billed, void the invoice item rather than delete it.
            if ($request->billing_item_id) {
                $item = $request->billingItem()->first();
                if ($item) {
                    $item->forceFill([
                        'payment_status'  => 'voided',
                        'patient_payable' => 0,
                        'balance'         => 0,
                        'discount_amount' => (float) $item->selected_price * (int) $item->quantity,
                    ])->save();
                    if ($item->invoice) {
                        app(InvoiceService::class)->recalculateTotals($item->invoice->fresh('items'));
                    }
                }
            }

            $request->forceFill([
                'status'              => ProcedureStatus::CANCELLED,
                'cancelled_by'        => $user->id,
                'cancelled_at'        => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $this->logStatusChange($request, $from, ProcedureStatus::CANCELLED, $user, $reason);

            return $request->fresh();
        });
    }

    public function completeProcedure(ProcedureRequest $request, User $user): ProcedureRequest
    {
        $this->assertCurrentStatus($request, ProcedureStatus::POST_OP, 'complete');

        $request->loadMissing(['anaesthesiaNote', 'operativeNote', 'postOpNote']);
        if (! $request->anaesthesiaNote || ! $request->operativeNote || ! $request->postOpNote) {
            throw new \RuntimeException('Cannot complete: required anaesthesia, operative, and post-op notes must all be recorded first.');
        }

        return DB::transaction(function () use ($request, $user) {
            $from = $request->status;
            $request->forceFill([
                'status'       => ProcedureStatus::COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ])->save();

            $this->logStatusChange($request, $from, ProcedureStatus::COMPLETED, $user, 'Procedure completed.');

            return $request->fresh();
        });
    }

    /**
     * Internal helper used by other services to advance status.
     */
    public function transition(ProcedureRequest $request, ProcedureStatus $to, User $user, ?string $reason = null): ProcedureRequest
    {
        $from = $request->status;
        if (! $from->canTransitionTo($to)) {
            throw new \RuntimeException("Invalid procedure transition: {$from->value} → {$to->value}.");
        }

        return DB::transaction(function () use ($request, $from, $to, $user, $reason) {
            $request->forceFill(['status' => $to])->save();
            $this->logStatusChange($request, $from, $to, $user, $reason);
            return $request->fresh();
        });
    }

    public function logStatusChange(ProcedureRequest $request, ?ProcedureStatus $from, ProcedureStatus $to, User $user, ?string $reason = null): void
    {
        ProcedureStatusLog::create([
            'procedure_request_id' => $request->id,
            'from_status'          => $from?->value,
            'to_status'            => $to->value,
            'changed_by'           => $user->id,
            'reason'               => $reason,
        ]);
    }

    protected function assertCurrentStatus(ProcedureRequest $request, ProcedureStatus $expected, string $action): void
    {
        if ($request->status !== $expected) {
            throw new \RuntimeException("Cannot {$action}: procedure status must be '{$expected->value}', currently '{$request->status->value}'.");
        }
    }
}
