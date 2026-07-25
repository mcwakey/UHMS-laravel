<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\ObstetricWorkspaceViewModel;
use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyDatingMethod;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\LaborEpisode;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternitySpecialtyWriteGuard;
use App\Services\Consultation\Maternity\ObstetricConsultationContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14R.3 — Obstetrics stage-aware workspace.
 *
 * Covers feature-flag gating, resolver-state behaviour, the dual-permission
 * actions (create profile / record ANC / start labor) and the server-side
 * field-level write guard.
 */
class ConsultationObstetricsMaternityWorkspacePhase14R3Test extends TestCase
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
        $this->disableFlags();

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

        $this->consultation = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            // Phase 14R.3.1: clinical-mutation actions require a genuinely
            // STARTED session, so the fixture must set started_at.
            'started_at' => now(),
            'activated_at' => now(),
        ]);
    }

    /* ── Feature flags ────────────────────────────────────────────────── */

    public function test_both_flags_off_render_nothing_and_do_not_resolve(): void
    {
        $this->makeProfile();

        $vm = $this->buildViewModel();

        $this->assertFalse($vm->shouldRender());
        $this->assertSame(ObstetricWorkspaceViewModel::MODE_DISABLED, $vm->mode());
        $this->assertSame([], $vm->writePolicy, 'no write policy when disabled');
    }

    public function test_workspace_enabled_with_guard_disabled_is_pilot_mode(): void
    {
        $this->enableWorkspace();
        $profile = $this->makeProfile();
        $this->linkProfile($profile);

        $vm = $this->buildViewModel();

        $this->assertTrue($vm->shouldRender());
        $this->assertSame(ObstetricWorkspaceViewModel::MODE_PILOT, $vm->mode());
        $this->assertFalse($vm->writeGuardEnabled, 'guard must stay off in pilot mode');
        $this->assertSame([], $vm->writePolicy, 'sections stay editable in pilot mode');
    }

    public function test_write_guard_never_activates_without_the_workspace_flag(): void
    {
        // Guard flag on, workspace flag OFF — must remain inert.
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => false,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => true,
        ]);

        $profile = $this->makeProfile();
        $this->linkProfile($profile);

        $guard = app(ConsultationMaternitySpecialtyWriteGuard::class);

        $this->assertFalse($guard->guardEnabled());
        $this->assertFalse($guard->applies($this->consultation, $this->obstetricsProfile()));
        $this->assertSame([], $guard->blockedFields(
            $this->consultation, $this->obstetricsProfile(), 'obstetric_history', ['gravida' => 3]
        ));
    }

    /* ── Context states ───────────────────────────────────────────────── */

    public function test_no_context_renders_workspace_without_creating_a_profile(): void
    {
        $this->enableWorkspace();

        $vm = $this->buildViewModel();

        $this->assertTrue($vm->shouldRender());
        $this->assertTrue($vm->isNone());
        $this->assertSame(0, PregnancyProfile::count(), 'opening the workspace must never create a profile');
    }

    public function test_inferred_context_is_suggested_not_persisted_and_does_not_guard(): void
    {
        $this->enableBoth();
        $this->makeProfile(); // single active profile → inferred

        $vm = $this->buildViewModel();

        $this->assertTrue($vm->isResolved());
        $this->assertTrue($vm->isInferred());
        $this->assertTrue($vm->showsSuggestion());
        $this->assertFalse($vm->showsFullRibbon());
        $this->assertFalse($vm->writeGuardEnabled, 'inferred context must not activate the guard');
        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_confirming_inferred_context_creates_an_explicit_link(): void
    {
        $this->enableWorkspace();
        $profile = $this->makeProfile();

        $this->actingAs($this->userWithBridge(['consultation.maternity_context.link']))
            ->post(route('admin.consultations.maternity-context.confirm', $this->visit))
            ->assertRedirect();

        $this->assertDatabaseCount('consultation_maternity_links', 1);
        $link = \App\Models\ConsultationMaternityLink::first();
        $this->assertSame($profile->id, $link->pregnancy_profile_id);
        $this->assertSame(ConsultationMaternityLinkRole::REVIEWED, $link->link_role);
    }

    public function test_ambiguous_context_offers_candidates_and_never_auto_selects(): void
    {
        $this->enableBoth();
        $this->makeProfile();
        $this->makeProfile();

        $vm = $this->buildViewModel();

        $this->assertTrue($vm->isAmbiguous());
        $this->assertSame([], $vm->pregnancy, 'no stage projection until a profile is chosen');
        $this->assertSame(2, $vm->candidateProfiles?->count());
        $this->assertFalse($vm->writeGuardEnabled);
        $this->assertTrue($vm->hasWarnings());
    }

    /* ── Actions ──────────────────────────────────────────────────────── */

    public function test_existing_same_patient_profile_can_be_linked(): void
    {
        $this->enableWorkspace();
        $profile = $this->makeProfile();

        $this->actingAs($this->userWithBridge(['consultation.maternity_context.link']))
            ->post(route('admin.consultations.maternity-context.link', $this->visit), [
                'pregnancy_profile_id' => $profile->id,
            ])->assertRedirect();

        $this->assertDatabaseCount('consultation_maternity_links', 1);
    }

    public function test_mismatched_patient_profile_cannot_be_linked(): void
    {
        $this->enableWorkspace();
        $foreign = $this->makeProfile(Patient::factory()->create());

        $this->actingAs($this->userWithBridge(['consultation.maternity_context.link']))
            ->post(route('admin.consultations.maternity-context.link', $this->visit), [
                'pregnancy_profile_id' => $foreign->id,
            ])->assertSessionHasErrors('pregnancy_profile_id');

        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    public function test_unlink_requires_a_reason(): void
    {
        $this->enableWorkspace();
        $this->linkProfile($this->makeProfile());

        $this->actingAs($this->userWithBridge([
            'consultation.maternity_context.link',
            'consultation.maternity_context.unlink',
        ]))->post(route('admin.consultations.maternity-context.unlink', $this->visit), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_profile_creation_requires_both_permissions(): void
    {
        $this->enableWorkspace();

        // Bridge permission only — underlying maternity permission missing.
        $this->actingAs($this->userWithBridge(['consultation.maternity_context.create_profile']))
            ->post(route('admin.consultations.maternity-context.create-profile', $this->visit), [
                'gravida' => 1,
            ])->assertForbidden();

        $this->assertSame(0, PregnancyProfile::count());
    }

    public function test_profile_can_be_created_with_both_permissions_and_is_linked_as_created(): void
    {
        $this->enableWorkspace();

        $this->actingAs($this->userWithBridge([
            'consultation.maternity_context.create_profile',
            'maternity.pregnancy.create',
        ]))->post(route('admin.consultations.maternity-context.create-profile', $this->visit), [
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(12)->toDateString(),
            'dating_method' => PregnancyDatingMethod::EARLY_ULTRASOUND->value,
        ])->assertRedirect();

        $this->assertSame(1, PregnancyProfile::count());
        $profile = PregnancyProfile::first();
        $this->assertSame(PregnancyDatingMethod::EARLY_ULTRASOUND, $profile->dating_method);

        $link = \App\Models\ConsultationMaternityLink::first();
        $this->assertSame(ConsultationMaternityLinkRole::CREATED, $link->link_role);

        $this->assertTrue(ActivityLog::where('event', 'PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION')->exists());
    }

    public function test_anc_recorded_from_consultation_creates_exactly_one_visit_and_no_specialty_entries(): void
    {
        $this->enableWorkspace();
        $profile = $this->makeProfile();
        $this->linkProfile($profile);

        $this->actingAs($this->userWithBridge([
            'consultation.maternity_context.record_anc',
            'maternity.anc.record',
        ]))->post(route('admin.consultations.maternity-context.record-anc', $this->visit), [
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'weight_kg' => 62.5,
            'fundal_height_cm' => 24,
            'fetal_heart_rate' => 140,
        ])->assertRedirect();

        $this->assertSame(1, AntenatalVisit::count(), 'exactly one AntenatalVisit');

        // No duplicated specialty JSON for maternity-owned sections.
        foreach (['antenatal_vitals', 'fetal_assessment', 'risk_assessment'] as $section) {
            $this->assertSame(
                0,
                ConsultationSpecialtyEntry::where('section_key', $section)->count(),
                "ANC action must not create a {$section} specialty entry"
            );
        }

        // ANC visit is linked to the consultation, profile link stays active.
        $this->assertTrue(
            \App\Models\ConsultationMaternityLink::query()
                ->forContextType(ConsultationMaternityContextType::ANC_VISIT)->active()->exists()
        );
        $this->assertTrue(
            \App\Models\ConsultationMaternityLink::query()
                ->forContextType(ConsultationMaternityContextType::PREGNANCY_PROFILE)->active()->exists()
        );
        $this->assertTrue(ActivityLog::where('event', 'ANC_VISIT_RECORDED_FROM_CONSULTATION')->exists());
    }

    public function test_inferred_but_unconfirmed_context_cannot_record_anc(): void
    {
        $this->enableWorkspace();
        $this->makeProfile(); // inferred only — never linked

        $this->actingAs($this->userWithBridge([
            'consultation.maternity_context.record_anc',
            'maternity.anc.record',
        ]))->post(route('admin.consultations.maternity-context.record-anc', $this->visit), [
            'fetal_heart_rate' => 140,
        ])->assertStatus(422);

        $this->assertSame(0, AntenatalVisit::count());
    }

    public function test_anc_requires_underlying_maternity_permission(): void
    {
        $this->enableWorkspace();
        $this->linkProfile($this->makeProfile());

        $this->actingAs($this->userWithBridge(['consultation.maternity_context.record_anc']))
            ->post(route('admin.consultations.maternity-context.record-anc', $this->visit), [
                'fetal_heart_rate' => 140,
            ])->assertForbidden();

        $this->assertSame(0, AntenatalVisit::count());
    }

    public function test_start_labor_creates_one_episode_and_reuses_an_active_one(): void
    {
        $this->enableWorkspace();
        $profile = $this->makeProfile();
        $this->linkProfile($profile);

        $actor = $this->userWithBridge([
            'consultation.maternity_context.start_labor',
            'maternity.labor.start',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.consultations.maternity-context.start-labor', $this->visit))
            ->assertRedirect();

        $this->assertSame(1, LaborEpisode::count());

        // Second call must NOT create a duplicate episode.
        $this->actingAs($actor)
            ->post(route('admin.consultations.maternity-context.start-labor', $this->visit))
            ->assertRedirect();

        $this->assertSame(1, LaborEpisode::count(), 'existing active episode must be reused');
        $this->assertTrue(ActivityLog::where('event', 'LABOR_EPISODE_STARTED_FROM_CONSULTATION')->exists());
    }

    public function test_labor_requires_underlying_maternity_permission(): void
    {
        $this->enableWorkspace();
        $this->linkProfile($this->makeProfile());

        $this->actingAs($this->userWithBridge(['consultation.maternity_context.start_labor']))
            ->post(route('admin.consultations.maternity-context.start-labor', $this->visit))
            ->assertForbidden();

        $this->assertSame(0, LaborEpisode::count());
    }

    /* ── Write guard ──────────────────────────────────────────────────── */

    public function test_guard_blocks_maternity_owned_fields_but_allows_consultation_owned_siblings(): void
    {
        $this->enableBoth();
        $this->linkProfile($this->makeProfile());

        $guard = app(ConsultationMaternitySpecialtyWriteGuard::class);
        $profile = $this->obstetricsProfile();

        // obstetric_history: gravida guarded, previous_complications writable.
        $blocked = $guard->blockedFields($this->consultation, $profile, 'obstetric_history', [
            'gravida' => 3,
            'previous_complications' => 'PPH in 2019',
        ]);
        $this->assertSame(['gravida'], $blocked);

        // fetal_assessment: presentation guarded, lie writable.
        $this->assertSame(
            ['presentation'],
            $guard->blockedFields($this->consultation, $profile, 'fetal_assessment', [
                'presentation' => 'cephalic', 'lie' => 'longitudinal',
            ])
        );

        // risk_assessment: risk_level guarded, action_plan writable.
        $this->assertSame(
            ['risk_level'],
            $guard->blockedFields($this->consultation, $profile, 'risk_assessment', [
                'risk_level' => 'high', 'action_plan' => 'Review in 1 week',
            ])
        );

        // lmp/edd/GA and ANC vitals fully guarded.
        $this->assertSame(
            ['lmp', 'gestational_age_weeks'],
            $guard->blockedFields($this->consultation, $profile, 'lmp_edd_gestational_age', [
                'lmp' => '2026-01-01', 'gestational_age_weeks' => 20,
            ])
        );
        $this->assertSame(
            ['blood_pressure', 'weight'],
            $guard->blockedFields($this->consultation, $profile, 'antenatal_vitals', [
                'blood_pressure' => '120/80', 'weight' => 62,
            ])
        );
    }

    public function test_guard_is_inert_without_an_explicit_link(): void
    {
        $this->enableBoth();
        $this->makeProfile(); // inferred only

        $this->assertSame([], app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $this->obstetricsProfile(), 'obstetric_history', ['gravida' => 3]
        ));
    }

    public function test_guard_never_applies_to_non_obstetrics_profiles(): void
    {
        $this->enableBoth();
        $this->linkProfile($this->makeProfile());

        $gynae = ConsultationSpecialtyProfile::where('code', 'gynecology')->first();

        if (! $gynae) {
            $this->markTestSkipped('gynecology profile not seeded in this environment');
        }

        $this->assertSame([], app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $gynae, 'obstetric_history', ['gravida' => 3]
        ));
    }

    public function test_guard_disabled_preserves_current_write_behaviour(): void
    {
        $this->enableWorkspace(); // guard flag stays off
        $this->linkProfile($this->makeProfile());

        $this->assertSame([], app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $this->obstetricsProfile(), 'antenatal_vitals', ['weight' => 60]
        ));
    }

    public function test_historical_entries_are_never_deleted_by_the_guard(): void
    {
        $entry = ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'section_key' => 'antenatal_vitals',
            'entry' => ['weight' => 61],
        ]);

        $this->enableBoth();
        $this->linkProfile($this->makeProfile());

        app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $this->obstetricsProfile(), 'antenatal_vitals', ['weight' => 62]
        );

        $this->assertDatabaseHas('consultation_specialty_entries', ['id' => $entry->id]);
    }

    /* ── Performance ──────────────────────────────────────────────────── */

    public function test_context_is_resolved_once_per_request(): void
    {
        $this->enableWorkspace();
        $this->linkProfile($this->makeProfile());

        $service = app(ObstetricConsultationContextService::class);

        $first = $service->context($this->consultation);
        $second = $service->context($this->consultation);

        $this->assertSame($first, $second, 'context must be memoised per request');
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function disableFlags(): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => false,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => false,
        ]);
    }

    private function enableWorkspace(): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => true,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => false,
        ]);
    }

    private function enableBoth(): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => true,
            'consultation.maternity_context.obstetrics_write_guard_enabled' => true,
        ]);
    }

    private function obstetricsProfile(): ?ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics / Antenatal', 'is_active' => true, 'sort_order' => 40]
        );
    }

    private function buildViewModel(): ObstetricWorkspaceViewModel
    {
        return app(ObstetricConsultationContextService::class)->build(
            $this->consultation,
            $this->obstetricsProfile(),
            $this->user,
        );
    }

    private function makeProfile(?Patient $patient = null): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => ($patient ?? $this->patient)->id,
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

    /** A user holding exactly the given permissions (plus consultation basics). */
    private function userWithBridge(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);

        $role = \Spatie\Permission\Models\Role::findOrCreate('Obstetrics 14R3 '.uniqid(), 'web');

        foreach (array_merge(['consultations.view', 'consultations.create'], $permissions) as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
