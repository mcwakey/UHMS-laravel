<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\EmergencyNote;
use App\Models\User;

class EmergencyNoteService
{
    public function __construct(
        private EmergencyTimelineService $timeline,
        private EmergencySessionService $sessions,
    ) {}

    public function create(EmergencyCase $case, array $data, User $user): EmergencyNote
    {
        $session = $this->sessions->getOrCreateForCase($case, $user);

        $note = EmergencyNote::create([
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $session->id,
            'medical_record_id' => $session->medical_record_id,
            'consultation_route_id' => $session->consultation_route_id,
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'note_type' => $data['note_type'] ?? EmergencyNote::TYPE_GENERAL_NOTE,
            'content' => $data['content'],
            'created_by' => $user->id,
        ]);

        $this->sessions->recordContribution($case, $user, $this->roleForNoteType($note->note_type));
        $this->timeline->record($case, 'NOTE_CREATED', 'Emergency note added', $note->content, $note, $user);

        return $note;
    }

    private function roleForNoteType(string $type): string
    {
        return match ($type) {
            EmergencyNote::TYPE_DOCTOR_ASSESSMENT => 'Doctor Assessment',
            EmergencyNote::TYPE_NURSING_NOTE => 'Nursing Note',
            EmergencyNote::TYPE_RESUSCITATION_NOTE => 'Resuscitation Note',
            EmergencyNote::TYPE_OBSERVATION_NOTE => 'Observation Note',
            default => 'Emergency Note',
        };
    }
}
