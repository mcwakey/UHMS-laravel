<?php

namespace App\Services\Consultation\Specialty;

use App\Data\Consultation\Specialty\ResolvedConsultationSpecialty;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\DoctorConsultationPreference;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Database\Eloquent\Model;

class ConsultationSpecialtyProfileResolver
{
    public function __construct(
        private readonly ConsultationSpecialtyProfileService $profiles,
    ) {}

    public function resolve(
        User $user,
        mixed $visit = null,
        mixed $consultation = null,
        mixed $consultationRoute = null,
        mixed $department = null,
        array $options = [],
    ): ResolvedConsultationSpecialty {
        $route = $this->routeFrom($consultationRoute, $consultation, $visit);
        $activeDepartment = $this->departmentFrom($department, $route, $visit);

        return $this->fromMapping(
            ConsultationSpecialtyProfileMapping::query()
                ->active()
                ->forConsultationRoute($route)
                ->whereNotNull('consultation_route_id'),
            'consultation_route_mapping',
            'Matched active consultation route specialty mapping.',
            $activeDepartment,
            $route,
        ) ?? $this->fromMapping(
            ConsultationSpecialtyProfileMapping::query()
                ->active()
                ->forDepartment($activeDepartment)
                ->whereNotNull('department_id'),
            'department_mapping',
            'Matched active department specialty mapping.',
            $activeDepartment,
            $route,
        ) ?? $this->fromMapping(
            ConsultationSpecialtyProfileMapping::query()
                ->active()
                ->forDepartmentType($this->departmentTypeValue($activeDepartment))
                ->whereNotNull('department_type'),
            'department_type_mapping',
            'Matched active department type specialty mapping.',
            $activeDepartment,
            $route,
        ) ?? $this->fromDoctorPreference($user, $activeDepartment, $route)
            ?? $this->fromUserDepartment($user, $route)
            ?? $this->fromExistingConsultationEntry($route, $consultation, $activeDepartment)
            ?? $this->profiles->fallbackResolvedContext(
                department: $activeDepartment,
                consultationRoute: $route,
            );
    }

    private function fromMapping($query, string $source, string $reason, ?object $department, ?object $route): ?ResolvedConsultationSpecialty
    {
        $mapping = $query
            ->whereHas('profile', fn ($profileQuery) => $profileQuery->active())
            ->with('profile')
            ->ordered()
            ->first();

        return $mapping?->profile
            ? $this->resolved($mapping->profile, $source, $reason, $department, $route)
            : null;
    }

    private function fromDoctorPreference(User $user, ?object $department, ?object $route): ?ResolvedConsultationSpecialty
    {
        $preference = DoctorConsultationPreference::query()
            ->with('defaultSpecialtyProfile')
            ->where('user_id', $user->id)
            ->first();

        $profile = $preference?->defaultSpecialtyProfile;

        return ($profile && $profile->is_active)
            ? $this->resolved($profile, 'doctor_preference', 'Matched doctor consultation preference.', $department, $route)
            : null;
    }

    private function fromUserDepartment(User $user, ?object $route): ?ResolvedConsultationSpecialty
    {
        $department = $user->departments()
            ->wherePivot('is_primary', true)
            ->where(function ($query) {
                $query->whereNull('department_user.ends_at')
                    ->orWhere('department_user.ends_at', '>', now());
            })
            ->first()
            ?? ($user->department_id ? Department::query()->find($user->department_id) : null);

        if (! $department) {
            return null;
        }

        return $this->fromMapping(
            ConsultationSpecialtyProfileMapping::query()
                ->active()
                ->forDepartment($department)
                ->whereNotNull('department_id'),
            'user_department_mapping',
            'Matched user primary or assigned department specialty mapping.',
            $department,
            $route,
        );
    }

    private function fromExistingConsultationEntry(mixed $route, mixed $consultation, ?object $department): ?ResolvedConsultationSpecialty
    {
        $routeId = $this->idFrom($route);
        $consultationId = $this->idFrom($consultation);

        $query = ConsultationSpecialtyEntry::query()
            ->with('profile')
            ->whereHas('profile', fn ($profileQuery) => $profileQuery->active())
            ->latest();

        if ($routeId) {
            $query->where('consultation_id', $routeId);
        } elseif ($consultationId) {
            $query->where('consultation_id', $consultationId);
        } else {
            return null;
        }

        $entry = $query->first();

        return $entry?->profile
            ? $this->resolved($entry->profile, 'existing_consultation_entry', 'Matched existing consultation specialty entry.', $department, $route)
            : null;
    }

    private function resolved(ConsultationSpecialtyProfile $profile, string $source, string $reason, ?object $department, ?object $route): ResolvedConsultationSpecialty
    {
        $sections = $this->profiles->getVisibleOrderedSections($profile);

        if ($sections->isEmpty() && ! $profile->isGeneral()) {
            return $this->profiles->fallbackResolvedContext(
                department: $department,
                consultationRoute: $route,
                reason: "Selected profile {$profile->code} has no visible sections.",
            );
        }

        return new ResolvedConsultationSpecialty(
            profile: $profile,
            source: $source,
            reason: $reason,
            department: $department,
            consultationRoute: $route,
            sections: $sections->all(),
            isFallback: false,
        );
    }

    private function routeFrom(mixed $route, mixed $consultation, mixed $visit): ?object
    {
        if ($route instanceof VisitConsultationRoute) {
            return $route;
        }

        if ($consultation instanceof VisitConsultationRoute) {
            return $consultation;
        }

        if ($visit instanceof Visit) {
            return $visit->currentConsultationRoute();
        }

        return null;
    }

    private function departmentFrom(mixed $department, ?object $route, mixed $visit): ?object
    {
        if ($department instanceof Department) {
            return $department;
        }

        if ($route instanceof VisitConsultationRoute) {
            return $route->relationLoaded('department') ? $route->department : $route->department()->first();
        }

        if ($visit instanceof Visit) {
            return $visit->relationLoaded('currentDepartment') ? $visit->currentDepartment : $visit->currentDepartment()->first();
        }

        return null;
    }

    private function departmentTypeValue(?object $department): ?string
    {
        $type = $department?->type ?? null;

        return $type instanceof \BackedEnum ? $type->value : $type;
    }

    private function idFrom(mixed $value): ?int
    {
        if ($value instanceof Model) {
            return $value->getKey();
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
