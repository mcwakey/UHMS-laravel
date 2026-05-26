<?php

namespace App\Services;

use App\Models\MedicalRecordEntryLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
    }
}
