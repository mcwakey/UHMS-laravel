<?php

namespace Tests\Feature;

use App\Models\ConsultationMaternitySnapshot;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternitySnapshotService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.7 — controlled reproduction of risk P2.
 * Phase 14R.8 — **P2 is CLOSED**; this suite is now the regression guard.
 *
 * When written, this suite existed to PROVE the defect: completion identity was
 * `route id + completed_at`, so a same-second reopen-and-recompletion collapsed
 * two genuine completions into one snapshot.
 *
 * 14R.8 replaced that identity with a durable completion-occurrence ULID. The
 * assertions below were therefore INVERTED — from "documents the collapse" to
 * "proves the collapse cannot happen". Nothing was weakened: the file now
 * asserts strictly more than it did.
 */
class ConsultationSnapshotSameSecondRiskPhase14R7Test extends TestCase
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
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    public function test_same_second_reopen_and_recompletion_creates_a_second_version(): void
    {
        // Freeze the clock so both completions land on the identical second —
        // the exact case that used to collapse.
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));

        $this->complete();
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $firstReference = ConsultationMaternitySnapshot::query()->value('completion_reference');

        $this->reopen();
        // A genuinely different clinical state on the second completion.
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $snapshots = ConsultationMaternitySnapshot::query()->orderBy('snapshot_version')->get();

        // P2 CLOSED: identity now comes from the completion-occurrence ledger,
        // not the clock, so the second completion is its own occurrence.
        $this->assertCount(
            2,
            $snapshots,
            'P2 regression: a same-second recompletion must create a new version.'
        );
        $this->assertNotSame($firstReference, $snapshots->last()->completion_reference);

        // Both clinical states are now preserved: v1 as captured, v2 as changed.
        $this->assertSame(2, $snapshots->first()->payload['pregnancy']['gravida']);
        $this->assertSame(6, $snapshots->last()->payload['pregnancy']['gravida']);

        Carbon::setTestNow();
    }

    public function test_one_second_apart_produces_two_distinct_versions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));
        $this->complete();

        $this->reopen();
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:01'));
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $snapshots = ConsultationMaternitySnapshot::query()->orderBy('snapshot_version')->get();

        // One second of separation is sufficient — the risk window is narrow.
        $this->assertCount(2, $snapshots);
        $this->assertSame(2, $snapshots->first()->payload['pregnancy']['gravida']);
        $this->assertSame(6, $snapshots->last()->payload['pregnancy']['gravida']);

        Carbon::setTestNow();
    }

    public function test_recompletion_never_corrupts_or_rewrites_the_first_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));

        $this->complete();
        $original = ConsultationMaternitySnapshot::query()->firstOrFail();
        $payload = $original->payload;
        $hash = $original->payload_hash;

        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $fresh = ConsultationMaternitySnapshot::query()->orderBy('snapshot_version')->firstOrFail();

        // v1 remains byte-identical and its hash still verifies, whether or not
        // a v2 exists.
        $this->assertSame($payload, $fresh->payload);
        $this->assertSame($hash, $fresh->payload_hash);
        $this->assertTrue($fresh->verifyPayloadHash());

        Carbon::setTestNow();
    }

    public function test_repeated_completion_without_a_reopen_is_still_correctly_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));

        $this->complete();
        $this->complete();
        $this->complete();

        // INTENDED idempotency: the route early-returns because it is already
        // completed, so no new occurrence and no new snapshot are created.
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(1, \App\Models\ConsultationCompletionOccurrence::query()->count());

        Carbon::setTestNow();
    }

    public function test_the_completion_reference_is_no_longer_derived_from_the_clock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));
        $this->complete();

        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();

        // The stored reference is occurrence-derived, not timestamp-derived.
        $this->assertStringStartsWith('occ:', $snapshot->completion_reference);
        $this->assertStringNotContainsString('2026-07-26', $snapshot->completion_reference);
        $this->assertNotNull($snapshot->completion_occurrence_id);

        // The legacy format is still available for reading historical rows.
        $legacy = app(ConsultationMaternitySnapshotService::class)
            ->legacyCompletionReference($this->route->fresh());
        $this->assertSame('route:'.$this->route->id.'@2026-07-26T10:00:00Z', $legacy);

        Carbon::setTestNow();
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

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
}
