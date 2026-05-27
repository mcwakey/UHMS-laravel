<?php

namespace App\Services;

use App\Models\EmergencyCase;
use App\Models\EmergencyNote;
use App\Models\User;

class EmergencyNoteService
{
    public function __construct(private EmergencyTimelineService $timeline) {}

    public function create(EmergencyCase $case, array $data, User $user): EmergencyNote
    {
        $note = EmergencyNote::create([
            'emergency_case_id' => $case->id,
            'visit_id' => $case->visit_id,
            'patient_id' => $case->patient_id,
            'note_type' => $data['note_type'] ?? EmergencyNote::TYPE_GENERAL_NOTE,
            'content' => $data['content'],
            'created_by' => $user->id,
        ]);

        $this->timeline->record($case, 'NOTE_CREATED', 'Emergency note added', $note->content, $note, $user);

        return $note;
    }
}
