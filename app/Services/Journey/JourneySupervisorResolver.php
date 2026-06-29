<?php

namespace App\Services\Journey;

use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Precise escalation routing (Phase 9.7). Resolves the department supervisor and the
 * ordered escalation recipients for a handoff, falling back safely when supervisor
 * info is missing. Capability-/department-aware, never returns inactive users, and
 * always bounded by escalation_policy.max_recipients.
 *
 * Fallback order: supervisor → escalation user → eligible destination staff →
 * journey.oversight users (critical only) → none.
 */
class JourneySupervisorResolver
{
    public function __construct(private JourneyAssignableUserService $assignable) {}

    public function supervisorForDepartment(?Department $department): ?User
    {
        if ($department === null) {
            return null;
        }

        $supervisor = $department->relationLoaded('supervisor') ? $department->supervisor : $department->supervisor()->first();
        if ($supervisor && $this->active($supervisor)) {
            return $supervisor;
        }

        $escalation = $department->relationLoaded('escalationUser') ? $department->escalationUser : $department->escalationUser()->first();

        return $escalation && $this->active($escalation) ? $escalation : null;
    }

    /**
     * Ordered escalation recipients for a destination (works for assignments + handoffs).
     *
     * @return Collection<int, User>
     */
    public function escalationRecipientsFor(?int $toDepartmentId, ?string $toType, string $level): Collection
    {
        $max = (int) config('journey.escalation_policy.max_recipients', 8);
        $recipients = collect();
        $department = $toDepartmentId ? Department::find($toDepartmentId) : null;

        if (config('journey.escalation_policy.prefer_supervisor', true) && $department) {
            $supervisor = $this->supervisorForDepartment($department);
            if ($supervisor) {
                $recipients->push($supervisor);
            }
            $escalationUser = $department->escalationUser;
            if ($escalationUser && $this->active($escalationUser)) {
                $recipients->push($escalationUser);
            }
        }

        // Critical → also route to journey-oversight users.
        if ($level === 'critical' && config('journey.escalation_policy.route_critical_to_oversight', true)) {
            $recipients = $recipients->merge($this->oversightUsers());
        }

        // No supervisor/oversight → fall back to eligible destination staff.
        if ($recipients->isEmpty() && config('journey.escalation_policy.fallback_to_eligible_staff', true)) {
            $recipients = $this->fallbackEligibleRecipients($toDepartmentId, $toType);
        }

        return $recipients->filter(fn (User $user) => $this->active($user))->unique('id')->take($max)->values();
    }

    /** @return Collection<int, User> */
    public function fallbackEligibleRecipients(?int $toDepartmentId, ?string $toType): Collection
    {
        return $this->assignable->forDestination($toDepartmentId, $toType)
            ->take((int) config('journey.escalation_policy.max_recipients', 8))
            ->values();
    }

    /** @return Collection<int, User> active users holding journey.oversight (bounded). */
    public function oversightUsers(): Collection
    {
        try {
            return User::query()->active()->permission('journey.oversight')
                ->limit((int) config('journey.escalation_policy.max_recipients', 8))
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function active(User $user): bool
    {
        return $user->status === UserStatus::ACTIVE;
    }
}
