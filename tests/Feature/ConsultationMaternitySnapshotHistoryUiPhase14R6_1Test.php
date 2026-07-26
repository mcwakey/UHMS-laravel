<?php

namespace Tests\Feature;

use App\Models\ConsultationMaternitySnapshot;
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
 * Phase 14R.6.1 — snapshot history navigation and the separate current-record
 * view. Both are GET-only and must never write.
 */
class ConsultationMaternitySnapshotHistoryUiPhase14R6_1Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'audit_streaming.async_writes' => false,
            'consultation.maternity_context.summary_projection_enabled' => true,
            'consultation.maternity_context.completion_snapshot_enabled' => true,
        ]);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
        View::share('errors', new ViewErrorBag);
    }

    /* ── Versions ──────────────────────────────────────────────────────── */

    public function test_both_versions_appear_and_the_latest_is_selected_by_default(): void
    {
        $this->twoVersions();

        $vm = $this->build();

        $this->assertSame(2, $vm->snapshotVersion);
        $this->assertSame([2, 1], $vm->history->pluck('version')->all());
        $this->assertSame(1, $vm->previousSnapshotVersion);
        $this->assertNull($vm->nextSnapshotVersion);
    }

    public function test_selecting_version_one_renders_version_one_and_changes_nothing(): void
    {
        $this->twoVersions();
        $v2Payload = ConsultationMaternitySnapshot::query()
            ->where('snapshot_version', 2)->value('payload');

        $response = $this->actingAs($this->summaryUser())
            ->get(route('admin.consultations.maternity-summary.history', $this->visit).'?version=1');

        $response->assertOk();
        $response->assertSee('v1', false);

        $vm = $this->build(version: 1);
        $this->assertSame(1, $vm->snapshotVersion);
        $this->assertSame(2, $vm->nextSnapshotVersion);
        // v1 shows the ORIGINAL gravida, not the value v2 captured.
        $this->assertSame(2, $vm->payload()['pregnancy']['gravida']);

        // v2 is untouched.
        $this->assertSame($v2Payload, ConsultationMaternitySnapshot::query()
            ->where('snapshot_version', 2)->value('payload'));
    }

    public function test_history_viewing_performs_no_writes(): void
    {
        $this->twoVersions();
        // Build the actor BEFORE listening: creating a user/role writes rows,
        // and those are fixture cost, not endpoint cost.
        $user = $this->summaryUser();

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes++;
            }
        });

        $this->actingAs($user)
            ->get(route('admin.consultations.maternity-summary.history', $this->visit).'?version=1')
            ->assertOk();

        $this->assertSame(0, $writes);
        $this->assertSame(2, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_a_snapshot_from_another_consultation_cannot_be_reached(): void
    {
        $this->twoVersions();

        // A second consultation on a different visit, with its own snapshot.
        $otherVisit = \App\Models\Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'current_department_id' => $this->department->id,
        ]);
        $otherRoute = VisitConsultationRoute::create([
            'visit_id' => $otherVisit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_at' => now(),
        ]);
        app(ConsultationMaternityLinkService::class)->link($otherRoute, $this->profile, $this->user);
        app(ConsultationRouteService::class)->completeRoute($otherRoute->fresh(), $this->user);

        $foreign = ConsultationMaternitySnapshot::query()
            ->forConsultation($otherRoute)->firstOrFail();

        // Asking THIS consultation for that version resolves to its own v1,
        // never to the other consultation's row.
        $presentation = app(ConsultationMaternitySummaryPresentationService::class);
        $resolved = $presentation->snapshotVersion($this->route->fresh(), (int) $foreign->snapshot_version);

        $this->assertNotNull($resolved);
        $this->assertNotSame($foreign->id, $resolved->id);
        $this->assertSame($this->route->id, (int) $resolved->consultation_route_id);
    }

    public function test_an_unknown_version_is_not_found(): void
    {
        $this->twoVersions();

        $this->actingAs($this->summaryUser())
            ->get(route('admin.consultations.maternity-summary.history', $this->visit).'?version=99')
            ->assertNotFound();
    }

    public function test_unauthorized_user_cannot_view_history(): void
    {
        $this->twoVersions();

        $this->actingAs($this->userWithPermissions(['consultations.view']))
            ->get(route('admin.consultations.maternity-summary.history', $this->visit))
            ->assertForbidden();
    }

    public function test_history_is_closed_while_the_summary_flag_is_off(): void
    {
        $this->twoVersions();
        config(['consultation.maternity_context.summary_projection_enabled' => false]);

        $this->actingAs($this->summaryUser())
            ->get(route('admin.consultations.maternity-summary.history', $this->visit))
            ->assertForbidden();
    }

    public function test_history_is_bounded_regardless_of_version_count(): void
    {
        $this->twoVersions();
        $user = $this->summaryUser();
        $user->can('consultation.maternity_context.summary.view');
        $service = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, []);
        $route = $this->route->fresh();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->build($route, $user);
        $snapshotQueries = collect(DB::getQueryLog())->pluck('query')
            ->filter(fn ($q) => str_contains($q, 'consultation_maternity_snapshots'))->count();
        DB::disableQueryLog();

        // latestFor() + one bounded history read. No per-version fan-out.
        $this->assertLessThanOrEqual(2, $snapshotQueries);
    }

    /* ── Current record ────────────────────────────────────────────────── */

    public function test_current_record_is_not_loaded_by_the_normal_completed_page(): void
    {
        $this->linkAndComplete();
        $user = $this->summaryUser();
        $user->can('consultation.maternity_context.summary.view');
        $service = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, []);
        $route = $this->route->fresh();

        DB::enableQueryLog();
        DB::flushQueryLog();
        $vm = $service->build($route, $user);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertTrue($vm->currentRecordAvailable, 'the ACTION should be offered');
        // …but the live data itself is not fetched.
        $this->assertFalse($queries->contains(fn ($q) => str_contains($q, 'antenatal_visits')));
    }

    public function test_current_record_endpoint_requires_bridge_and_maternity_permissions(): void
    {
        $this->linkAndComplete();

        $summaryOnly = $this->userWithPermissions(['consultation.maternity_context.summary.view']);
        $noMaternity = $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
        ]);

        foreach ([$summaryOnly, $noMaternity] as $user) {
            $this->actingAs($user)
                ->get(route('admin.consultations.maternity-summary.current', $this->visit))
                ->assertForbidden();
        }
    }

    public function test_current_record_renders_separately_labelled_and_writes_nothing(): void
    {
        $this->linkAndComplete();
        $this->profile->forceFill(['gravida' => 7])->save();
        $user = $this->summaryUser();

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes++;
            }
        });

        $response = $this->actingAs($user)
            ->get(route('admin.consultations.maternity-summary.current', $this->visit));

        $response->assertOk();
        $response->assertSee(__('consultation_maternity_summary.current.not_part_of_snapshot'), false);

        $this->assertSame(0, $writes);
        // The historical snapshot still reports the ORIGINAL value.
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();
        $this->assertSame(2, $snapshot->payload['pregnancy']['gravida']);
    }

    public function test_unconfirmed_context_is_not_presented_as_current_truth(): void
    {
        // Completed WITHOUT an explicit link: only a suggestion exists.
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);

        $payload = app(ConsultationMaternitySummaryPresentationService::class)
            ->currentRecord($this->route->fresh(), $this->summaryUser());

        $this->assertNull($payload);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function build(?int $version = null)
    {
        return app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $this->summaryUser(), version: $version);
    }

    private function linkAndComplete(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    private function twoVersions(): void
    {
        $this->linkAndComplete();

        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
        ])->save();

        $this->profile->forceFill(['gravida' => 4])->save();
        $this->travel(2)->seconds();

        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    /**
     * `consultations.view` is the enclosing read group's own requirement — the
     * maternity summary routes deliberately sit at the READ level, not behind
     * the write-level `consultations.create`.
     */
    private function summaryUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
            'maternity.pregnancy.view',
        ], baseline: ['consultations.view']);
    }
}
