<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\ReceivableCase;
use App\Models\User;

class ReceivableRecommendationService
{
    public function __construct(protected ActivityLogService $activity) {}

    public function recommendWriteoff(ReceivableCase $case, array $data, User $user)
    {
        $recommendation = $case->writeoffRecommendations()->create([
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'recommended_amount' => $data['recommended_amount'],
            'reason' => $data['reason'],
            'status' => 'recommended',
            'recommended_by' => $user->id,
            'recommended_at' => now(),
        ]);
        $case->update(['status' => 'recommended_writeoff']);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_WRITEOFF_RECOMMENDED', ['amount' => $recommendation->recommended_amount], $case);

        return $recommendation;
    }

    public function recommendCreditNote(ReceivableCase $case, array $data, User $user)
    {
        $recommendation = $case->creditnoteRecommendations()->create([
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'recommended_amount' => $data['recommended_amount'],
            'reason' => $data['reason'],
            'status' => 'recommended',
            'recommended_by' => $user->id,
            'recommended_at' => now(),
        ]);
        $case->update(['status' => 'recommended_credit_note']);
        $this->activity->log(LogModule::BILLING, 'RECEIVABLE_CREDITNOTE_RECOMMENDED', ['amount' => $recommendation->recommended_amount], $case);

        return $recommendation;
    }
}
