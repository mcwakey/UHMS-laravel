<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\ConsultationMaternitySummaryViewModel as ViewModel;
use App\Models\ConsultationMaternitySnapshot;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternitySummaryPresentationService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.6.1 — real summary UI.
 *
 * The load-bearing rule: an ACTIVE consultation shows current maternity truth;
 * a COMPLETED one shows what was true at completion.
 */
class ConsultationMaternitySummaryUiPhase14R6_1Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->uiFlags(summary: true, snapshot: true);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
        View::share('errors', new ViewErrorBag);
    }

    private function uiFlags(bool $readiness = false, bool $summary = false, bool $snapshot = false): void
    {
        config([
            'consultation.maternity_context.readiness_enabled' => $readiness,
            'consultation.maternity_context.summary_projection_enabled' => $summary,
            'consultation.maternity_context.completion_snapshot_enabled' => $snapshot,
        ]);
    }

    /* ── Flag off ──────────────────────────────────────────────────────── */

    public function test_summary_flag_off_renders_nothing_and_costs_no_queries(): void
    {
        $this->uiFlags();
        $this->link();
        $service = app(ConsultationMaternitySummaryPresentationService::class);
        $user = $this->summaryUser();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $vm = $service->build($this->route, $user);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertFalse($vm->shouldRender());
        $this->assertSame(ViewModel::MODE_UNAVAILABLE, $vm->mode);
        $this->assertSame(0, $queries);
        $this->assertSame('', trim($this->renderLive($vm)));
        $this->assertSame('', trim($this->renderSnapshot($vm)));
    }

    public function test_summary_permission_is_required(): void
    {
        $this->link();
        $withoutPermission = $this->userWithPermissions(['consultations.view']);

        $vm = app(ConsultationMaternitySummaryPresentationService::class)
            ->build($this->route, $withoutPermission);

        $this->assertFalse($vm->shouldRender());
    }

    /* ── Active: live projection ───────────────────────────────────────── */

    public function test_active_explicit_consultation_renders_current_maternity_record(): void
    {
        $this->link();

        $vm = $this->build();
        $html = $this->renderLive($vm);

        $this->assertTrue($vm->isLive());
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.current_record'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.source_of_truth'), $html);
        // Never labelled as completion-time data.
        $this->assertStringNotContainsString(
            __('consultation_maternity_summary.summary.completion_snapshot_title'),
            $html
        );
    }

    public function test_active_summary_creates_no_snapshot_and_no_specialty_entry(): void
    {
        $this->link();
        $entries = ConsultationSpecialtyEntry::query()->count();

        $this->renderLive($this->build());

        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame($entries, ConsultationSpecialtyEntry::query()->count());
    }

    public function test_suggested_and_ambiguous_contexts_render_no_clinical_values(): void
    {
        // Nothing linked: the same-visit records make this SUGGESTED only.
        $html = $this->renderLive($this->build());

        $this->assertStringContainsString(__('maternity_handoffs.cards.no_context'), $html);
        $this->assertStringNotContainsString('gravida', strtolower($html));
    }

    public function test_excluded_narrative_never_leaks_into_the_rendered_summary(): void
    {
        $this->link();
        $this->ancVisit->forceFill([
            'assessment' => 'SECRETASSESSMENTNARRATIVE',
            'plan' => 'SECRETPLANNARRATIVE',
            'counselling' => 'SECRETCOUNSELLING',
        ])->save();

        $html = $this->renderLive($this->build());

        foreach (['SECRETASSESSMENTNARRATIVE', 'SECRETPLANNARRATIVE', 'SECRETCOUNSELLING'] as $secret) {
            $this->assertStringNotContainsString($secret, $html);
        }
    }

    public function test_gynaecology_without_context_renders_no_maternity_values(): void
    {
        $vm = app(ConsultationMaternitySummaryPresentationService::class)
            ->build($this->route, $this->summaryUser(), isGynaecology: true);

        $html = $this->renderLive($vm);

        $this->assertStringNotContainsString(__('consultation_maternity.gynaecology.remains_gynaecology'), $html);
        $this->assertStringContainsString(__('maternity_handoffs.cards.no_context'), $html);
    }

    public function test_gynaecology_with_explicit_link_states_it_remains_gynaecology(): void
    {
        $this->link();

        $vm = app(ConsultationMaternitySummaryPresentationService::class)
            ->build($this->route->fresh(), $this->summaryUser(), isGynaecology: true);

        $this->assertStringContainsString(
            __('consultation_maternity.gynaecology.remains_gynaecology'),
            $this->renderLive($vm)
        );
    }

    /* ── Completed: snapshot ───────────────────────────────────────────── */

    public function test_completed_consultation_defaults_to_the_latest_snapshot(): void
    {
        $this->link();
        $this->complete();

        $vm = $this->build();
        $html = $this->renderSnapshot($vm);

        $this->assertTrue($vm->isSnapshot());
        $this->assertSame(1, $vm->snapshotVersion);
        $this->assertStringContainsString(
            __('consultation_maternity_summary.summary.completion_snapshot_title'),
            $html
        );
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.historical_label'), $html);
    }

    public function test_completed_summary_does_not_load_live_maternity_data(): void
    {
        $this->link();
        $this->complete();

        $service = app(ConsultationMaternitySummaryPresentationService::class);
        $user = $this->summaryUser();
        $route = $this->route->fresh();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $vm = $service->build($route, $user);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertTrue($vm->isSnapshot());
        // No fan-out into the live maternity tables.
        foreach (['antenatal_visits', 'labor_episodes', 'delivery_records', 'newborn_records', 'postnatal_cases'] as $table) {
            $this->assertFalse(
                $queries->contains(fn ($q) => str_contains($q, $table)),
                "Completed summary must not query {$table}"
            );
        }
    }

    public function test_changing_maternity_data_after_completion_does_not_change_the_rendered_summary(): void
    {
        $this->link();
        $this->complete();

        $before = $this->renderSnapshot($this->build());

        $this->profile->forceFill(['gravida' => 9])->save();

        $after = $this->renderSnapshot(
            app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
                ->build($this->route->fresh(), $this->summaryUser())
        );

        $this->assertSame($before, $after);
        $this->assertStringNotContainsString('>9<', $after);
    }

    public function test_snapshot_metadata_and_verified_integrity_render(): void
    {
        $this->link();
        $this->complete();

        $html = $this->renderSnapshot($this->build());

        $this->assertStringContainsString(__('consultation_maternity_summary.summary.captured_at'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.schema_version'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.verified'), $html);
    }

    public function test_tampered_snapshot_renders_a_mismatch_warning_without_repairing_it(): void
    {
        $this->link();
        $this->complete();
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        $payload = $snapshot->payload;
        $payload['pregnancy']['gravida'] = 99;
        DB::table('consultation_maternity_snapshots')->where('id', $snapshot->id)
            ->update(['payload' => json_encode($payload)]);

        $hashBefore = $snapshot->fresh()->payload_hash;
        $html = $this->renderSnapshot($this->build());

        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.hash_mismatch'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.tamper_evidence_warning'), $html);
        // Not repaired, not re-hashed, not deleted.
        $this->assertSame($hashBefore, $snapshot->fresh()->payload_hash);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_no_raw_json_and_no_mutation_form_is_rendered(): void
    {
        $this->link();
        $this->complete();

        $html = $this->renderSnapshot($this->build());

        $this->assertStringNotContainsString('{"schema_version"', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('method="POST"', $html);
        $this->assertStringNotContainsString('_method', $html);
    }

    /* ── No snapshot ───────────────────────────────────────────────────── */

    public function test_completed_without_a_snapshot_says_so_and_fabricates_nothing(): void
    {
        $this->uiFlags(summary: true, snapshot: false);
        $this->link();
        $this->complete();

        $vm = $this->build();
        $html = $this->renderSnapshot($vm);

        $this->assertTrue($vm->isMissingSnapshot());
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.no_snapshot_available'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.none_fabricated'), $html);
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_enabling_capture_later_does_not_fabricate_history(): void
    {
        $this->uiFlags(summary: true, snapshot: false);
        $this->link();
        $this->complete();

        $this->uiFlags(summary: true, snapshot: true);
        $vm = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $this->summaryUser());

        $this->assertTrue($vm->isMissingSnapshot());
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_existing_snapshot_stays_visible_when_capture_is_later_disabled(): void
    {
        $this->link();
        $this->complete();

        $this->uiFlags(summary: true, snapshot: false);
        $vm = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $this->summaryUser());

        $this->assertTrue($vm->isSnapshot());
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /* ── Reopened ──────────────────────────────────────────────────────── */

    public function test_reopened_consultation_shows_live_values_with_history_available(): void
    {
        $this->link();
        $this->complete();
        $this->reopen();

        $vm = $this->build();

        $this->assertTrue($vm->isLive());
        $this->assertTrue($vm->isReopened);
        $this->assertTrue($vm->hasHistory());
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());

        $html = $this->renderLive($vm);
        $this->assertStringContainsString(__('consultation_maternity_summary.reopened.title'), $html);
    }

    public function test_recompletion_makes_the_new_version_the_default(): void
    {
        $this->link();
        $this->complete();
        $this->reopen();
        $this->travel(2)->seconds();
        $this->complete();

        $vm = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $this->summaryUser());

        $this->assertTrue($vm->isSnapshot());
        $this->assertSame(2, $vm->snapshotVersion);
        $this->assertSame(2, ConsultationMaternitySnapshot::query()->count());
    }

    /* ── Performance ───────────────────────────────────────────────────── */

    public function test_completed_snapshot_default_is_bounded_and_newborn_count_does_not_matter(): void
    {
        $this->link();
        $delivery = $this->delivery();
        foreach ([1, 2, 3] as $order) {
            $this->newborn($delivery, $order);
        }
        $this->complete();

        $threeCost = $this->measure();

        // More newborns exist only inside the stored payload — the completed
        // page reads snapshot rows regardless of how many babies there were.
        foreach ([4, 5] as $order) {
            $this->newborn($delivery, $order);
        }

        $fiveCost = $this->measure();

        $this->assertSame($threeCost, $fiveCost, 'newborn count must not change the query cost');
        // The snapshot path itself stays at the measured two-query shape:
        // latestFor() + the bounded history read.
        $this->assertLessThanOrEqual(2, $threeCost['snapshot']);
    }

    public function test_presentation_is_memoised_per_request(): void
    {
        $this->link();
        $service = app(ConsultationMaternitySummaryPresentationService::class);
        $user = $this->summaryUser();

        $service->build($this->route, $user);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->build($this->route, $user);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries);
    }

    public function test_partials_perform_no_queries(): void
    {
        $this->link();
        $this->complete();
        $vm = $this->build();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->renderSnapshot($vm);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    /**
     * Count only the queries the presentation itself issues.
     *
     * The permission cache is warmed first: Spatie loads a role's permissions
     * once per user, which is request-setup cost, not per-render cost.
     *
     * @return array{total: int, snapshot: int}
     */
    private function measure(): array
    {
        $service = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, []);
        $route = $this->route->fresh();
        $user = $this->summaryUser();
        $user->can('consultation.maternity_context.summary.view');

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->build($route, $user);
        $log = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        return [
            'total' => $log->count(),
            'snapshot' => $log->filter(fn ($q) => str_contains($q, 'consultation_maternity_snapshots'))->count(),
        ];
    }

    private function newborn($delivery, int $order): void
    {
        \App\Models\NewbornRecord::create([
            'delivery_record_id' => $delivery->id,
            'labor_episode_id' => $this->labor->id,
            'pregnancy_profile_id' => $this->profile->id,
            'mother_patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'birth_order' => $order,
            'birth_time' => now(),
        ]);
    }

    private function build(): ViewModel
    {
        return app(ConsultationMaternitySummaryPresentationService::class)
            ->build($this->route->fresh(), $this->summaryUser());
    }

    private function renderLive(ViewModel $vm): string
    {
        return view('consultations.partials.maternity.summary-live', ['maternitySummary' => $vm])->render();
    }

    private function renderSnapshot(ViewModel $vm): string
    {
        return view('consultations.partials.maternity.summary-snapshot', ['maternitySummary' => $vm])->render();
    }

    private function link(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    private function complete(): void
    {
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    private function reopen(): void
    {
        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();
    }

    private function summaryUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
            'maternity.pregnancy.view',
        ]);
    }
}
