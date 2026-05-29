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
        private VisitStatusService $statuses,
        private VisitPathwayService $pathway,
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
            if ($disposition !== EmergencyCase::DISPOSITION_ADMITTED) {
                $this->bays->release($case->fresh('bay'), user: $user);
            }
            $this->sessions->completeForDisposition($case->fresh(['activeEmergencySession.consultationRoute', 'emergencySessions.consultationRoute']), $user);
            $this->timeline->record($case->fresh(), 'DISPOSITION', 'Emergency case disposed', $disposition, $case, $user);

            $this->pathway->record($case->visit, 'EMERGENCY_DISPOSITION', [
                'source' => $case,
                'status' => $disposition,
                'title' => 'Emergency disposition recorded',
                'description' => $disposition === EmergencyCase::DISPOSITION_ADMITTED
                    ? 'Patient accepted for admission; emergency bed billing continues until ward bed assignment.'
                    : ($data['disposition_notes'] ?? $disposition),
            ]);

            return $case->fresh(['visit', 'patient', 'bay', 'disposedBy']);
        });
    }

    private function applyVisitDisposition(EmergencyCase $case, array $data, User $user): void
    {
        $visit = $case->visit;
        $disposition = $data['disposition'];

        match ($disposition) {
            EmergencyCase::DISPOSITION_ADMITTED => $this->markAdmitting($visit),
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_OPD => $this->markTransferredToOpd($visit),
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_THEATRE => $this->statuses->setConsulting($visit, 'Transferred to theatre'),
            EmergencyCase::DISPOSITION_DISCHARGED,
            EmergencyCase::DISPOSITION_REFERRED_OUT,
            EmergencyCase::DISPOSITION_LEFT_AGAINST_MEDICAL_ADVICE,
            EmergencyCase::DISPOSITION_ABSCONDED => $this->statuses->setCompleted($visit, 'Emergency case completed'),
            EmergencyCase::DISPOSITION_DIED,
            EmergencyCase::DISPOSITION_DEAD_ON_ARRIVAL => $this->markDeath($case, $data, $user),
            default => null,
        };
    }

    private function markAdmitting($visit): void
    {
        if ($visit->canTransitionTo(VisitStatus::ADMITTING)) {
            $visit->transitionTo(VisitStatus::ADMITTING, 'Emergency patient accepted for admission');
        } else {
            $visit->update(['status' => VisitStatus::ADMITTING->value]);
        }

        $visit->update(['visit_type' => VisitType::INPATIENT->value]);
    }

    private function markTransferredToOpd($visit): void
    {
        $visit->update(['visit_type' => VisitType::OUTPATIENT->value]);
        $this->statuses->setWaitingConsultation($visit, 'Transferred from emergency to OPD consultation');
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

        $this->statuses->transition($case->visit, VisitStatus::DECEASED, 'Patient died in emergency care');
    }

}
