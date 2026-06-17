<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\InvoiceReceivable;
use App\Models\ReceivableAssignment;
use App\Models\ReceivableCase;
use App\Models\ReceivableDispute;
use App\Models\ReceivableDunningNotice;
use App\Models\ReceivableFollowup;
use App\Models\ReceivablePromise;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableCaseService
{
    public function __construct(
        protected ReceivableWorkbenchService $workbench,
        protected ActivityLogService $activity,
    ) {}

    public function openFromReceivable(InvoiceReceivable $receivable, array $data, User $user): ReceivableCase
    {
        $activeExists = ReceivableCase::active()
            ->whereHas('items', fn ($query) => $query
                ->where('source_type', InvoiceReceivable::class)
                ->where('source_id', $receivable->id))
            ->exists();

        if ($activeExists && empty($data['allow_duplicate_active'])) {
            throw ValidationException::withMessages(['invoice_receivable_id' => 'This receivable already has an active collection case.']);
        }

        return DB::transaction(function () use ($receivable, $data, $user) {
            $receivable->loadMissing(['invoice', 'patient', 'insuranceProvider', 'sponsor', 'corporateClient', 'claim']);
            $bucket = $this->workbench->bucketForReceivable($receivable);
            $case = ReceivableCase::create([
                'case_number' => $this->nextCaseNumber(),
                'payer_type' => $receivable->payer_type,
                'payer_id' => $receivable->payer_id,
                'payer_name_snapshot' => $receivable->payerName(),
                'patient_id' => $receivable->patient_id,
                'insurance_provider_id' => $receivable->insurance_provider_id,
                'sponsor_id' => $receivable->sponsor_id,
                'corporate_client_id' => $receivable->corporate_client_id,
                'claim_id' => $receivable->claim_id,
                'case_type' => $data['case_type'] ?? $this->defaultCaseType($receivable->payer_type),
                'priority' => $data['priority'] ?? 'normal',
                'status' => ReceivableCase::STATUS_OPEN,
                'opened_by' => $user->id,
                'opened_at' => now(),
                'total_original_amount' => $receivable->original_amount,
                'total_outstanding_amount' => $receivable->balance,
                'oldest_due_date' => $receivable->due_date,
                'aging_bucket' => $bucket,
                'notes' => $data['notes'] ?? null,
            ]);

            $case->items()->create([
                'source_type' => InvoiceReceivable::class,
                'source_id' => $receivable->id,
                'invoice_id' => $receivable->invoice_id,
                'invoice_number' => $receivable->invoice?->invoice_number,
                'claim_id' => $receivable->claim_id,
                'payer_type' => $receivable->payer_type,
                'payer_id' => $receivable->payer_id,
                'original_amount' => $receivable->original_amount,
                'outstanding_amount' => $receivable->balance,
                'due_date' => $receivable->due_date,
                'aging_bucket' => $bucket,
                'status' => 'open',
                'metadata_snapshot' => json_encode(['accounting_status' => $receivable->accounting_status]),
            ]);

            if (! empty($data['assigned_to'])) {
                $this->assign($case, User::findOrFail($data['assigned_to']), $user);
            }

            $this->activity->log(LogModule::BILLING, 'RECEIVABLE_CASE_OPENED', ['case_number' => $case->case_number], $case);

            return $case->refresh()->load(['items', 'assignee']);
        });
    }

    public function assign(ReceivableCase $case, User $assignee, User $assignedBy): ReceivableAssignment
    {
        return DB::transaction(function () use ($case, $assignee, $assignedBy) {
            ReceivableAssignment::where('receivable_case_id', $case->id)
                ->whereNull('released_at')
                ->update(['released_at' => now(), 'release_reason' => 'Reassigned']);

            $assignment = ReceivableAssignment::create([
                'receivable_case_id' => $case->id,
                'assigned_to' => $assignee->id,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
            ]);
            $case->update(['assigned_to' => $assignee->id, 'status' => ReceivableCase::STATUS_IN_PROGRESS]);
            $this->activity->log(LogModule::BILLING, 'RECEIVABLE_CASE_ASSIGNED', ['assigned_to' => $assignee->id], $case);

            return $assignment;
        });
    }

    public function addFollowup(ReceivableCase $case, array $data, User $user): ReceivableFollowup
    {
        $followup = $case->followups()->create($data + ['created_by' => $user->id]);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_FOLLOWUP_CREATED', ['outcome' => $followup->outcome], $case);

        return $followup;
    }

    public function addPromise(ReceivableCase $case, array $data, User $user): ReceivablePromise
    {
        $promise = $case->promises()->create($data + [
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $this->refreshCaseTotals($case);
        $case->update(['status' => ReceivableCase::STATUS_PROMISED]);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_PROMISE_CREATED', ['amount' => $promise->promised_amount], $case);

        return $promise;
    }

    public function addDispute(ReceivableCase $case, array $data, User $user): ReceivableDispute
    {
        $dispute = $case->disputes()->create($data + [
            'status' => 'open',
            'raised_by' => $user->id,
            'raised_at' => now(),
        ]);
        $this->refreshCaseTotals($case);
        $case->update(['status' => ReceivableCase::STATUS_DISPUTED]);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_DISPUTE_CREATED', ['amount' => $dispute->disputed_amount], $case);

        return $dispute;
    }

    public function generateDunningNotice(ReceivableCase $case, array $data, User $user): ReceivableDunningNotice
    {
        $level = $data['notice_level'] ?? 'friendly_reminder';
        $subject = $data['subject'] ?? 'Receivable reminder '.$case->case_number;
        $body = $data['body'] ?? $this->defaultNoticeBody($case, $level);

        $notice = $case->dunningNotices()->create([
            'notice_number' => $this->nextNoticeNumber(),
            'notice_level' => $level,
            'notice_date' => $data['notice_date'] ?? today()->toDateString(),
            'delivery_channel' => $data['delivery_channel'] ?? 'print',
            'recipient_name' => $data['recipient_name'] ?? $case->payer_name_snapshot,
            'recipient_contact' => $data['recipient_contact'] ?? null,
            'subject' => $subject,
            'body' => $body,
            'status' => 'generated',
            'generated_by' => $user->id,
            'generated_at' => now(),
            'metadata_snapshot' => json_encode(['case_balance' => $case->total_outstanding_amount]),
        ]);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_DUNNING_GENERATED', ['notice_number' => $notice->notice_number], $case);

        return $notice;
    }

    public function refreshCaseTotals(ReceivableCase $case): ReceivableCase
    {
        $case->loadMissing(['items', 'promises', 'disputes']);
        $case->update([
            'total_original_amount' => round((float) $case->items->sum('original_amount'), 2),
            'total_outstanding_amount' => round((float) $case->items->sum('outstanding_amount'), 2),
            'total_disputed_amount' => round((float) $case->disputes->whereIn('status', ['open', 'under_review'])->sum('disputed_amount'), 2),
            'total_promised_amount' => round((float) $case->promises->where('status', 'active')->sum('promised_amount'), 2),
        ]);

        return $case->refresh();
    }

    private function defaultCaseType(string $payerType): string
    {
        return match ($payerType) {
            InvoiceReceivable::PAYER_INSURANCE => 'insurance_followup',
            InvoiceReceivable::PAYER_SPONSOR => 'sponsor_followup',
            InvoiceReceivable::PAYER_CORPORATE => 'corporate_followup',
            default => 'normal_collection',
        };
    }

    private function defaultNoticeBody(ReceivableCase $case, string $level): string
    {
        return "Dear {$case->payer_name_snapshot},\n\nThis {$level} concerns outstanding receivables of GHS "
            .number_format((float) $case->total_outstanding_amount, 2)
            ." under case {$case->case_number}. Please contact the finance office for settlement or clarification.";
    }

    private function nextCaseNumber(): string
    {
        $prefix = 'RC-'.now()->format('Y').'-';
        $last = ReceivableCase::where('case_number', 'like', $prefix.'%')->orderByDesc('case_number')->value('case_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextNoticeNumber(): string
    {
        $prefix = 'DN-'.now()->format('Y').'-';
        $last = ReceivableDunningNotice::where('notice_number', 'like', $prefix.'%')->orderByDesc('notice_number')->value('notice_number');
        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
