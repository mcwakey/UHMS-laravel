<?php

namespace Tests\Feature;

use App\Data\Consultation\Maternity\ConsultationMaternitySummaryProjection as Projection;
use App\Models\ConsultationMaternitySnapshot;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\PregnancyProfile;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternitySnapshotService;
use App\Services\Consultation\Maternity\ConsultationMaternitySummaryService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.6 — live summary projection and immutable completion snapshots.
 */
class ConsultationMaternitySummarySnapshotPhase14R6Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private VisitConsultationRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        config(['audit_streaming.async_writes' => false]);
        $this->traceabilityFlags(summary: true, snapshot: true);
        $this->buildMaternityFixture();
        $this->route = $this->consultationRoute();
    }

    private function traceabilityFlags(
        bool $readiness = false,
        bool $summary = false,
        bool $snapshot = false,
    ): void {
        config([
            'consultation.maternity_context.readiness_enabled' => $readiness,
            'consultation.maternity_context.summary_projection_enabled' => $summary,
            'consultation.maternity_context.completion_snapshot_enabled' => $snapshot,
        ]);
    }

    /* ── Live projection ───────────────────────────────────────────────── */

    public function test_summary_flag_off_yields_no_projection_and_no_queries(): void
    {
        $this->traceabilityFlags();
        $this->link();
        $service = app(ConsultationMaternitySummaryService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $projection = $service->project($this->route);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(Projection::STATUS_NONE, $projection->contextStatus);
        $this->assertSame(0, $queries);
    }

    public function test_explicit_context_projects_the_curated_fields(): void
    {
        $this->profile->forceFill(['dating_method' => 'lmp'])->save();
        $this->link();

        $projection = app(ConsultationMaternitySummaryService::class)->project($this->route);

        $this->assertTrue($projection->isExplicit());
        $this->assertSame($this->profile->id, $projection->pregnancyProfileId);
        $this->assertSame($this->profile->id, $projection->pregnancy['id']);
        $this->assertSame(2, $projection->pregnancy['gravida']);
        $this->assertArrayHasKey('edd', $projection->pregnancy);
        // Dating method is READ from the profile, never re-derived.
        $this->assertArrayHasKey('gestational_age_source', $projection->pregnancy);
    }

    public function test_projection_excludes_disallowed_narrative(): void
    {
        $this->link();
        $this->ancVisit->forceFill([
            'assessment' => 'Long clinical assessment narrative',
            'plan' => 'Detailed plan narrative',
            'counselling' => 'Counselling narrative',
        ])->save();

        $payload = json_encode(
            app(ConsultationMaternitySummaryService::class)->project($this->route->fresh())->toCanonicalArray()
        );

        foreach (['Long clinical assessment', 'Detailed plan', 'Counselling narrative'] as $narrative) {
            $this->assertStringNotContainsString($narrative, $payload);
        }
    }

    public function test_suggested_and_ambiguous_context_carry_no_clinical_values(): void
    {
        // Suggested: the profile is on the same visit but not linked.
        $projection = app(ConsultationMaternitySummaryService::class)->project($this->route);

        $this->assertSame(Projection::STATUS_SUGGESTED, $projection->contextStatus);
        $this->assertNull($projection->pregnancyProfileId);
        $this->assertSame([], $projection->pregnancy);
        $this->assertFalse($projection->isSnapshotEligible());

        // Ambiguous: a SECOND profile also reachable from this visit, so the
        // inferring resolver finds two candidates and picks neither.
        \App\Models\AntenatalVisit::create([
            'pregnancy_profile_id' => $this->secondProfile()->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'visit_date' => now(),
            'visit_number' => 1,
            'status' => 'recorded',
        ]);

        $fresh = app()->makeWith(ConsultationMaternitySummaryService::class, []);
        $ambiguous = $fresh->project($this->consultationRoute());

        $this->assertSame(Projection::STATUS_AMBIGUOUS, $ambiguous->contextStatus);
        $this->assertSame([], $ambiguous->pregnancy);
    }

    public function test_projection_creates_no_specialty_entry_and_no_snapshot(): void
    {
        $this->link();
        $entries = ConsultationSpecialtyEntry::query()->count();

        app(ConsultationMaternitySummaryService::class)->project($this->route);

        $this->assertSame($entries, ConsultationSpecialtyEntry::query()->count());
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_newborn_order_is_deterministic(): void
    {
        $this->link();
        $delivery = $this->delivery();

        foreach ([[3, 'c'], [1, 'a'], [2, 'b']] as [$order, $_]) {
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

        $projection = app(ConsultationMaternitySummaryService::class)->project($this->route);
        $orders = array_column($projection->newborns, 'birth_order');

        $this->assertSame([1, 2, 3], $orders);
    }

    /* ── Snapshot capture ──────────────────────────────────────────────── */

    public function test_snapshot_flag_off_captures_nothing(): void
    {
        $this->traceabilityFlags(summary: true, snapshot: false);
        $this->link();

        $this->completeRoute();

        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $this->route->fresh()->status);
    }

    public function test_completing_an_explicitly_linked_consultation_captures_one_snapshot(): void
    {
        $this->link();

        $this->completeRoute();

        $snapshots = ConsultationMaternitySnapshot::query()->get();
        $this->assertCount(1, $snapshots);

        $snapshot = $snapshots->first();
        $this->assertSame(1, $snapshot->snapshot_version);
        $this->assertSame($this->profile->id, (int) $snapshot->pregnancy_profile_id);
        $this->assertSame(Projection::SCHEMA_VERSION, $snapshot->schema_version);
        $this->assertTrue($snapshot->verifyPayloadHash());
    }

    public function test_snapshot_is_written_inside_the_completion_transaction(): void
    {
        $this->link();

        // Rolling the outer transaction back must take the snapshot with it.
        DB::beginTransaction();
        $this->completeRoute();
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        DB::rollBack();

        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
        $this->assertNotSame(VisitConsultationRoute::STATUS_COMPLETED, $this->route->fresh()->status);
    }

    public function test_repeated_completion_is_idempotent(): void
    {
        $this->link();

        $this->completeRoute();
        $this->completeRoute();
        $this->completeRoute();

        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_reopen_and_recomplete_creates_version_two_leaving_version_one_intact(): void
    {
        $this->link();
        $this->completeRoute();

        $v1 = ConsultationMaternitySnapshot::query()->first();
        $v1Payload = $v1->payload;
        $v1Hash = $v1->payload_hash;

        // Reopen, change the maternity record, recomplete. `fresh()` matters:
        // the in-memory model still shows the pre-completion state, so
        // forceFill on it would mark nothing dirty and save nothing.
        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();

        $this->profile->forceFill(['gravida' => 5])->save();
        $this->travel(2)->seconds();
        $this->completeRoute();

        $all = ConsultationMaternitySnapshot::query()->orderBy('snapshot_version')->get();
        $this->assertCount(2, $all);
        $this->assertSame([1, 2], $all->pluck('snapshot_version')->map(fn ($v) => (int) $v)->all());
        $this->assertSame($v1->id, (int) $all->last()->previous_snapshot_id);

        // Version 1 is byte-identical to what was captured.
        $this->assertSame($v1Payload, $all->first()->payload);
        $this->assertSame($v1Hash, $all->first()->payload_hash);
        $this->assertSame(5, $all->last()->payload['pregnancy']['gravida']);
    }

    public function test_unconfirmed_context_captures_no_snapshot_but_completion_succeeds(): void
    {
        // Suggested only — nothing explicitly linked.
        $this->completeRoute();

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $this->route->fresh()->status);
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    /* ── Immutability ──────────────────────────────────────────────────── */

    public function test_snapshot_update_is_rejected(): void
    {
        $this->link();
        $this->completeRoute();
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $snapshot->update(['context_status' => 'tampered']);
    }

    public function test_snapshot_save_after_force_fill_is_rejected(): void
    {
        $this->link();
        $this->completeRoute();
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $snapshot->forceFill(['payload_hash' => str_repeat('0', 64)])->save();
    }

    public function test_snapshot_delete_is_rejected(): void
    {
        $this->link();
        $this->completeRoute();
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $snapshot->delete();
    }

    public function test_tampering_produces_a_hash_mismatch(): void
    {
        $this->link();
        $this->completeRoute();
        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        $this->assertTrue($snapshot->verifyPayloadHash());

        // Bypass the model guard to simulate direct database tampering.
        $payload = $snapshot->payload;
        $payload['pregnancy']['gravida'] = 99;
        DB::table('consultation_maternity_snapshots')
            ->where('id', $snapshot->id)
            ->update(['payload' => json_encode($payload)]);

        $this->assertFalse($snapshot->fresh()->verifyPayloadHash());
    }

    public function test_hash_is_stable_across_key_insertion_order(): void
    {
        $a = ['b' => 2, 'a' => 1, 'nested' => ['y' => 2, 'x' => 1]];
        $b = ['a' => 1, 'nested' => ['x' => 1, 'y' => 2], 'b' => 2];

        $projectionA = new Projection(
            contextStatus: Projection::STATUS_EXPLICIT, resolutionSource: 'explicit', pregnancy: $a
        );
        $projectionB = new Projection(
            contextStatus: Projection::STATUS_EXPLICIT, resolutionSource: 'explicit', pregnancy: $b
        );

        $this->assertSame(
            ConsultationMaternitySnapshot::hashPayload($projectionA->toCanonicalArray()),
            ConsultationMaternitySnapshot::hashPayload($projectionB->toCanonicalArray()),
        );
    }

    /* ── Completed summary binds to the snapshot ───────────────────────── */

    public function test_changing_maternity_data_after_completion_does_not_change_the_snapshot(): void
    {
        $this->link();
        $this->completeRoute();

        $captured = ConsultationMaternitySnapshot::query()->firstOrFail();
        $capturedGravida = $captured->payload['pregnancy']['gravida'];

        $this->profile->forceFill(['gravida' => 9])->save();

        $this->assertSame($capturedGravida, $captured->fresh()->payload['pregnancy']['gravida']);
        $this->assertNotSame(9, $captured->fresh()->payload['pregnancy']['gravida']);

        // The LIVE projection does reflect the change — the two are separate.
        $live = app(ConsultationMaternitySummaryService::class)->project($this->route->fresh(), force: true);
        $this->assertSame(9, $live->pregnancy['gravida']);
    }

    public function test_completed_consultation_without_a_snapshot_never_fabricates_one(): void
    {
        $this->traceabilityFlags(summary: true, snapshot: false);
        $this->link();
        $this->completeRoute();

        $snapshots = app(ConsultationMaternitySnapshotService::class);
        $this->assertNull($snapshots->latestFor($this->route->fresh()));
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());

        // Turning the flag on later must not retroactively invent history.
        $this->traceabilityFlags(summary: true, snapshot: true);
        $this->assertNull($snapshots->latestFor($this->route->fresh()));
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_snapshot_history_is_ordered_and_bounded(): void
    {
        $this->link();
        $this->completeRoute();

        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
        ])->save();
        $this->travel(2)->seconds();
        $this->completeRoute();

        $service = app(ConsultationMaternitySnapshotService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $history = $service->historyFor($this->route->fresh());
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame([2, 1], $history->pluck('snapshot_version')->map(fn ($v) => (int) $v)->all());
        // Bounded: the snapshot list plus its eager-loaded actor. Two versions
        // cost the same as twenty — there is no per-row fan-out.
        $this->assertLessThanOrEqual(3, $queries);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function link(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    private function completeRoute(): void
    {
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }
}
