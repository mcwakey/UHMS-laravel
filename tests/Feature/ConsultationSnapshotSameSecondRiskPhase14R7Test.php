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
 * Phase 14R.7 — controlled reproduction of documented risk P2.
 *
 * The snapshot completion identity is `route id + completed_at`, which is
 * SECOND-granular. This suite establishes, rather than assumes, whether a
 * reopen-and-recomplete inside the same second collapses two genuine
 * completions into one snapshot.
 *
 * This phase deliberately does NOT redesign the snapshot identity. The purpose
 * here is to make the risk visible and measurable in the readiness verdict.
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

    public function test_same_second_reopen_and_recompletion_collapses_into_one_snapshot(): void
    {
        // Freeze the clock so both completions land on the identical second —
        // the worst case the identity scheme can encounter.
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));

        $this->complete();
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $firstReference = ConsultationMaternitySnapshot::query()->value('completion_reference');

        $this->reopen();
        // A genuinely different clinical state on the second completion.
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $snapshots = ConsultationMaternitySnapshot::query()->orderBy('snapshot_version')->get();

        // DOCUMENTED OUTCOME: the two completions share a reference, so the
        // second is treated as the same occurrence and no v2 is written.
        $this->assertCount(
            1,
            $snapshots,
            'P2 confirmed: a same-second recompletion is treated as the same completion occurrence.'
        );
        $this->assertSame($firstReference, $snapshots->first()->completion_reference);

        // The surviving snapshot still reflects the FIRST completion — the
        // second completion's clinical state is not captured anywhere.
        $this->assertSame(2, $snapshots->first()->payload['pregnancy']['gravida']);

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

    public function test_the_collapse_never_corrupts_or_rewrites_the_first_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));

        $this->complete();
        $original = ConsultationMaternitySnapshot::query()->firstOrFail();
        $payload = $original->payload;
        $hash = $original->payload_hash;

        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $fresh = ConsultationMaternitySnapshot::query()->firstOrFail();

        // The failure mode is a MISSING second version, never a corrupted
        // first one: v1 remains byte-identical and its hash still verifies.
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

        // This is the INTENDED idempotency, not the P2 collapse: the route
        // early-returns because it is already completed.
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());

        Carbon::setTestNow();
    }

    public function test_the_completion_reference_is_second_granular_by_construction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-26 10:00:00'));
        $this->complete();

        $service = app(ConsultationMaternitySnapshotService::class);
        $reference = $service->completionReference($this->route->fresh());

        // Documents the exact shape the risk derives from.
        $this->assertSame('route:'.$this->route->id.'@2026-07-26T10:00:00Z', $reference);
        $this->assertStringNotContainsString('.', $reference, 'no sub-second component');

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
