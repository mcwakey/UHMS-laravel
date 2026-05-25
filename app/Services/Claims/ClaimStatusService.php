<?php

namespace App\Services\Claims;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\ClaimStatusLog;
use App\Models\User;

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

        return $claim->fresh(['statusLogs']);
    }
}
