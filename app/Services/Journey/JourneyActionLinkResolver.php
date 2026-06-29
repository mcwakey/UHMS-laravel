<?php

namespace App\Services\Journey;

use App\Enums\JourneyDelayCause;
use App\Models\User;
use App\Models\Visit;
use App\Services\Department\DepartmentDashboardCapabilityService;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Maps a delay cause to a SAFE deep link to the resolving screen. Returns null
 * unless the route exists AND the user holds the owning domain's capability — it
 * never points staff at a screen they may not open, and never hard-fails on a
 * missing route. At most one cheap record-id lookup per call (displayed rows only).
 */
class JourneyActionLinkResolver
{
    public function __construct(private DepartmentDashboardCapabilityService $capabilities) {}

    public function resolve(JourneyDelayCause $cause, Visit $visit, User $user): ?string
    {
        [$route, $params] = $this->routeFor($cause, $visit);

        if ($route === null || ! Route::has($route)) {
            return null;
        }

        // Gate by the TARGET screen's domain (who actually clicks), not the cause's
        // "responsible" owner — a clinician ordering a lab acts on the visit page.
        $capability = $this->capabilityForRoute($route);
        if ($capability !== null && ! $this->capabilities->can($user, $capability)) {
            return null;
        }

        try {
            return route($route, $params);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{0:?string,1:mixed} */
    private function routeFor(JourneyDelayCause $cause, Visit $visit): array
    {
        return match ($cause) {
            JourneyDelayCause::AWAITING_LAB_RESULT,
            JourneyDelayCause::AWAITING_RADIOLOGY_RESULT => $this->labRoute($visit),
            JourneyDelayCause::AWAITING_DISPENSING => ['admin.pharmacy.dispensing.index', []],
            JourneyDelayCause::AWAITING_BED,
            JourneyDelayCause::AWAITING_DISCHARGE => $this->admissionRoute($visit, $cause),
            JourneyDelayCause::AWAITING_ADMISSION => ['admin.admissions.index', []],
            JourneyDelayCause::AWAITING_PAYMENT => ['admin.billing.invoices.index', []],
            JourneyDelayCause::AWAITING_PROCEDURE => ['admin.theatre.board', []],
            default => ['admin.visits.show', $visit->id],
        };
    }

    private function labRoute(Visit $visit): array
    {
        $requestId = $visit->relationLoaded('labRequests')
            ? $visit->getRelation('labRequests')->sortByDesc('id')->first()?->id
            : $this->safe(fn () => $visit->labRequests()->latest('id')->value('id'));

        return $requestId
            ? ['admin.lab.requests.show', $requestId]
            : ['admin.lab.requests.index', []];
    }

    private function admissionRoute(Visit $visit, JourneyDelayCause $cause): array
    {
        $admissionId = $visit->relationLoaded('admission')
            ? $visit->getRelation('admission')?->id
            : $this->safe(fn () => $visit->admission()->value('id'));

        if ($admissionId && $cause === JourneyDelayCause::AWAITING_DISCHARGE && Route::has('admin.admissions.discharge')) {
            return ['admin.admissions.discharge', $admissionId];
        }

        return $admissionId
            ? ['admin.admissions.show', $admissionId]
            : ['admin.admissions.index', []];
    }

    private function capabilityForRoute(string $route): ?string
    {
        return match (true) {
            str_starts_with($route, 'admin.lab.') => 'investigation_access',
            str_starts_with($route, 'admin.pharmacy.') => 'pharmacy_access',
            str_starts_with($route, 'admin.admissions.') => 'ward_access',
            str_starts_with($route, 'admin.billing.') => 'financial_access',
            str_starts_with($route, 'admin.theatre.') => 'consultation_access',
            str_starts_with($route, 'admin.visits.') => 'consultation_access',
            default => null,
        };
    }

    private function safe(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}
