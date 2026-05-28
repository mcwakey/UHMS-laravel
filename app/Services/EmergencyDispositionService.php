<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmergencyDispositionService
{
    public function __construct(
        private EmergencyBayService $bays,
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function dispose(EmergencyCase $case, array $data, User $user): EmergencyCase
    {
        return DB::transaction(function () use ($case, $data, $user) {
            $disposition = $data['disposition'];

            $case->update([
                'emergency_status' => EmergencyCase::STATUS_DISPOSED,
                'disposition' => $disposition,
                'disposition_notes' => $data['disposition_notes'] ?? null,
                'disposition_time' => $data['disposition_time'] ?? now(),
                'disposed_by' => $user->id,
            ]);

            $this->applyVisitDisposition($case->fresh('visit.patient'), $data, $user);
            $this->bays->release($case->fresh('bay'));
            $this->completeSession($case->fresh('activeEmergencySession'), $user);
            $this->timeline->record($case->fresh(), 'DISPOSITION', 'Emergency case disposed', $disposition, $case, $user);

            return $case->fresh(['visit', 'patient', 'bay', 'disposedBy']);
        });
    }

    private function applyVisitDisposition(EmergencyCase $case, array $data, User $user): void
    {
        $visit = $case->visit;
        $disposition = $data['disposition'];

        match ($disposition) {
            EmergencyCase::DISPOSITION_ADMITTED => $visit->update([
                'status' => VisitStatus::ADMITTING->value,
                'visit_type' => VisitType::INPATIENT->value,
            ]),
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_OPD => $visit->update([
                'status' => VisitStatus::WAITING_CONSULTATION->value,
                'visit_type' => VisitType::OUTPATIENT->value,
            ]),
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_THEATRE => $visit->update([
                'status' => VisitStatus::CONSULTING->value,
            ]),
            EmergencyCase::DISPOSITION_DISCHARGED,
            EmergencyCase::DISPOSITION_REFERRED_OUT,
            EmergencyCase::DISPOSITION_LEFT_AGAINST_MEDICAL_ADVICE,
            EmergencyCase::DISPOSITION_ABSCONDED => $visit->update([
                'status' => VisitStatus::COMPLETED->value,
                'checked_out_at' => now(),
            ]),
            EmergencyCase::DISPOSITION_DIED,
            EmergencyCase::DISPOSITION_DEAD_ON_ARRIVAL => $this->markDeath($case, $data, $user),
            default => null,
        };
    }

    private function markDeath(EmergencyCase $case, array $data, User $user): void
    {
        $case->patient->update([
            'is_deceased' => true,
            'deceased_at' => now()->toDateString(),
            'cause_of_death' => $data['cause_of_death'] ?? null,
            'deceased_notes' => $data['disposition_notes'] ?? null,
            'marked_deceased_by' => $user->id,
        ]);

        $case->visit->update([
            'status' => VisitStatus::COMPLETED->value,
            'checked_out_at' => now(),
        ]);
    }

    private function completeSession(EmergencyCase $case, User $user): void
    {
        $session = $case->activeEmergencySession;
        if (! $session) {
            return;
        }

        $session->update([
            'status' => \App\Models\EmergencySession::STATUS_COMPLETED,
            'ended_at' => now(),
            'ended_by' => $user->id,
        ]);
        $this->sessions->recordContribution($case, $user, 'Disposition');
    }
}
