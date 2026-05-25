<?php

namespace App\Services\Claims;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\ClaimPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClaimPaymentService
{
    public function __construct(private ClaimStatusService $statusService) {}

    public function record(Claim $claim, array $data, User $user): ClaimPayment
    {
        return DB::transaction(function () use ($claim, $data, $user) {
            $payment = ClaimPayment::create([
                'claim_id' => $claim->id,
                'insurance_type_id' => $claim->insurance_type_id,
                'insurance_provider_id' => $claim->insurance_provider_id,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'received_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $paid = (float) $claim->payments()->sum('amount');
            $approved = (float) ($claim->approved_amount ?: $claim->total_claim_amount ?: $claim->total_amount);
            $status = $approved > 0 && $paid >= $approved ? ClaimStatus::PAID : ClaimStatus::PARTIALLY_PAID;

            $this->statusService->transition($claim, $status, $user, 'Insurer claim payment recorded.', [
                'paid_amount' => $paid,
            ]);

            return $payment;
        });
    }
}
