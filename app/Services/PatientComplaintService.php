<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintCatalogue;
use App\Models\EmergencyCase;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\Auth;

class PatientComplaintService
{
    public const DURATION_UNITS = ['minutes', 'hours', 'days', 'weeks', 'months', 'years'];

    public const SEVERITIES = ['mild', 'moderate', 'severe', 'critical'];

    private const EMERGENCY_CHIEF_COMPLAINT_NOTE = 'Captured from emergency case chief complaint.';

    public function __construct(
        protected ConsultationContributorService $contributors,
        protected MedicalRecordEntryLogService $entryLogs,
    ) {}

    public function createForRecord(MedicalRecord $record, array $data, ?User $user = null): Complaint
    {
        $this->assertRecordEditable($record, $user);

        $user ??= Auth::user();
        $context = $this->entryContext($record, $user);
        $payload = array_merge($context, $this->normalisePayload($data));

        foreach (['emergency_case_id', 'emergency_session_id', 'admission_id'] as $contextKey) {
            $payload[$contextKey] = $payload[$contextKey] ?? $context[$contextKey] ?? null;
        }

        $duplicate = $this->findExactDuplicate($record, $payload);
        if ($duplicate) {
            return $duplicate->load(['creator', 'sourcePattern', 'complaintCatalogue']);
        }

        $complaint = $record->complaints()->create($payload);

        if ($user) {
            $this->contributors->recordContribution($record, $user, 'Complaint');
            $this->entryLogs->created($complaint, $user);
        }

        return $complaint->load(['creator', 'sourcePattern', 'complaintCatalogue']);
    }

    public function update(Complaint $complaint, array $data, ?User $user = null): Complaint
    {
        $user ??= Auth::user();
        $this->assertRecordEditable($complaint->medicalRecord, $user);

        $old = $complaint->getOriginal();
        $complaint->update(array_merge($this->normalisePayload($data), ['updated_by' => $user?->id]));

        if ($user) {
            $this->entryLogs->updated($complaint, $old, $user);
        }

        return $complaint->fresh(['creator', 'updater', 'sourcePattern', 'complaintCatalogue']);
    }

    public function delete(Complaint $complaint, ?User $user = null): void
    {
        $user ??= Auth::user();
        $this->assertRecordEditable($complaint->medicalRecord, $user);

        if ($user) {
            $this->entryLogs->deleted($complaint, $user);
        }

        $complaint->delete();
    }

    public function syncEmergencyChiefComplaint(EmergencyCase $case, MedicalRecord $record, ?User $user = null): ?Complaint
    {
        $text = trim((string) $case->chief_complaint);
        if ($text === '') {
            return null;
        }

        $user ??= Auth::user();
        $record->loadMissing('consultationRoute.emergencySession');
        $context = $this->entryContext($record, $user);

        $complaint = Complaint::query()
            ->where('medical_record_id', $record->id)
            ->where('emergency_case_id', $case->id)
            ->where('notes', self::EMERGENCY_CHIEF_COMPLAINT_NOTE)
            ->oldest('id')
            ->first();

        $payload = array_merge($context, [
            'description' => $text,
            'emergency_case_id' => $case->id,
            'emergency_session_id' => $record->consultationRoute?->emergencySession?->id,
            'notes' => self::EMERGENCY_CHIEF_COMPLAINT_NOTE,
            'created_by' => $complaint?->created_by ?: ($user?->id ?? $case->created_by),
            'doctor_id' => $complaint?->doctor_id ?: ($user?->id ?? $record->doctor_id),
        ]);

        if ($complaint) {
            $old = $complaint->getOriginal();
            $complaint->update(array_merge($payload, ['updated_by' => $user?->id]));
            if ($user) {
                $this->entryLogs->updated($complaint, $old, $user);
            }

            return $complaint->fresh(['creator', 'updater', 'sourcePattern', 'complaintCatalogue']);
        }

        $complaint = $record->complaints()->create($payload);
        if ($user) {
            $this->contributors->recordContribution($record, $user, 'Emergency Complaint');
            $this->entryLogs->created($complaint, $user);
        }

        return $complaint->load(['creator', 'sourcePattern', 'complaintCatalogue']);
    }

    private function normalisePayload(array $data): array
    {
        $catalogueId = $data['complaint_catalogue_id'] ?? null;
        $catalogue = $catalogueId ? ComplaintCatalogue::query()->active()->find($catalogueId) : null;
        $description = trim((string) ($data['description'] ?? $data['complaint_text'] ?? ''));

        if ($description === '' && $catalogue) {
            $description = $catalogue->name;
        }

        $payload = [
            'complaint_catalogue_id' => $catalogue?->id,
            'description' => $description,
            'duration' => $this->nullableString($data['duration'] ?? null),
            'duration_unit' => in_array(($data['duration_unit'] ?? null), self::DURATION_UNITS, true) ? $data['duration_unit'] : null,
            'severity' => in_array(($data['severity'] ?? null), self::SEVERITIES, true) ? $data['severity'] : null,
            'notes' => $this->nullableString($data['notes'] ?? null),
        ];

        foreach (['source_pattern_id', 'emergency_case_id', 'emergency_session_id', 'admission_id'] as $optionalLink) {
            if (array_key_exists($optionalLink, $data)) {
                $payload[$optionalLink] = $data[$optionalLink];
            }
        }

        return $payload;
    }

    private function entryContext(MedicalRecord $record, ?User $user): array
    {
        $record->loadMissing(['consultationRoute.emergencySession', 'visit.admission']);

        return [
            'consultation_route_id' => $record->consultation_route_id,
            'visit_id' => $record->visit_id,
            'patient_id' => $record->patient_id,
            'department_id' => $record->department_id,
            'doctor_id' => $user?->id ?? $record->doctor_id,
            'created_by' => $user?->id ?? $record->doctor_id,
            'emergency_case_id' => $record->consultationRoute?->emergency_case_id,
            'emergency_session_id' => $record->consultationRoute?->emergencySession?->id,
            'admission_id' => $record->visit?->admission?->id,
        ];
    }

    private function findExactDuplicate(MedicalRecord $record, array $payload): ?Complaint
    {
        return Complaint::query()
            ->where('medical_record_id', $record->id)
            ->where('created_by', $payload['created_by'])
            ->where('description', $payload['description'])
            ->where('complaint_catalogue_id', $payload['complaint_catalogue_id'])
            ->where('duration', $payload['duration'])
            ->where('duration_unit', $payload['duration_unit'])
            ->where('severity', $payload['severity'])
            ->where('notes', $payload['notes'])
                ->where('source_pattern_id', $payload['source_pattern_id'] ?? null)
            ->latest('id')
            ->first();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function assertRecordEditable(?MedicalRecord $record, ?User $user): void
    {
        if (! $record) {
            throw new \RuntimeException('A medical record is required before recording complaints.');
        }

        $route = $record->consultationRoute;
        if (! $route || (! $route->locked_at && ! in_array($route->status, [
            VisitConsultationRoute::STATUS_COMPLETED,
            VisitConsultationRoute::STATUS_CANCELLED,
        ], true))) {
            return;
        }

        if ($user && ($user->can('consultation.entries.correct_completed') || $user->can('visits.reopen_locked_session'))) {
            return;
        }

        throw new \RuntimeException('This consultation session is locked or completed. Use correction permission to amend it.');
    }
}
