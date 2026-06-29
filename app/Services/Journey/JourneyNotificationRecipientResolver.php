<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\UserStatus;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves WHO to notify for a journey handoff event — capability- and
 * department-aware, always bounded. There is no department-head field in UHMS, so
 * "supervisor" recipients fall back to eligible destination-domain staff (who, by
 * definition, hold the capability to act). Never returns inactive users.
 */
class JourneyNotificationRecipientResolver
{
    /** Cap broadcast recipients to avoid notification storms. */
    private const STAFF_CAP = 8;

    public function __construct(private JourneyAssignableUserService $assignable) {}

    /** @return Collection<int, User> the current assignee, if active. */
    public function forAssignee(JourneyHandoffAssignment $assignment): Collection
    {
        $assignment->loadMissing('assignedTo');
        $user = $assignment->assignedTo;

        return $user && $this->active($user) ? collect([$user]) : collect();
    }

    /** @return Collection<int, User> the assigner, when someone else acknowledged. */
    public function forAssigner(JourneyHandoffAssignment $assignment, ?User $actor): Collection
    {
        $assignment->loadMissing('assignedBy');
        $user = $assignment->assignedBy;
        if ($user === null || ! $this->active($user) || ($actor !== null && $user->id === $actor->id)) {
            return collect();
        }

        return collect([$user]);
    }

    /** @return Collection<int, User> the assignee, when resolved by someone else. */
    public function forResolution(JourneyHandoffAssignment $assignment, ?User $actor): Collection
    {
        return $this->forAssignee($assignment)
            ->reject(fn (User $user) => $actor !== null && $user->id === $actor->id)
            ->values();
    }

    /** @return Collection<int, User> eligible destination staff (supervisor/critical/unassigned). */
    public function forDestinationStaff(?int $toDepartmentId, ?string $toType, ?User $exclude = null): Collection
    {
        return $this->assignable->forDestination($toDepartmentId, $toType)
            ->reject(fn (User $user) => $exclude !== null && $user->id === $exclude->id)
            ->take(self::STAFF_CAP)
            ->values();
    }

    /** @return Collection<int, User> eligible staff for an unassigned handoff. */
    public function forUnassignedHandoff(JourneyHandoff $handoff): Collection
    {
        return $this->forDestinationStaff($handoff->toDepartmentId, $handoff->toDepartmentType);
    }

    private function active(User $user): bool
    {
        return $user->status === UserStatus::ACTIVE;
    }
}
