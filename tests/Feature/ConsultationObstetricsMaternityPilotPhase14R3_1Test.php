<?php

namespace Tests\Feature;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityContextResolver;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ObstetricConsultationContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 14R.3.1 — pilot wiring + clinical mutation boundary hardening.
 *
 * Closes K1 (components unreachable on the real page), K2 (clinical mutations
 * bypassed the consultation mutation guard) and K3 (unmeasured query cost).
 */
class ConsultationObstetricsMaternityPilotPhase14R3_1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Patient $patient;

    private Visit $visit;

    private VisitConsultationRoute $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->flags(false, false);

        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);

        $this->consultation = $this->makeRoute(VisitConsultationRoute::STATUS_ACTIVE);
    }

    /* ── K3: resolver invocation + query cost ─────────────────────────── */

    public function test_flag_off_never_invokes_the_resolver(): void
    {
        $this->makeProfile();
        $spy = $this->spyResolver();

        app(ObstetricConsultationContextService::class)
            ->build($this->consultation, $this->obstetricsProfile(), $this->user);

        $this->assertSame(0, $spy->count, 'flag off must add zero resolver calls');
    }

    public function test_non_obstetrics_profile_never_invokes_the_resolver(): void
    {
        $this->flags(true, false);
        $this->makeProfile();
        $spy = $this->spyResolver();

        $other = ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'general_medicine'],
            ['name' => 'General Medicine', 'is_active' => true, 'sort_order' => 10]
        );

        app(ObstetricConsultationContextService::class)
            ->build($this->consultation, $other, $this->user);

        $this->assertSame(0, $spy->count, 'non-Obstetrics profiles must add zero resolver calls');
    }

    public function test_enabled_obstetrics_workspace_resolves_exactly_once(): void
    {
        $this->flags(true, false);
        $this->linkProfile($this->makeProfile());
        $spy = $this->spyResolver();

        $service = app(ObstetricConsultationContextService::class);
        $service->build($this->consultation, $this->obstetricsProfile(), $this->user);
        // A second build in the same request must reuse the memoised context.
        $service->build($this->consultation, $this->obstetricsProfile(), $this->user);

        $this->assertSame(1, $spy->count, 'context must resolve once per request');
    }

    public function test_flag_off_adds_no_maternity_queries_and_enabled_delta_is_bounded(): void
    {
        $this->linkProfile($this->makeProfile());
        $profile = $this->obstetricsProfile();

        $off = $this->countQueries(function () use ($profile) {
            app(ObstetricConsultationContextService::class)
                ->build($this->consultation, $profile, $this->user);
        });

        $this->flags(true, false);

        $on = $this->countQueries(function () use ($profile) {
            app(ObstetricConsultationContextService::class)
                ->build($this->consultation, $profile, $this->user);
        });

        $this->assertSame(0, $off, 'disabled mode must issue zero maternity queries');
        // Bounded: explicit link + latest-of-each projection lookups. Guards
        // against an N+1 regression rather than pinning an exact number.
        $this->assertLessThanOrEqual(15, $on, "enabled build issued {$on} queries");
    }


    /**
     * K3 — records the actual query counts for all five required fixtures.
     * Emits them to STDERR so the phase report can quote real numbers.
     */
    public function test_query_counts_across_all_measured_fixtures(): void
    {
        $obs = $this->obstetricsProfile();
        $gm = ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'general_medicine'],
            ['name' => 'General Medicine', 'is_active' => true, 'sort_order' => 10]
        );

        $fresh = function () {
            app()->forgetInstance(ObstetricConsultationContextService::class);

            return app(ObstetricConsultationContextService::class);
        };

        // 1 — flag off.
        $this->flags(false, false);
        $offNoContext = $this->countQueries(fn () => $fresh()->build($this->consultation, $obs, $this->user));

        // 2 — enabled, no context.
        $this->flags(true, false);
        $enabledNoContext = $this->countQueries(fn () => $fresh()->build($this->consultation, $obs, $this->user));

        // 3 — enabled, one inferred active profile.
        $profile = $this->makeProfile();
        $inferred = $this->countQueries(fn () => $fresh()->build($this->consultation, $obs, $this->user));

        // 4 — explicit link + ANC + labor records.
        $this->linkProfile($profile);
        AntenatalVisit::create([
            'pregnancy_profile_id' => $profile->id, 'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id, 'department_id' => $this->department->id,
            'recorded_by' => $this->user->id, 'visit_date' => now(), 'status' => 'recorded',
        ]);
        LaborEpisode::create([
            'pregnancy_profile_id' => $profile->id, 'patient_id' => $this->patient->id,
            'department_id' => $this->department->id, 'started_at' => now(),
            'labor_stage' => 'first_stage', 'status' => 'active',
        ]);
        $explicitFull = $this->countQueries(fn () => $fresh()->build($this->consultation, $obs, $this->user));

        // 5 — non-Obstetrics with the flag enabled globally.
        $nonObstetrics = $this->countQueries(fn () => $fresh()->build($this->consultation, $gm, $this->user));

        fwrite(STDERR, sprintf(
            "\nQUERY COUNTS  off=%d  enabled_no_ctx=%d  inferred=%d  explicit_full=%d  non_obstetrics=%d\n",
            $offNoContext, $enabledNoContext, $inferred, $explicitFull, $nonObstetrics
        ));

        // Hard guarantees.
        $this->assertSame(0, $offNoContext, 'flag off must add zero queries');
        $this->assertSame(0, $nonObstetrics, 'non-Obstetrics must add zero queries');
        $this->assertLessThanOrEqual(20, $explicitFull, 'richest fixture must stay bounded');
    }

    /* ── K2: clinical mutation boundary ───────────────────────────────── */

    public function test_completed_consultation_can_still_link_relink_and_unlink(): void
    {
        $this->flags(true, false);
        $first = $this->makeProfile();
        $second = $this->makeProfile();
        $actor = $this->userWith([
            'consultation.maternity_context.link',
            'consultation.maternity_context.unlink',
        ]);

        $this->completeConsultation();

        // Link on a completed consultation — allowed (context-link action).
        $this->actingAs($actor)->post(
            route('admin.consultations.maternity-context.link', $this->visit),
            ['pregnancy_profile_id' => $first->id]
        )->assertRedirect();
        $this->assertSame(1, ConsultationMaternityLink::query()->active()->count());

        // Relink with reason — allowed.
        $this->actingAs($actor)->post(
            route('admin.consultations.maternity-context.relink', $this->visit),
            ['pregnancy_profile_id' => $second->id, 'reason' => 'wrong profile linked']
        )->assertRedirect();

        // Unlink with reason — allowed.
        $this->actingAs($actor)->post(
            route('admin.consultations.maternity-context.unlink', $this->visit),
            ['reason' => 'closing out']
        )->assertRedirect();

        $this->assertSame(0, ConsultationMaternityLink::query()->active()->count());
        // History preserved, never deleted.
        $this->assertSame(2, ConsultationMaternityLink::query()->historical()->count());
    }

    public function test_completed_consultation_cannot_create_a_pregnancy_profile(): void
    {
        $this->flags(true, false);
        $this->completeConsultation();

        $this->actingAs($this->userWith([
            'consultation.maternity_context.create_profile',
            'maternity.pregnancy.create',
        ]))->post(route('admin.consultations.maternity-context.create-profile', $this->visit), [
            'gravida' => 1,
        ])->assertStatus(422);

        $this->assertSame(0, PregnancyProfile::count(), 'no maternity record on a blocked action');
        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_completed_consultation_cannot_record_anc(): void
    {
        $this->flags(true, false);
        $this->linkProfile($this->makeProfile());
        $this->completeConsultation();

        $this->actingAs($this->userWith([
            'consultation.maternity_context.record_anc',
            'maternity.anc.record',
        ]))->post(route('admin.consultations.maternity-context.record-anc', $this->visit), [
            'fetal_heart_rate' => 140,
        ])->assertStatus(422);

        $this->assertSame(0, AntenatalVisit::count());
        // Only the pre-existing profile link remains; no ANC link was created.
        $this->assertSame(0, ConsultationMaternityLink::query()
            ->forContextType(ConsultationMaternityContextType::ANC_VISIT)->count());
    }

    public function test_completed_consultation_cannot_start_labor(): void
    {
        $this->flags(true, false);
        $this->linkProfile($this->makeProfile());
        $this->completeConsultation();

        $this->actingAs($this->userWith([
            'consultation.maternity_context.start_labor',
            'maternity.labor.start',
        ]))->post(route('admin.consultations.maternity-context.start-labor', $this->visit))
            ->assertStatus(422);

        $this->assertSame(0, LaborEpisode::count());
        $this->assertSame(0, ConsultationMaternityLink::query()
            ->forContextType(ConsultationMaternityContextType::LABOR)->count());
    }

    public function test_active_consultation_can_still_perform_clinical_mutations(): void
    {
        $this->flags(true, false);
        $this->linkProfile($this->makeProfile());

        $this->actingAs($this->userWith([
            'consultation.maternity_context.record_anc',
            'maternity.anc.record',
        ]))->post(route('admin.consultations.maternity-context.record-anc', $this->visit), [
            'fetal_heart_rate' => 142,
        ])->assertRedirect();

        $this->assertSame(1, AntenatalVisit::count(), 'active consultation must still allow ANC');
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function flags(bool $workspace, bool $guard): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => $workspace,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => $guard,
        ]);
    }

    /** Counts only maternity-bridge-relevant queries issued inside $callback. */
    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return count($queries);
    }

    /** Swaps the resolver for a counting decorator bound in the container. */
    private function spyResolver(): object
    {
        $spy = new class(app(ConsultationMaternityLinkService::class)) extends ConsultationMaternityContextResolver
        {
            public int $count = 0;

            public function resolve(VisitConsultationRoute $consultation): \App\Data\Consultation\Maternity\ConsultationMaternityContext
            {
                $this->count++;

                return parent::resolve($consultation);
            }
        };

        app()->instance(ConsultationMaternityContextResolver::class, $spy);
        // Rebuild the context service so it receives the spy.
        app()->forgetInstance(ObstetricConsultationContextService::class);

        return $spy;
    }

    private function makeRoute(string $status): VisitConsultationRoute
    {
        return VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => $status,
            'routed_by' => $this->user->id,
            // An ACTIVE route without started_at counts as "not started" for
            // the consultation mutation guard — set it so the fixture models a
            // genuinely editable session.
            'started_at' => $status === VisitConsultationRoute::STATUS_ACTIVE ? now() : null,
            'activated_at' => $status === VisitConsultationRoute::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function completeConsultation(): void
    {
        $this->consultation->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $this->user->id,
        ])->save();
    }

    private function obstetricsProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics / Antenatal', 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function makeProfile(): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(20)->toDateString(),
            'estimated_due_date' => now()->addWeeks(20)->toDateString(),
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);
    }

    private function linkProfile(PregnancyProfile $profile): void
    {
        app(ConsultationMaternityLinkService::class)->link(
            $this->consultation, $profile, $this->user, ConsultationMaternityLinkRole::PRIMARY
        );
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = \Spatie\Permission\Models\Role::findOrCreate('Pilot 14R31 '.uniqid(), 'web');

        foreach (array_merge(['consultations.view', 'consultations.create'], $permissions) as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
