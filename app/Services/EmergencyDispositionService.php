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

            // Activity log → patient timeline. Emergency owns the disposition event;
            // Admission owns the subsequent ADMISSION_CREATED (a separate workflow event).
            $fresh = $case->fresh(['visit', 'patient', 'bay', 'disposedBy']);
            [$event, $label] = $this->dispositionEvent($disposition);
            $death = in_array($disposition, [EmergencyCase::DISPOSITION_DIED, EmergencyCase::DISPOSITION_DEAD_ON_ARRIVAL], true);
            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::EMERGENCY,
                $event,
                $fresh->toActivityContext() + array_filter([
                    'reason' => $data['disposition_notes'] ?? null,
                    'severity' => $death ? \App\Enums\LogSeverity::WARNING : \App\Enums\LogSeverity::INFO,
                    'old_values' => ['emergency_status' => EmergencyCase::STATUS_UNDER_CARE],
                    'new_values' => ['emergency_status' => EmergencyCase::STATUS_DISPOSED, 'disposition' => $disposition],
                    'metadata' => ['disposition' => $disposition],
                    'causer' => $user,
                ], fn ($v) => $v !== null),
                $fresh,
                $label,
            );

            return $fresh;
        });
    }

    /** @return array{0:string,1:string} [event, description] */
    private function dispositionEvent(string $disposition): array
    {
        return match ($disposition) {
            EmergencyCase::DISPOSITION_ADMITTED => ['EMERGENCY_TRANSFERRED_TO_ADMISSION', 'Emergency disposition: transferred to admission'],
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_OPD => ['EMERGENCY_TRANSFERRED_TO_OPD', 'Emergency disposition: transferred to OPD'],
            EmergencyCase::DISPOSITION_TRANSFERRED_TO_THEATRE => ['EMERGENCY_TRANSFERRED_TO_THEATRE', 'Emergency disposition: transferred to theatre'],
            EmergencyCase::DISPOSITION_REFERRED_OUT => ['EMERGENCY_REFERRED_OUT', 'Emergency disposition: referred out'],
            EmergencyCase::DISPOSITION_LEFT_AGAINST_MEDICAL_ADVICE => ['EMERGENCY_LEFT_AGAINST_MEDICAL_ADVICE', 'Emergency disposition: left against medical advice'],
            EmergencyCase::DISPOSITION_ABSCONDED => ['EMERGENCY_ABSCONDED', 'Emergency disposition: absconded'],
            EmergencyCase::DISPOSITION_DIED => ['EMERGENCY_DEATH_RECORDED', 'Emergency death recorded'],
            EmergencyCase::DISPOSITION_DEAD_ON_ARRIVAL => ['EMERGENCY_DOA_RECORDED', 'Dead on arrival recorded'],
            EmergencyCase::DISPOSITION_DISCHARGED => ['EMERGENCY_DISPOSITION_COMPLETED', 'Emergency disposition completed: discharged'],
            default => ['EMERGENCY_DISPOSITION_COMPLETED', 'Emergency disposition completed: ' . $disposition],
        };
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
