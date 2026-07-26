<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\GynaecologyWorkspaceViewModel;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\GynaecologyConsultationContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 14R.4.1 (R1) — Gynaecology pilot wiring + performance.
 *
 * Verifies the card renders through the real view model, stays explicit-only,
 * and adds zero cost when the flag is off or the profile is not Gynaecology.
 */
class ConsultationGynaecologyMaternityPilotPhase14R4_1Test extends TestCase
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
            'type' => DepartmentType::CONSULTATION->value, 'status' => 'active',
        ]);
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id, 'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT, 'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);
        $this->consultation = VisitConsultationRoute::create([
            'visit_id' => $this->visit->id, 'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id, 'started_at' => now(), 'activated_at' => now(),
        ]);
    }

    /* ── Card rendering through the real partial ──────────────────────── */

    public function test_flags_off_render_an_empty_card(): void
    {
        $this->makeProfile();

        $this->assertSame('', trim($this->renderCard($this->build())));
    }

    public function test_non_gynaecology_profile_renders_an_empty_card(): void
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

        $this->assertSame('', trim($this->renderCard($vm)));
    }

    public function test_enabled_and_unlinked_shows_only_the_discreet_link_action(): void
    {
        $this->flags(true, false);
        $this->makeProfile(); // exists but NOT linked

        $html = $this->renderCard($this->build($this->linker()));

        $this->assertStringContainsString(__('consultation_maternity.gynaecology.start_or_link'), $html);
        // No profile detail leaks from an unlinked profile.
        $this->assertStringNotContainsString(__('consultation_maternity.ribbon.edd'), $html);
        $this->assertStringNotContainsString(__('consultation_maternity.gynaecology.remains_gynaecology'), $html);
    }

    public function test_explicit_link_renders_the_small_card_with_the_gynaecology_statement(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());

        $html = $this->renderCard($this->build($this->linker()));

        $this->assertStringContainsString(__('consultation_maternity.gynaecology.explicitly_linked_profile'), $html);
        $this->assertStringContainsString(__('consultation_maternity.gynaecology.remains_gynaecology'), $html);
    }

    public function test_card_never_renders_anc_or_labor_actions(): void
    {
        $this->flags(true, true);
        $this->link($this->makeProfile());

        // A user holding every ANC/labor permission.
        $html = $this->renderCard($this->build($this->userWith([
            'consultation.maternity_context.view',
            'consultation.maternity_context.record_anc', 'maternity.anc.record',
            'consultation.maternity_context.start_labor', 'maternity.labor.start',
        ])));

        $this->assertStringNotContainsString(__('consultation_maternity.actions.record_anc'), $html);
        $this->assertStringNotContainsString(__('consultation_maternity.actions.start_labor'), $html);
    }

    public function test_rollout_markers_reflect_the_active_mode(): void
    {
        $this->link($this->makeProfile());

        $viewer = $this->viewer();

        $this->flags(true, false);
        $pilot = $this->renderCard($this->build($viewer), $viewer);
        $this->assertStringContainsString(__('consultation_maternity.gynaecology.pilot_mode'), $pilot);

        $this->flags(true, true);
        $guarded = $this->renderCard($this->build($viewer), $viewer);
        $this->assertStringContainsString(__('consultation_maternity.gynaecology.source_of_truth_mode'), $guarded);
    }

    public function test_markers_are_hidden_from_users_without_view_permission(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());

        // User without consultation.maternity_context.view.
        $noViewer = $this->userWith([]);
        $html = $this->renderCard($this->build($noViewer), $noViewer);

        $this->assertStringNotContainsString(__('consultation_maternity.gynaecology.pilot_mode'), $html);
    }

    public function test_positive_test_affordance_creates_nothing(): void
    {
        $this->flags(true, false);
        ConsultationSpecialtyEntry::create([
            'consultation_id' => $this->consultation->id,
            'section_key' => 'sexual_sti_history',
            'entry' => ['pregnancy_test' => 'Positive'],
        ]);

        $html = $this->renderCard($this->build($this->linker()));

        $this->assertStringContainsString(__('consultation_maternity.gynaecology.positive_test_recorded'), $html);
        $this->assertStringContainsString(
            __('consultation_maternity.gynaecology.no_profile_created_automatically'), $html
        );
        $this->assertSame(0, PregnancyProfile::count());
        $this->assertDatabaseCount('consultation_maternity_links', 0);
    }

    /* ── Performance (7 required fixtures) ────────────────────────────── */

    public function test_query_counts_across_all_measured_fixtures(): void
    {
        $gynae = $this->gynaeProfile();
        $obstetrics = ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'obstetrics'],
            ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
        );

        $fresh = function () {
            app()->forgetInstance(GynaecologyConsultationContextService::class);

            return app(GynaecologyConsultationContextService::class);
        };

        // A — flags off.
        $this->flags(false, false);
        $a = $this->countQueries(fn () => $fresh()->build($this->consultation, $gynae, $this->user));

        // B — enabled, no explicit link.
        $this->flags(true, false);
        $b = $this->countQueries(fn () => $fresh()->build($this->consultation, $gynae, $this->user));

        // C — enabled, explicit link.
        $profile = $this->makeProfile();
        $this->link($profile);
        $c = $this->countQueries(fn () => $fresh()->build($this->consultation, $gynae, $this->user));

        // D — explicit link + latest ANC.
        AntenatalVisit::create([
            'pregnancy_profile_id' => $profile->id, 'patient_id' => $this->patient->id,
            'department_id' => $this->department->id, 'recorded_by' => $this->user->id,
            'visit_date' => now(), 'status' => 'recorded',
        ]);
        $d = $this->countQueries(fn () => $fresh()->build($this->consultation, $gynae, $this->user));

        // F — non-Gynaecology with the flag enabled globally.
        $f = $this->countQueries(fn () => $fresh()->build($this->consultation, $obstetrics, $this->user));

        fwrite(STDERR, sprintf(
            "\nGYNAE QUERY COUNTS  off=%d  unlinked=%d  linked=%d  linked+anc=%d  non_gynae=%d\n",
            $a, $b, $c, $d, $f
        ));

        $this->assertSame(0, $a, 'flags off must add zero queries');
        $this->assertSame(0, $f, 'non-Gynaecology must add zero queries');
        // Explicit-only: the unlinked path must stay far below the six-table
        // fallback chain the Obstetrics resolver can perform.
        $this->assertLessThanOrEqual(10, $b, "unlinked issued {$b} queries — fallback chain may have leaked in");
        $this->assertLessThanOrEqual(12, $d, "linked+ANC issued {$d} queries");
    }

    public function test_context_is_resolved_once_per_request(): void
    {
        $this->flags(true, false);
        $this->link($this->makeProfile());

        $service = app(GynaecologyConsultationContextService::class);
        $first = $service->context($this->consultation);
        $second = $service->context($this->consultation);

        $this->assertSame($first, $second);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    private function flags(bool $context, bool $guard): void
    {
        config([
            'consultation.maternity_context.gynaecology_context_enabled' => $context,
            'consultation.maternity_context.gynaecology_write_guard_enabled' => $guard,
        ]);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /** Renders the real partial — proves it performs no queries of its own. */
    private function renderCard(GynaecologyWorkspaceViewModel $vm, ?User $as = null): string
    {
        // Blade's @can resolves the AUTHENTICATED user, not the one passed to
        // build(), so the acting user must be set for permission-gated markup.
        if ($as) {
            $this->actingAs($as);
        }

        // Phase 14R.5.1 — the card now renders its triggers from typed actions
        // prepared by ConsultationMaternityModalPresenter, exactly as the real
        // workspace does. Built BEFORE the query window: the presenter is
        // controller-side work, the partial itself must still issue no query.
        $actions = app(\App\Services\Consultation\Maternity\ConsultationMaternityModalPresenter::class)
            ->gynaecology($vm, $this->consultation, $as ?? $this->user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $html = view('consultations.partials.maternity.gynaecology-context-card', [
            'gynaecology' => $vm,
            'visit' => $this->visit,
            'gynaecologyMaternityActions' => $actions,
        ])->render();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries, 'the Blade partial must not query the database');

        return $html;
    }

    private function build(?User $user = null): GynaecologyWorkspaceViewModel
    {
        app()->forgetInstance(GynaecologyConsultationContextService::class);

        return app(GynaecologyConsultationContextService::class)
            ->build($this->consultation, $this->gynaeProfile(), $user ?? $this->user);
    }

    private function gynaeProfile(): ConsultationSpecialtyProfile
    {
        return ConsultationSpecialtyProfile::firstOrCreate(
            ['code' => 'gynecology'],
            ['name' => 'Gynecology', 'is_active' => true, 'sort_order' => 50]
        );
    }

    private function makeProfile(): PregnancyProfile
    {
        return PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'last_menstrual_period' => now()->subWeeks(10)->toDateString(),
            'estimated_due_date' => now()->addWeeks(30)->toDateString(),
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);
    }

    private function link(PregnancyProfile $profile): void
    {
        app(ConsultationMaternityLinkService::class)->link(
            $this->consultation, $profile, $this->user, ConsultationMaternityLinkRole::PRIMARY
        );
    }

    private function viewer(): User
    {
        return $this->userWith(['consultation.maternity_context.view']);
    }

    private function linker(): User
    {
        return $this->userWith([
            'consultation.maternity_context.view',
            'consultation.maternity_context.link',
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = \Spatie\Permission\Models\Role::findOrCreate('GynaePilot '.uniqid(), 'web');

        foreach (array_merge(['consultations.view'], $permissions) as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
