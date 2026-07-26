<?php

namespace App\Services\Consultation\Maternity;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\LogModule;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\PregnancyProfile;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\ConsultationRouteService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — explicit "Refer to Obstetrics/Maternity" from a Gynaecology
 * consultation.
 *
 * GYNAECOLOGY REMAINS GYNAECOLOGY. This service:
 *   - never changes the current route's department, specialty or status;
 *   - never edits or deletes a single existing specialty entry;
 *   - never creates ANC, Labor or an Admission Request;
 *   - never switches the specialty profile to Obstetrics.
 *
 * It creates a SEPARATE target consultation route through the existing
 * ConsultationRouteService — no parallel routing engine is invented — and links
 * that new route to the SAME pregnancy profile with link_role = handoff.
 *
 * Idempotency comes from the existing system: createRouteForDepartment() already
 * reuses a visit's non-cancelled route for the same department, so a repeated
 * referral returns the same route rather than a second one.
 *
 * Limitation (documented): when no Obstetrics consultation department is mapped
 * in this installation there is nothing safe to route to. Rather than inventing
 * a referral subsystem, the service reports `unavailable` and the UI links into
 * the existing standard create-consultation flow with the context preselected.
 */
class GynaecologyObstetricsReferralService
{
    public const OBSTETRICS_PROFILE_CODE = 'obstetrics';

    public const OUTCOME_CREATED = 'created';
    public const OUTCOME_REUSED = 'reused';
    public const OUTCOME_UNAVAILABLE = 'unavailable';

    public function __construct(
        private readonly ConsultationRouteService $routes,
        private readonly ConsultationMaternityLinkService $links,
        private readonly ActivityLogService $activityLog,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function enabled(): bool
    {
        return $this->flags->consultationHandoffsEnabled();
    }

    /**
     * The consultation department mapped to the Obstetrics specialty profile.
     * Returns null when this installation has no such mapping.
     */
    public function targetDepartment(): ?Department
    {
        $departmentId = ConsultationSpecialtyProfileMapping::query()
            ->active()
            ->whereNotNull('department_id')
            ->whereHas('profile', fn ($query) => $query
                ->active()
                ->where('code', self::OBSTETRICS_PROFILE_CODE))
            ->ordered()
            ->value('department_id');

        if (! $departmentId) {
            return null;
        }

        return Department::query()
            ->whereKey($departmentId)
            ->where('type', DepartmentType::CONSULTATION->value)
            ->where('status', 'active')
            ->first();
    }

    /**
     * An Obstetrics route already open on this visit, if any. Used to report
     * "handoff already exists" without attempting a write.
     */
    public function existingObstetricsRoute(VisitConsultationRoute $source, ?Department $department = null): ?VisitConsultationRoute
    {
        $department ??= $this->targetDepartment();

        if (! $department || ! $source->visit_id) {
            return null;
        }

        return VisitConsultationRoute::query()
            ->where('visit_id', $source->visit_id)
            ->where('department_id', $department->id)
            ->where('status', '!=', VisitConsultationRoute::STATUS_CANCELLED)
            ->oldest('id')
            ->first();
    }

    /**
     * Refer the patient to Obstetrics/Maternity.
     *
     * @return array{outcome: string, route: VisitConsultationRoute|null, department: Department|null}
     */
    public function refer(
        VisitConsultationRoute $source,
        PregnancyProfile $profile,
        User $actor,
        ?string $notes = null,
    ): array {
        $department = $this->targetDepartment();

        if (! $department) {
            return [
                'outcome' => self::OUTCOME_UNAVAILABLE,
                'route' => null,
                'department' => null,
            ];
        }

        $source->loadMissing('visit');
        $visit = $source->visit;

        if (! $visit) {
            return [
                'outcome' => self::OUTCOME_UNAVAILABLE,
                'route' => null,
                'department' => $department,
            ];
        }

        $existing = $this->existingObstetricsRoute($source, $department);

        return DB::transaction(function () use ($visit, $department, $source, $profile, $actor, $notes, $existing) {
            // The existing service is idempotent per (visit, department) — a
            // repeated referral returns the same route.
            $target = $this->routes->createRouteForDepartment(
                $visit,
                $department,
                $this->defaultServices($department),
                null,
                $actor,
                $notes,
            );

            // Link the TARGET route to the same pregnancy profile. The source
            // Gynaecology route keeps its own links untouched.
            $this->links->link(
                $target,
                $profile,
                $actor,
                ConsultationMaternityLinkRole::HANDOFF,
                $notes,
                ['referred_from_consultation_route_id' => $source->id],
            );

            $outcome = $existing && (int) $existing->id === (int) $target->id
                ? self::OUTCOME_REUSED
                : self::OUTCOME_CREATED;

            if ($outcome === self::OUTCOME_CREATED) {
                $this->activityLog->log(
                    LogModule::CONSULTATION,
                    'GYNAECOLOGY_OBSTETRICS_HANDOFF_CREATED',
                    [
                        'patient_id' => $source->patient_id,
                        'visit_id' => $source->visit_id,
                        'causer' => $actor,
                        'metadata' => [
                            'source_module' => 'consultation',
                            'source_record_id' => $source->id,
                            'target_module' => 'consultation',
                            'target_record_id' => $target->id,
                            'pregnancy_profile_id' => $profile->id,
                            'handoff_role' => ConsultationMaternityLinkRole::HANDOFF->value,
                        ],
                    ],
                    $target,
                    'GYNAECOLOGY_OBSTETRICS_HANDOFF_CREATED',
                );
            }

            return ['outcome' => $outcome, 'route' => $target, 'department' => $department];
        });
    }

    /**
     * A single default service for the target department, when one exists.
     * Routing without a service is allowed by the existing service, so an empty
     * result is not an error.
     *
     * @return list<ServiceCatalog>
     */
    private function defaultServices(Department $department): array
    {
        $service = ServiceCatalog::query()
            ->where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return $service ? [$service] : [];
    }
}
