<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyDatingMethod;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ActivityLog;
use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
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
use App\Services\Consultation\Maternity\GynaecologyConsultationContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 14R.4 — Gynaecology separation, explicit pregnancy transition and
 * one-way LMP adoption.
 *
 * Core invariant under test: Gynaecology never acquires maternity context
 * implicitly and never becomes Obstetrics.
 */
class ConsultationGynaecologyMaternityPhase14R4Test extends TestCase
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
        $this->consultation = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_at' => now(),
            'activated_at' => now(),
        ]);
    }

    /* ── Flags + explicit-only resolution ─────────────────────────────── */

    public function test_flags_off_render_nothing(): void
    {
        $this->makeProfile();

        $vm = $this->build();

        $this->assertFalse($vm->shouldRender());
        $this->assertSame('disabled', $vm->mode());
    }

    public function test_non_gynaecology_profile_renders_nothing(): void
    {
        $this->flags(true, false);

        $vm = app(GynaecologyConsultationContextService::class)->build(
            $this->consultation,
            ConsultationSpecialtyProfile::firstOrCreate(
                ['code' => 'obstetrics'],
                ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
            ),
            $this->user,
        );

        $this->assertFalse($vm->shouldRender());
    }

    public function test_gynaecology_never_infers_context_from_a_single_active_profile(): void
    {
        $this->flags(true, false);
        // An active profile exists on the same visit — Obstetrics would infer
        // this, but Gynaecology must NOT.
        $this->makeProfile();

        $vm = $this->build();

        $this->assertTrue($vm->shouldRender());
        $this->assertFalse($vm->hasExplicitLink, 'Gynaecology must use explicit-only resolution');
        $this->assertFalse($vm->showsCard());
        $this->assertTrue($vm->showsLinkAffordance());
    }

    public function test_explicit_link_shows_the_small_card(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);

        $vm = $this->build();

        $this->assertTrue($vm->hasExplicitLink);
        $this->assertTrue($vm->showsCard());
        $this->assertSame($profile->id, $vm->pregnancyProfile?->id);
    }

    public function test_write_guard_cannot_activate_while_context_flag_is_off(): void
    {
        config([
            'consultation.maternity_context.gynaecology_context_enabled' => false,
            'consultation.maternity_context.gynaecology_write_guard_enabled' => true,
        ]);
        $this->link($this->makeProfile());

        $guard = app(ConsultationMaternitySpecialtyWriteGuard::class);

        $this->assertFalse($guard->gynaecologyGuardEnabled());
        $this->assertSame([], $guard->blockedFields(
            $this->consultation, $this->gynaeProfile(), 'obstetric_history', ['gravida' => 3]
        ));
    }

    /* ── No automatic transition ──────────────────────────────────────── */

    public function test_positive_pregnancy_test_creates_nothing_and_never_switches_specialty(): void
    {
        $this->flags(true, true);
        $this->savePregnancyTest('Positive');

        $vm = $this->build();

        $this->assertTrue($vm->positivePregnancyTestRecorded);
        $this->assertSame(0, PregnancyProfile::count(), 'must not create a profile');
        $this->assertDatabaseCount('consultation_maternity_links', 0);
        $this->assertFalse($vm->hasExplicitLink);
        // Specialty profile is untouched.
        $this->assertSame('gynecology', $this->gynaeProfile()->code);
    }

    public function test_negative_pregnancy_test_shows_no_prompt(): void
    {
        $this->flags(true, false);
        $this->savePregnancyTest('Negative');

        $this->assertFalse($this->build()->positivePregnancyTestRecorded);
    }

    public function test_linking_a_profile_creates_no_anc_or_labor_and_keeps_specialty(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());

        $this->assertSame(0, AntenatalVisit::count());
        $this->assertSame(0, LaborEpisode::count());
        $this->assertSame(1, ConsultationMaternityLink::query()->active()->count());
    }

    public function test_gynaecology_never_exposes_anc_or_labor_actions(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());

        // A user holding every maternity permission still gets no ANC/labor here.
        $vm = app(GynaecologyConsultationContextService::class)->build(
            $this->consultation,
            $this->gynaeProfile(),
            $this->userWith([
                'consultation.maternity_context.record_anc', 'maternity.anc.record',
                'consultation.maternity_context.start_labor', 'maternity.labor.start',
            ]),
        );

        $this->assertArrayNotHasKey('record_anc', $vm->availableActions);
        $this->assertArrayNotHasKey('start_labor', $vm->availableActions);
    }

    /* ── Gynaecology obstetric history ownership ──────────────────────── */

    public function test_unlinked_obstetric_history_remains_writable(): void
    {
        $this->flags(true, true);

        $this->assertSame([], app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $this->gynaeProfile(), 'obstetric_history', ['gravida' => 3]
        ));
    }

    public function test_linked_pilot_mode_remains_writable(): void
    {
        $this->flags(true, false); // guard off
        $this->link($this->makeProfile());

        $this->assertSame([], app(ConsultationMaternitySpecialtyWriteGuard::class)->blockedFields(
            $this->consultation, $this->gynaeProfile(), 'obstetric_history', ['gravida' => 3]
        ));
    }

    public function test_linked_guarded_mode_blocks_only_profile_owned_fields(): void
    {
        $this->flags(true, true);
        $this->link($this->makeProfile());

        $guard = app(ConsultationMaternitySpecialtyWriteGuard::class);

        $this->assertSame(
            ['gravida', 'para'],
            $guard->blockedFields($this->consultation, $this->gynaeProfile(), 'obstetric_history', [
                'gravida' => 3, 'para' => 2, 'previous_complications' => 'PPH 2019',
            ]),
            'previous_complications must remain writable'
        );

        // menstrual_history.lmp is NEVER guarded (decision R2).
        $this->assertSame([], $guard->blockedFields(
            $this->consultation, $this->gynaeProfile(), 'menstrual_history', ['lmp' => '2026-01-01']
        ));

        // Other gynaecology sections are untouched.
        foreach (['contraceptive_history', 'sexual_sti_history', 'pelvic_examination', 'breast_examination'] as $section) {
            $this->assertSame([], $guard->blockedFields(
                $this->consultation, $this->gynaeProfile(), $section, ['anything' => 'value']
            ), "{$section} must not become maternity-owned");
        }
    }

    /* ── One-way LMP adoption ─────────────────────────────────────────── */

    public function test_adoption_populates_a_null_profile_lmp_and_sets_dating_method(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit))
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame('2026-01-15', $profile->last_menstrual_period->toDateString());
        $this->assertSame(PregnancyDatingMethod::LMP, $profile->dating_method);
        $this->assertTrue(ActivityLog::where('event', 'GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING')->exists());
    }

    public function test_consultation_lmp_is_never_modified_by_adoption(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());
        $entry = $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit));

        $this->assertSame('2026-01-15', $entry->fresh()->entry['lmp'], 'one-way only');
    }

    public function test_client_cannot_substitute_another_lmp(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())->post(
            route('admin.consultations.maternity-context.adopt-lmp', $this->visit),
            ['lmp' => '2020-01-01', 'last_menstrual_period' => '2020-01-01']
        );

        // Server-side source wins.
        $this->assertSame('2026-01-15', $profile->fresh()->last_menstrual_period->toDateString());
    }

    public function test_adoption_without_a_saved_lmp_is_rejected(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit))
            ->assertRedirect();

        $this->assertNull($profile->fresh()->last_menstrual_period);
    }

    public function test_conflicting_profile_lmp_blocks_adoption(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile(['last_menstrual_period' => '2025-11-01']);
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit));

        $this->assertSame('2025-11-01', $profile->fresh()->last_menstrual_period->toDateString(), 'never overwritten');
    }

    public function test_ultrasound_dating_blocks_adoption(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile(['dating_method' => PregnancyDatingMethod::EARLY_ULTRASOUND->value]);
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit));

        $this->assertNull($profile->fresh()->last_menstrual_period, 'scan dating must not be replaced');
        $this->assertSame(PregnancyDatingMethod::EARLY_ULTRASOUND, $profile->fresh()->dating_method);
    }

    public function test_adoption_requires_both_permissions(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        // Bridge permission only.
        $this->actingAs($this->userWith(['consultation.maternity_context.adopt_lmp']))
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit))
            ->assertForbidden();

        $this->assertNull($profile->fresh()->last_menstrual_period);
    }

    public function test_completed_consultation_blocks_adoption(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile();
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->consultation->forceFill([
            'status' => VisitConsultationRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit))
            ->assertStatus(422);

        $this->assertNull($profile->fresh()->last_menstrual_period);
    }

    public function test_adoption_is_idempotent_when_dates_already_match(): void
    {
        $this->flags(true, false);
        $profile = $this->makeProfile(['last_menstrual_period' => '2026-01-15']);
        $this->link($profile);
        $this->saveMenstrualLmp('2026-01-15');

        $this->actingAs($this->adopter())
            ->post(route('admin.consultations.maternity-context.adopt-lmp', $this->visit))
            ->assertRedirect();

        $this->assertSame('2026-01-15', $profile->fresh()->last_menstrual_period->toDateString());
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function flags(bool $context, bool $guard): void
    {
        config([
            'consultation.maternity_context.gynaecology_context_enabled' => $context,
            'consultation.maternity_context.gynaecology_write_guard_enabled' => $guard,
        ]);
    }

    private function build(): GynaecologyWorkspaceViewModel
    {
        app()->forgetInstance(GynaecologyConsultationContextService::class);

        return app(GynaecologyConsultationContextService::class)
            ->build($this->consultation, $this->gynaeProfile(), $this->user);
    }

    private function gynaeProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'gynecology'],
            ['name' => 'Gynecology', 'is_active' => true, 'sort_order' => 50]
        );
    }

    private function makeProfile(array $attributes = []): PregnancyProfile
    {
        return PregnancyProfile::create($attributes + [
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);
    }

    private function link(PregnancyProfile $profile): void
    {
        app(ConsultationMaternityLinkService::class)->link(
            $this->consultation, $profile, $this->user, ConsultationMaternityLinkRole::PRIMARY
        );
    }

    private function saveMenstrualLmp(string $date): ConsultationSpecialtyEntry
    {
        return ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'consultation_specialty_profile_id' => $this->gynaeProfile()->id,
            'section_key' => 'menstrual_history',
            'entry' => ['lmp' => $date],
        ]);
    }

    private function savePregnancyTest(string $value): void
    {
        ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'consultation_specialty_profile_id' => $this->gynaeProfile()->id,
            'section_key' => 'sexual_sti_history',
            'entry' => ['pregnancy_test' => $value],
        ]);
    }

    private function adopter(): User
    {
        return $this->userWith([
            'consultation.maternity_context.adopt_lmp',
            'maternity.pregnancy.update',
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = \Spatie\Permission\Models\Role::findOrCreate('Gynae 14R4 '.uniqid(), 'web');

        foreach (array_merge(['consultations.view', 'consultations.create'], $permissions) as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
