<?php

namespace App\Services\Admissions;

use App\Enums\AdmissionCareFlag;
use App\Enums\LogModule;
use App\Enums\NursingTaskStatus;
use App\Models\Admission;
use App\Models\NursingNote;
use App\Models\NursingTask;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class AdmissionNursingCareService
{
    public function __construct(private ActivityLogService $logger) {}

    public function createNote(Admission $admission, array $data, User $user): NursingNote
    {
        $note = NursingNote::create([
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'nurse_id' => $data['nurse_id'] ?? $user->id,
            'note_type' => $data['note_type'] ?? null,
            'note' => $data['note'],
            'observed_at' => $data['observed_at'] ?? now(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->logger->log(LogModule::ADMISSION, 'NURSING_NOTE_CREATED', $note->toActivityContext() + [
            'metadata' => [
                'note_id' => $note->id,
                'note_type' => $note->note_type?->value,
                'observed_at' => $note->observed_at,
            ],
            'causer' => $user,
        ], $note, 'Nursing note created');

        return $note;
    }

    public function updateNote(NursingNote $note, array $data, User $user): NursingNote
    {
        $note->update([
            'nurse_id' => $data['nurse_id'] ?? $note->nurse_id,
            'note_type' => $data['note_type'] ?? $note->note_type?->value,
            'note' => $data['note'] ?? $note->note,
            'observed_at' => $data['observed_at'] ?? $note->observed_at,
            'updated_by' => $user->id,
        ]);

        $this->logger->log(LogModule::ADMISSION, 'NURSING_NOTE_UPDATED', $note->toActivityContext() + [
            'metadata' => [
                'note_id' => $note->id,
                'note_type' => $note->note_type?->value,
            ],
            'causer' => $user,
        ], $note, 'Nursing note updated');

        return $note->fresh(['nurse', 'createdBy', 'updatedBy']);
    }

    public function createTask(Admission $admission, array $data, User $user): NursingTask
    {
        $task = NursingTask::create([
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'visit_id' => $admission->visit_id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $user->id,
            'task_type' => $data['task_type'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['status'] ?? NursingTaskStatus::OPEN->value,
            'due_at' => $data['due_at'] ?? null,
        ]);

        $this->logTask($task, 'NURSING_TASK_CREATED', $user, 'Nursing task created');

        return $task;
    }

    public function updateTask(NursingTask $task, array $data, User $user): NursingTask
    {
        $updates = [];
        foreach (['assigned_to', 'task_type', 'title', 'description', 'priority', 'status', 'due_at', 'cancellation_reason'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if ($updates !== []) {
            $task->update($updates);
        }

        if ($task->status === NursingTaskStatus::CANCELLED) {
            $this->logTask($task, 'NURSING_TASK_CANCELLED', $user, 'Nursing task cancelled');
        } else {
            $this->logTask($task, 'NURSING_TASK_UPDATED', $user, 'Nursing task updated');
        }

        return $task->fresh(['assignedTo', 'createdBy', 'completedBy']);
    }

    public function completeTask(NursingTask $task, User $user): NursingTask
    {
        $task->update([
            'status' => NursingTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        $this->logTask($task, 'NURSING_TASK_COMPLETED', $user, 'Nursing task completed');

        return $task->fresh(['assignedTo', 'createdBy', 'completedBy']);
    }

    public function updateCareFlags(Admission $admission, array $flags, User $user): Admission
    {
        $allowed = collect(AdmissionCareFlag::cases())->pluck('value')->all();
        $flags = collect($flags)->filter(fn ($flag) => in_array($flag, $allowed, true))->unique()->values()->all();

        return DB::transaction(function () use ($admission, $flags, $user) {
            $admission->update(['care_flags' => $flags ?: null]);

            $this->logger->log(LogModule::ADMISSION, 'ADMISSION_CARE_FLAGS_UPDATED', $admission->toActivityContext() + [
                'metadata' => [
                    'flag_count' => count($flags),
                    'flags' => $flags,
                ],
                'causer' => $user,
            ], $admission, 'Admission care flags updated');

            return $admission->fresh(['patient', 'bed.ward']);
        });
    }

    private function logTask(NursingTask $task, string $action, User $user, string $description): void
    {
        $this->logger->log(LogModule::ADMISSION, $action, $task->toActivityContext() + [
            'metadata' => [
                'task_id' => $task->id,
                'task_type' => $task->task_type?->value,
                'status' => $task->status?->value,
                'priority' => $task->priority,
                'due_at' => $task->due_at,
            ],
            'causer' => $user,
        ], $task, $description);
    }
}
