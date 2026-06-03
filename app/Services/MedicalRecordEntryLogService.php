<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Models\MedicalRecordEntryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Canonical funnel for clinical medical-record entry changes (complaint, HOPC,
 * examination, diagnosis, treatment, prescription, investigation, …). It keeps
 * the clinical-specific `medical_record_entry_logs` table AND mirrors each change
 * to the central activity log so it surfaces on the patient profile timeline.
 *
 * Clinical entries carry their own patient/visit/medical-record context, so the
 * mirror attaches it explicitly (no inference).
 */
class MedicalRecordEntryLogService
{
    public function created(Model $entry, ?User $user = null): void
    {
        $this->write($entry, 'CREATED', null, $entry->getAttributes(), $user);
    }

    public function updated(Model $entry, array $oldValue, ?User $user = null, ?string $reason = null, bool $override = false): void
    {
        $this->write($entry, $override ? 'OVERRIDE_UPDATED' : 'UPDATED', $oldValue, $entry->getChanges(), $user, $reason);
    }

    public function deleted(Model $entry, ?User $user = null, ?string $reason = null, bool $override = false): void
    {
        $this->write($entry, $override ? 'OVERRIDE_DELETED' : 'DELETED', $entry->getAttributes(), null, $user, $reason);
    }

    private function write(Model $entry, string $action, ?array $oldValue, ?array $newValue, ?User $user = null, ?string $reason = null): void
    {
        MedicalRecordEntryLog::create([
            'entry_type' => $entry::class,
            'entry_id' => $entry->getKey(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'performed_by' => $user?->id,
        ]);

        // Mirror to the central activity log → patient timeline. Never let the
        // mirror break a clinical save.
        try {
            $this->mirrorToActivityLog($entry, $action, $oldValue, $newValue, $user, $reason);
        } catch (\Throwable $e) {
            // ActivityLogService also self-protects; this is a final safety net.
        }
    }

    private function mirrorToActivityLog(Model $entry, string $action, ?array $oldValue, ?array $newValue, ?User $user, ?string $reason): void
    {
        [$short, $label] = $this->descriptorFor($entry);
        $verb = $this->verbFor($action);

        $data = $this->clinicalContext($entry) + array_filter([
            'reason' => $reason,
            'severity' => str_starts_with($action, 'OVERRIDE') ? 'WARNING' : 'INFO',
        ], fn ($v) => $v !== null);

        // Only attach meaningful changed fields (not timestamps / context ids).
        if ($action === 'UPDATED' || $action === 'OVERRIDE_UPDATED') {
            $changed = $this->clean((array) $newValue);
            if ($changed !== []) {
                $data['new_values'] = $changed;
                $data['old_values'] = array_intersect_key($this->clean((array) $oldValue), $changed);
            }
        } elseif ($action === 'DELETED' || $action === 'OVERRIDE_DELETED') {
            $old = $this->clean((array) $oldValue);
            if ($old !== []) {
                $data['old_values'] = $old;
            }
        }

        $snippet = $this->snippet($entry);
        $description = trim("{$label} {$verb}" . ($snippet ? ": {$snippet}" : ''));

        app(ActivityLogService::class)->log(
            LogModule::CONSULTATION,
            $short . '_' . strtoupper($verb),
            $data,
            $entry,
            $description,
        );
    }

    /** @return array{0:string,1:string} [short action prefix, human label] */
    private function descriptorFor(Model $entry): array
    {
        return match (class_basename($entry)) {
            'Complaint' => ['COMPLAINT', 'Complaint'],
            'HistoryOfPresentingComplaint' => ['HOPC', 'History of presenting complaint'],
            'PhysicalExamination' => ['EXAMINATION', 'Examination'],
            'Diagnosis' => ['DIAGNOSIS', 'Diagnosis'],
            'Treatment' => ['TREATMENT', 'Treatment'],
            'Prescription' => ['PRESCRIPTION', 'Prescription'],
            'Investigation' => ['INVESTIGATION', 'Investigation'],
            'ProcedureRequest' => ['PROCEDURE_REQUEST', 'Procedure request'],
            'LabRequest' => ['LAB_REQUEST', 'Lab request'],
            'ConsultationNote' => ['NOTE', 'Clinical note'],
            default => [strtoupper(Str::snake(class_basename($entry))), Str::headline(class_basename($entry))],
        };
    }

    private function verbFor(string $action): string
    {
        return match ($action) {
            'CREATED' => 'added',
            'DELETED', 'OVERRIDE_DELETED' => 'removed',
            'OVERRIDE_UPDATED' => 'corrected',
            default => 'updated',
        };
    }

    private function clinicalContext(Model $entry): array
    {
        $a = $entry->getAttributes();

        return array_filter([
            'patient_id' => $a['patient_id'] ?? null,
            'visit_id' => $a['visit_id'] ?? null,
            'medical_record_id' => $a['medical_record_id'] ?? null,
            'consultation_route_id' => $a['consultation_route_id'] ?? null,
            'department_id' => $a['department_id'] ?? null,
            'source_type' => class_basename($entry),
            'source_id' => $entry->getKey(),
        ], fn ($v) => $v !== null);
    }

    /** Strip context / meta keys, leaving the clinically meaningful fields. */
    private function clean(array $values): array
    {
        $drop = [
            'id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by',
            'patient_id', 'visit_id', 'medical_record_id', 'consultation_route_id',
            'department_id', 'source_pattern_id',
        ];

        return array_diff_key($values, array_flip($drop));
    }

    /** A short human snippet of the entry's main content for the timeline. */
    private function snippet(Model $entry): ?string
    {
        foreach (['complaint_text', 'description', 'content', 'name', 'diagnosis', 'title', 'notes', 'examination'] as $field) {
            $value = $entry->getAttribute($field);
            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 60);
            }
        }

        return null;
    }
}
