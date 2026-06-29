<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lists the active staff eligible to act on a handoff's destination — members of the
 * destination department (or its capability domain). Used to populate the "assign to"
 * picker so a handoff is only ever assigned to someone who can actually resolve it.
 */
class JourneyAssignableUserService
{
    /** @return Collection<int, User> */
    public function forHandoff(JourneyHandoff $handoff): Collection
    {
        return $this->forDestination($handoff->toDepartmentId, $handoff->toDepartmentType);
    }

    /** @return Collection<int, User> */
    public function forDestination(?int $toDepartmentId, ?string $toType): Collection
    {
        $query = User::query()->active();

        if ($toDepartmentId !== null) {
            $query->where(fn ($q) => $q
                ->where('department_id', $toDepartmentId)
                ->orWhereHas('departments', fn ($d) => $d->where('departments.id', $toDepartmentId)));
        } else {
            $types = $this->typesForDestination($toType);
            if ($types === []) {
                return collect();
            }
            $query->where(fn ($q) => $q
                ->whereHas('department', fn ($d) => $d->whereIn('type', $types))
                ->orWhereHas('departments', fn ($d) => $d->whereIn('type', $types)));
        }

        return $query->orderBy('first_name')->orderBy('last_name')->limit(25)->get();
    }

    public function hasAny(?int $toDepartmentId, ?string $toType): bool
    {
        return $this->forDestination($toDepartmentId, $toType)->isNotEmpty();
    }

    /** Capability-domain department types for a destination type. */
    private function typesForDestination(?string $toType): array
    {
        return match ($toType) {
            'investigation', 'radiology', 'blood_bank' => ['investigation', 'radiology', 'blood_bank'],
            'pharmacy' => ['pharmacy'],
            'inpatient', 'maternity' => ['inpatient', 'maternity', 'nursing', 'treatment'],
            'finance', 'administrative' => ['finance', 'administrative'],
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => ['consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records'],
            default => [],
        };
    }
}
