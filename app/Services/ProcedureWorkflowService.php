<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
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
        protected ServicePriceResolver $priceResolver,
        protected NotificationService $notifications,
        protected ActivityLogService $logger,
    ) {}

    /**
     * Resolve the price that will be posted when this procedure is billed.
     */
    public function billingPreview(ProcedureRequest $request): ?array
    {
        $request->loadMissing([
            'service.prices',
            'visit.visitInsurance.insuranceProvider',
        ]);

        if (! $request->service || ! $request->visit) {
            return null;
        }

        return $this->priceResolver->resolveForVisit($request->service, $request->visit);
    }

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

            if ($request->visit) {
                app(VisitPathwayService::class)->record($request->visit, 'PROCEDURE_ACCEPTED', [
                    'source' => $request,
                    'department_id' => $request->department_id,
                    'title' => 'Procedure accepted',
                    'description' => $notes,
                    'created_by' => $user->id,
                ]);
            }

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

            if ($request->visit) {
                app(VisitPathwayService::class)->record($request->visit, 'PROCEDURE_REJECTED', [
                    'source' => $request,
                    'department_id' => $request->department_id,
                    'title' => 'Procedure rejected',
                    'description' => $reason,
                    'created_by' => $user->id,
                ]);
            }

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

            app(VisitPathwayService::class)->record($visit, 'PROCEDURE_BILLED', [
                'source' => $request,
                'department_id' => $request->department_id,
                'title' => 'Procedure billed',
                'description' => $service->name,
                'created_by' => $user->id,
            ]);

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

            $fresh = $request->fresh();
            $this->notifyProcedureCancelled($fresh, $user, $reason);
            // Activity log is emitted by logStatusChange() above (PROCEDURE_CANCELLED).

            return $fresh;
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

            $fresh = $request->fresh();
            if ($fresh->visit) {
                app(VisitPathwayService::class)->record($fresh->visit, 'PROCEDURE_COMPLETED', [
                    'source' => $fresh,
                    'department_id' => $fresh->department_id,
                    'title' => 'Procedure completed',
                    'description' => $fresh->service?->name,
                    'created_by' => $user->id,
                ]);
            }
            $this->notifyProcedureCompleted($fresh, $user);
            // Activity log is emitted by logStatusChange() above (PROCEDURE_COMPLETED).

            return $fresh;
        });
    }

    /* ── Notifications ──────────────────────────────────────────── */

    protected function notifyProcedureCancelled(ProcedureRequest $request, User $actor, string $reason): void
    {
        $request->loadMissing(['patient', 'service', 'requestingDoctor']);
        $patient = $request->patient;
        $title = $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'patient';
        $serviceName = $request->service?->name ?? 'Procedure';

        $payload = [
            'title' => 'Procedure cancelled',
            'message' => sprintf('%s for %s was cancelled: %s', $serviceName, $title, $reason),
            'module' => NotificationModule::PROCEDURE,
            'priority' => NotificationPriority::HIGH,
            'source_type' => 'procedure_request',
            'source_id' => $request->id,
            'action_url' => $this->procedureUrl($request),
            'patient_id' => $request->patient_id,
        ];

        $recipients = collect();
        if ($request->requestingDoctor && $request->requestingDoctor->id !== $actor->id) {
            $recipients->push($request->requestingDoctor);
        }
        $this->notifications->notifyUsers($recipients, $payload);
    }

    protected function notifyProcedureCompleted(ProcedureRequest $request, User $actor): void
    {
        $request->loadMissing(['patient', 'service', 'requestingDoctor']);
        $patient = $request->patient;
        $title = $patient ? trim($patient->first_name . ' ' . $patient->last_name) : 'patient';
        $serviceName = $request->service?->name ?? 'Procedure';

        $payload = [
            'title' => 'Procedure completed',
            'message' => sprintf('%s for %s has been completed.', $serviceName, $title),
            'module' => NotificationModule::PROCEDURE,
            'priority' => NotificationPriority::NORMAL,
            'source_type' => 'procedure_request_completed',
            'source_id' => $request->id,
            'action_url' => $this->procedureUrl($request),
            'patient_id' => $request->patient_id,
        ];

        if ($request->requestingDoctor && $request->requestingDoctor->id !== $actor->id) {
            $this->notifications->notifyUser($request->requestingDoctor, $payload);
        }
    }

    protected function procedureUrl(ProcedureRequest $request): string
    {
        foreach (['admin.procedures.show', 'admin.procedures.index'] as $name) {
            try {
                return $name === 'admin.procedures.show'
                    ? route($name, $request->id)
                    : route($name);
            } catch (\Throwable $e) {
                continue;
            }
        }
        return '#';
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

        // Mirror onto the central activity log → patient timeline. This single
        // funnel covers the whole accept → schedule → surgery → complete lifecycle
        // (ProcedureClinicalService and ProcedureScheduleService both route through
        // it). The initial REQUESTED status is set at creation, NOT via a transition,
        // so the consultation-side procedure-request log is never duplicated here.
        try {
            $request->loadMissing('service');
            $warning = in_array($to, [ProcedureStatus::REJECTED, ProcedureStatus::CANCELLED, ProcedureStatus::ON_HOLD], true);
            $this->logger->log(
                $this->statusModule($to),
                $this->statusEvent($to),
                array_filter([
                    'patient_id' => $request->patient_id,
                    'visit_id' => $request->visit_id,
                    'emergency_case_id' => $request->emergency_case_id,
                    'medical_record_id' => $request->medical_record_id,
                    'consultation_route_id' => $request->consultation_route_id,
                    'department_id' => $request->department_id,
                    'service_id' => $request->service_catalog_id,
                    'procedure_request_id' => $request->id,
                    'invoice_item_id' => $request->billing_item_id,
                    'reason' => $reason,
                    'severity' => $warning ? LogSeverity::WARNING : LogSeverity::INFO,
                    'old_values' => $from ? ['status' => $from->value] : null,
                    'new_values' => ['status' => $to->value],
                    'source_type' => 'procedure_request',
                    'source_id' => $request->id,
                    'causer' => $user,
                ], fn ($v) => $v !== null),
                $request,
                $this->statusDescription($to, $request, $reason),
            );
        } catch (\Throwable $e) {
            // Logging must never break a procedure transition.
        }
    }

    private function statusModule(ProcedureStatus $to): LogModule
    {
        return match ($to) {
            ProcedureStatus::SCHEDULED, ProcedureStatus::RESCHEDULED, ProcedureStatus::PRE_OP,
            ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY, ProcedureStatus::SURGERY_DONE,
            ProcedureStatus::POST_OP => LogModule::THEATRE,
            default => LogModule::PROCEDURE,
        };
    }

    private function statusEvent(ProcedureStatus $to): string
    {
        return match ($to) {
            ProcedureStatus::ACCEPTED => 'PROCEDURE_ACCEPTED',
            ProcedureStatus::REJECTED => 'PROCEDURE_REJECTED',
            ProcedureStatus::BILLED => 'PROCEDURE_BILLED',
            ProcedureStatus::SCHEDULED => 'THEATRE_CASE_SCHEDULED',
            ProcedureStatus::RESCHEDULED => 'THEATRE_CASE_RESCHEDULED',
            ProcedureStatus::PRE_OP => 'PREOP_CHECKLIST_UPDATED',
            ProcedureStatus::ANAESTHESIA => 'ANAESTHESIA_NOTE_ADDED',
            ProcedureStatus::IN_SURGERY => 'PROCEDURE_STARTED',
            ProcedureStatus::SURGERY_DONE => 'OPERATIVE_NOTE_ADDED',
            ProcedureStatus::POST_OP => 'RECOVERY_NOTE_ADDED',
            ProcedureStatus::COMPLETED => 'PROCEDURE_COMPLETED',
            ProcedureStatus::CANCELLED => 'PROCEDURE_CANCELLED',
            ProcedureStatus::ON_HOLD => 'PROCEDURE_POSTPONED',
            default => 'PROCEDURE_STATUS_CHANGED',
        };
    }

    private function statusDescription(ProcedureStatus $to, ProcedureRequest $request, ?string $reason): string
    {
        $service = $request->service?->name ?? 'Procedure';
        $base = match ($to) {
            ProcedureStatus::ACCEPTED => "Procedure accepted: {$service}",
            ProcedureStatus::REJECTED => "Procedure rejected: {$service}",
            ProcedureStatus::BILLED => "Procedure billed: {$service}",
            ProcedureStatus::SCHEDULED => "Procedure scheduled: {$service}",
            ProcedureStatus::RESCHEDULED => "Procedure rescheduled: {$service}",
            ProcedureStatus::PRE_OP => "Pre-op checklist recorded: {$service}",
            ProcedureStatus::ANAESTHESIA => "Anaesthesia note recorded: {$service}",
            ProcedureStatus::IN_SURGERY => "Procedure started: {$service}",
            ProcedureStatus::SURGERY_DONE => "Operative note recorded: {$service}",
            ProcedureStatus::POST_OP => "Recovery note recorded: {$service}",
            ProcedureStatus::COMPLETED => "Procedure completed: {$service}",
            ProcedureStatus::CANCELLED => "Procedure cancelled: {$service}",
            ProcedureStatus::ON_HOLD => "Procedure postponed: {$service}",
            default => "Procedure status changed: {$service}",
        };

        return $reason && in_array($to, [ProcedureStatus::REJECTED, ProcedureStatus::CANCELLED, ProcedureStatus::ON_HOLD], true)
            ? "{$base} — {$reason}"
            : $base;
    }

    protected function assertCurrentStatus(ProcedureRequest $request, ProcedureStatus $expected, string $action): void
    {
        if ($request->status !== $expected) {
            throw new \RuntimeException("Cannot {$action}: procedure status must be '{$expected->value}', currently '{$request->status->value}'.");
        }
    }
}
