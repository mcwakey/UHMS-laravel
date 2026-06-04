<?php

namespace App\Services\Claims;

use App\Enums\ClaimStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Claim;
use App\Models\ClaimStatusLog;
use App\Models\User;
use App\Services\ActivityLogService;

class ClaimStatusService
{
    public function transition(Claim $claim, ClaimStatus $toStatus, ?User $user = null, ?string $notes = null, array $extra = []): Claim
    {
        $fromStatus = $claim->status;

        $claim->forceFill(array_merge($extra, [
            'status' => $toStatus,
        ]))->save();

        ClaimStatusLog::create([
            'claim_id' => $claim->id,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'notes' => $notes,
            'performed_by' => $user?->id,
        ]);

        // Mirror onto the central activity log → patient timeline. Single funnel
        // for the claim lifecycle (submit/review/approve/reject/paid/appeal). The
        // initial DRAFT status is set at creation (no transition), so the
        // CLAIM_PREPARED log lives in ClaimService and is not duplicated here.
        try {
            $warning = in_array($toStatus, [ClaimStatus::REJECTED, ClaimStatus::CANCELLED], true);
            app(ActivityLogService::class)->log(
                LogModule::CLAIMS,
                $this->statusEvent($toStatus),
                $claim->toActivityContext() + array_filter([
                    'reason' => $notes,
                    'severity' => $warning ? LogSeverity::WARNING : LogSeverity::INFO,
                    'old_values' => $fromStatus ? ['status' => $fromStatus->value] : null,
                    'new_values' => ['status' => $toStatus->value],
                    'metadata' => array_filter([
                        'claim_number' => $claim->claim_number,
                        'total_amount' => $claim->total_claim_amount ?: $claim->total_amount,
                        'approved_amount' => $claim->approved_amount,
                        'paid_amount' => $claim->paid_amount,
                    ], fn ($v) => $v !== null && $v !== ''),
                    'causer' => $user,
                ], fn ($v) => $v !== null),
                $claim,
                $this->statusDescription($toStatus, $claim, $notes),
            );
        } catch (\Throwable $e) {
            // Logging must never break a claim transition.
        }

        return $claim->fresh(['statusLogs']);
    }

    private function statusEvent(ClaimStatus $to): string
    {
        return match ($to) {
            ClaimStatus::READY => 'CLAIM_MARKED_READY',
            ClaimStatus::SUBMITTED => 'CLAIM_SUBMITTED',
            ClaimStatus::ACKNOWLEDGED => 'CLAIM_ACKNOWLEDGED',
            ClaimStatus::UNDER_REVIEW => 'CLAIM_REVIEWED',
            ClaimStatus::APPROVED => 'CLAIM_APPROVED',
            ClaimStatus::PARTIALLY_APPROVED => 'CLAIM_PARTIALLY_APPROVED',
            ClaimStatus::REJECTED => 'CLAIM_REJECTED',
            ClaimStatus::RESUBMITTED => 'CLAIM_RESUBMITTED',
            ClaimStatus::APPEALED => 'CLAIM_APPEALED',
            ClaimStatus::PARTIALLY_PAID, ClaimStatus::PAID => 'CLAIM_PAYMENT_RECORDED',
            ClaimStatus::CANCELLED => 'CLAIM_CANCELLED',
            default => 'CLAIM_STATUS_CHANGED',
        };
    }

    private function statusDescription(ClaimStatus $to, Claim $claim, ?string $notes): string
    {
        $ref = $claim->claim_number ?: ('#' . $claim->id);
        $base = match ($to) {
            ClaimStatus::READY => "Claim marked ready: {$ref}",
            ClaimStatus::SUBMITTED => "Claim submitted: {$ref}",
            ClaimStatus::ACKNOWLEDGED => "Claim acknowledged: {$ref}",
            ClaimStatus::UNDER_REVIEW => "Claim review started: {$ref}",
            ClaimStatus::APPROVED => "Claim approved: {$ref}",
            ClaimStatus::PARTIALLY_APPROVED => "Claim partially approved: {$ref}",
            ClaimStatus::REJECTED => "Claim rejected: {$ref}",
            ClaimStatus::RESUBMITTED => "Claim resubmitted: {$ref}",
            ClaimStatus::APPEALED => "Claim appealed: {$ref}",
            ClaimStatus::PARTIALLY_PAID, ClaimStatus::PAID => "Claim payment recorded: {$ref}",
            ClaimStatus::CANCELLED => "Claim cancelled: {$ref}",
            default => "Claim status changed: {$ref}",
        };

        return $notes && in_array($to, [ClaimStatus::REJECTED, ClaimStatus::CANCELLED], true)
            ? "{$base} — {$notes}"
            : $base;
    }
}
