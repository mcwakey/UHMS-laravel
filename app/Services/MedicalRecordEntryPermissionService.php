<?php

namespace App\Services;

use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Model;

class MedicalRecordEntryPermissionService
{
    public function canView(User $user, Model $entry): bool
    {
        return $user->can('consultation.entries.view_all')
            || ($entry instanceof Complaint && $user->can('complaints.view'))
            || $user->can('consultations.view')
            || $this->isOwner($user, $entry);
    }

    public function canEdit(User $user, Model $entry): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($this->isCompletedSession($entry) && ! $user->can('consultation.entries.correct_completed')) {
            return false;
        }

        if ($this->isOwner($user, $entry)) {
            return $user->can('consultation.entries.edit_own')
                || ($entry instanceof Complaint && $user->can('complaints.edit_own'))
                || $user->can('consultations.create');
        }

        return $user->can('consultation.entries.edit_any')
            || ($entry instanceof Complaint && $user->can('complaints.edit_any'));
    }

    public function canDelete(User $user, Model $entry): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        if ($this->isCompletedSession($entry) && ! $user->can('consultation.entries.correct_completed')) {
            return false;
        }

        if ($this->isOwner($user, $entry)) {
            return $user->can('consultation.entries.delete_own')
                || ($entry instanceof Complaint && $user->can('complaints.delete_own'))
                || $user->can('consultations.create');
        }

        return $user->can('consultation.entries.delete_any')
            || ($entry instanceof Complaint && $user->can('complaints.delete_any'));
    }

    public function isOwner(User $user, Model $entry): bool
    {
        return (int) ($entry->created_by ?? $entry->doctor_id ?? null) === (int) $user->id;
    }

    private function isCompletedSession(Model $entry): bool
    {
        $route = method_exists($entry, 'consultationRoute') ? $entry->consultationRoute : null;

        return $route && (
            $route->locked_at !== null
            || in_array($route->status, [
                VisitConsultationRoute::STATUS_COMPLETED,
                VisitConsultationRoute::STATUS_CANCELLED,
            ], true)
        );
    }
}
