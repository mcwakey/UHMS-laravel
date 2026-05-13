<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\ProcedureRequest;

class ProcedureReportService
{
    /**
     * Build a chronological timeline for the procedure (used by views + history).
     *
     * @return array<int, array{stage:string,label:string,status:string,timestamp:?\Illuminate\Support\Carbon,user:?string,summary:?string,action:?string}>
     */
    public function getTimeline(ProcedureRequest $request): array
    {
        $request->loadMissing([
            'requestingDoctor', 'acceptedBy', 'completedBy',
            'rejectedBy', 'cancelledBy',
            'schedule.theatreRoom', 'schedule.surgeon', 'schedule.anaesthetist', 'schedule.scheduledBy',
            'vitals.recordedBy', 'checklist.completedBy',
            'anaesthesiaNote.anaesthetist',
            'operativeNote.surgeon',
            'postOpNote.recordedBy',
            'billingItem.invoice',
            'statusLogs.changedBy',
        ]);

        $steps = [];

        // 1. Requested
        $steps[] = [
            'stage'     => 'requested',
            'label'     => 'Requested',
            'status'    => 'done',
            'timestamp' => $request->requested_at,
            'user'      => $this->userName($request->requestingDoctor),
            'summary'   => 'Indication: ' . \Illuminate\Support\Str::limit($request->indication, 80),
            'action'    => null,
        ];

        // 2. Accepted / Rejected
        if ($request->accepted_at) {
            $steps[] = [
                'stage'     => 'accepted',
                'label'     => 'Accepted',
                'status'    => 'done',
                'timestamp' => $request->accepted_at,
                'user'      => $this->userName($request->acceptedBy),
                'summary'   => $request->acceptance_notes,
                'action'    => null,
            ];
        } elseif ($request->rejected_at) {
            $steps[] = [
                'stage'     => 'rejected',
                'label'     => 'Rejected',
                'status'    => 'rejected',
                'timestamp' => $request->rejected_at,
                'user'      => $this->userName($request->rejectedBy),
                'summary'   => $request->rejection_reason,
                'action'    => null,
            ];
        } else {
            $steps[] = [
                'stage' => 'accepted', 'label' => 'Accepted', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'accept',
            ];
        }

        // 3. Billed
        if ($request->billed_at) {
            $invoiceNumber = $request->billingItem?->invoice?->invoice_number;
            $steps[] = [
                'stage'     => 'billed',
                'label'     => 'Billed',
                'status'    => 'done',
                'timestamp' => $request->billed_at,
                'user'      => null,
                'summary'   => $invoiceNumber ? "Added to invoice {$invoiceNumber}" : 'Added to visit invoice',
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::ACCEPTED) {
            $steps[] = [
                'stage' => 'billed', 'label' => 'Billing', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'bill',
            ];
        }

        // 4. Scheduled
        if ($schedule = $request->schedule) {
            $room = $schedule->theatreRoom?->name;
            $summary = trim(
                ($schedule->scheduled_start?->format('d M Y, h:i A') ?? '')
                . ($room ? " · {$room}" : '')
                . ($schedule->surgeon ? ' · Surgeon: ' . $this->userName($schedule->surgeon) : '')
            );
            $steps[] = [
                'stage'     => 'scheduled',
                'label'     => 'Scheduled',
                'status'    => 'done',
                'timestamp' => $schedule->scheduled_at,
                'user'      => $this->userName($schedule->scheduledBy),
                'summary'   => $summary,
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::BILLED) {
            $steps[] = [
                'stage' => 'scheduled', 'label' => 'Schedule', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'schedule',
            ];
        }

        // 5. Pre-op
        if ($request->checklist) {
            $steps[] = [
                'stage'     => 'pre_op',
                'label'     => 'Pre-op',
                'status'    => 'done',
                'timestamp' => $request->checklist->completed_at,
                'user'      => $this->userName($request->checklist->completedBy ?? null),
                'summary'   => $request->checklist->pre_op_diagnosis,
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::SCHEDULED) {
            $steps[] = [
                'stage' => 'pre_op', 'label' => 'Pre-op', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'preop',
            ];
        }

        // 6. Anaesthesia
        if ($request->anaesthesiaNote) {
            $steps[] = [
                'stage'     => 'anaesthesia',
                'label'     => 'Anaesthesia',
                'status'    => 'done',
                'timestamp' => $request->anaesthesiaNote->created_at,
                'user'      => $this->userName($request->anaesthesiaNote->anaesthetist),
                'summary'   => strtoupper($request->anaesthesiaNote->anaesthesia_type),
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::PRE_OP) {
            $steps[] = [
                'stage' => 'anaesthesia', 'label' => 'Anaesthesia', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'anaesthesia',
            ];
        }

        // 7. Surgery / operative note
        if ($request->operativeNote) {
            $steps[] = [
                'stage'     => 'surgery',
                'label'     => 'Surgery',
                'status'    => 'done',
                'timestamp' => $request->operativeNote->created_at,
                'user'      => $this->userName($request->operativeNote->surgeon),
                'summary'   => \Illuminate\Support\Str::limit($request->operativeNote->procedure_performed, 80),
                'action'    => null,
            ];
        } elseif (in_array($request->status, [ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY], true)) {
            $steps[] = [
                'stage' => 'surgery', 'label' => 'Surgery', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'operative',
            ];
        }

        // 8. Post-op
        if ($request->postOpNote) {
            $steps[] = [
                'stage'     => 'post_op',
                'label'     => 'Post-op',
                'status'    => 'done',
                'timestamp' => $request->postOpNote->created_at,
                'user'      => $this->userName($request->postOpNote->recordedBy),
                'summary'   => $request->postOpNote->recovery_status,
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::SURGERY_DONE) {
            $steps[] = [
                'stage' => 'post_op', 'label' => 'Post-op', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'postop',
            ];
        }

        // 9. Completed / Cancelled
        if ($request->completed_at) {
            $steps[] = [
                'stage'     => 'completed',
                'label'     => 'Completed',
                'status'    => 'done',
                'timestamp' => $request->completed_at,
                'user'      => $this->userName($request->completedBy),
                'summary'   => null,
                'action'    => null,
            ];
        } elseif ($request->status === ProcedureStatus::POST_OP) {
            $steps[] = [
                'stage' => 'completed', 'label' => 'Complete', 'status' => 'pending',
                'timestamp' => null, 'user' => null, 'summary' => null, 'action' => 'complete',
            ];
        }

        if ($request->cancelled_at) {
            $steps[] = [
                'stage'     => 'cancelled',
                'label'     => 'Cancelled',
                'status'    => 'cancelled',
                'timestamp' => $request->cancelled_at,
                'user'      => $this->userName($request->cancelledBy),
                'summary'   => $request->cancellation_reason,
                'action'    => null,
            ];
        }

        return $steps;
    }

    protected function userName($user): ?string
    {
        if (! $user) return null;
        return $user->full_name ?? $user->name ?? ('User #' . $user->id);
    }
}
