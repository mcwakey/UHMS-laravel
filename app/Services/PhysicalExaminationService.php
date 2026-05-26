<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\PhysicalExamination;
use App\Models\User;

class PhysicalExaminationService
{
    public function __construct(
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
    ) {}

    public function create(MedicalRecord $record, array $data, User $user): PhysicalExamination
    {
        $entry = $record->physicalExaminations()->create(array_merge(
            $this->context($record, $user),
            $data,
        ));

        $this->contributors->recordContribution($record, $user, 'Examination');
        $this->entryLogs->created($entry, $user);

        return $entry->load(['creator', 'doctor', 'sourcePattern']);
    }

    private function context(MedicalRecord $record, User $user): array
    {
        return [
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'consultation_route_id' => $record->consultation_route_id,
            'doctor_id' => $user->id,
            'created_by' => $user->id,
        ];
    }
}
