<?php

namespace Tests\Feature;

use App\Models\ConsultationCompletionOccurrence;
use App\Models\ConsultationMaternitySnapshot;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\ConsultationCompletionOccurrenceService;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\Consultation\Maternity\ConsultationMaternitySnapshotService;
use App\Services\Consultation\Maternity\ConsultationMaternitySummaryPresentationService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.8 — the dedicated closure suite for risk P2.
 *
 * P2: completion identity was `route:{id}@{completed_at}` — SECOND-granular —
 * so a reopen-and-recompletion inside the same second was treated as the same
 * completion occurrence and no v2 was written. The clinical state at the second
 * completion was lost. This is a record-completeness defect, not a corruption
 * defect: v1 was always intact.
 *
 * The fix binds snapshot identity to a durable completion-occurrence ULID
 * allocated inside the completion transaction. Identity no longer reads a
 * clock, so the frozen-clock cases below are the real proof.
 *
 * Test numbering matches the Phase 14R.8 specification (checks 1–41) so the
 * test matrix document and this file cannot drift apart.
 */
class ConsultationSnapshotCompletionIdentityPhase14R8Test extends TestCase
{
    use BuildsMaternityHandoffFixtures;
    use RefreshDatabase;

    private const FROZEN = '2026-07-26 10:00:00';

    private VisitConsultationRoute $route;

    private ?\App\Models\User $summaryUser = null;

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
        View::share('errors', new ViewErrorBag);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | Same-second completion identity (checks 1–6)
    |--------------------------------------------------------------------------
    */

    /** Checks 1–6: three completions in ONE frozen second produce v1, v2, v3. */
    public function test_three_same_second_completion_cycles_create_three_distinct_versions(): void
    {
        $this->freeze();

        $this->complete();                                   // 1. v1
        $v1 = ConsultationMaternitySnapshot::query()->firstOrFail();
        $v1Payload = $v1->payload;
        $v1Hash = $v1->payload_hash;
        $v1Reference = $v1->completion_reference;

        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();                                   // 2. v2

        $this->reopen();
        $this->profile->forceFill(['gravida' => 7])->save();
        $this->complete();                                   // 3. v3

        $snapshots = ConsultationMaternitySnapshot::query()
            ->orderBy('snapshot_version')->get();

        $this->assertCount(3, $snapshots, 'Three genuine occurrences must produce three versions.');
        $this->assertSame([1, 2, 3], $snapshots->pluck('snapshot_version')->all());

        // Every completed_at is the SAME second — proving identity is not
        // derived from the clock.
        $this->assertSame(
            [self::FROZEN],
            ConsultationCompletionOccurrence::query()
                ->pluck('completed_at')->map(fn ($v) => Carbon::parse($v)->format('Y-m-d H:i:s'))
                ->unique()->values()->all()
        );

        // 4. Distinct completion-occurrence identities.
        $this->assertCount(3, $snapshots->pluck('completion_occurrence_id')->unique());
        $this->assertCount(3, $snapshots->pluck('completion_reference')->unique());
        $this->assertSame([1, 2, 3], ConsultationCompletionOccurrence::query()
            ->orderBy('occurrence_number')->pluck('occurrence_number')->all());

        // 5. v1 byte-identical after v2 and v3.
        $freshV1 = $snapshots->firstWhere('snapshot_version', 1);
        $this->assertSame($v1Payload, $freshV1->payload);
        $this->assertSame($v1Hash, $freshV1->payload_hash);
        $this->assertSame($v1Reference, $freshV1->completion_reference);

        // 6. Every hash verifies, and each version holds its own clinical state.
        foreach ($snapshots as $snapshot) {
            $this->assertTrue($snapshot->verifyPayloadHash(), "v{$snapshot->snapshot_version} hash must verify.");
        }
        $this->assertSame(
            [2, 6, 7],
            $snapshots->map(fn ($s) => $s->payload['pregnancy']['gravida'])->all()
        );
    }

    /** Check 4 (identity shape): the occurrence lineage is an explicit chain. */
    public function test_occurrence_lineage_is_chained_and_uses_generated_identifiers(): void
    {
        $this->freeze();
        $this->complete();
        $this->reopen();
        $this->complete();

        $occurrences = ConsultationCompletionOccurrence::query()
            ->orderBy('occurrence_number')->get();

        $this->assertNull($occurrences[0]->previous_occurrence_id);
        $this->assertSame($occurrences[0]->id, $occurrences[1]->previous_occurrence_id);

        // ULIDs, not timestamps, not sequences derived from the clock.
        foreach ($occurrences as $occurrence) {
            $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $occurrence->occurrence_uid);
            $this->assertSame('occ:'.$occurrence->occurrence_uid, $occurrence->snapshotReference());
        }
        $this->assertNotSame($occurrences[0]->occurrence_uid, $occurrences[1]->occurrence_uid);

        // The transition is recorded truthfully.
        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $occurrences[1]->to_status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $occurrences[1]->from_status);
    }

    /*
    |--------------------------------------------------------------------------
    | Intended idempotency (checks 7–11)
    |--------------------------------------------------------------------------
    */

    /** Check 7: repeat completion without reopen → one occurrence, one snapshot. */
    public function test_repeat_completion_without_reopen_creates_one_occurrence_and_one_snapshot(): void
    {
        $this->freeze();

        $this->complete();
        $this->complete();
        $this->complete();

        $this->assertSame(1, ConsultationCompletionOccurrence::query()->count());
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 8: retrying the same completion request creates no duplicate. */
    public function test_retrying_the_same_completion_request_creates_no_duplicate_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        $before = ConsultationMaternitySnapshot::query()->firstOrFail();

        // Same request, replayed — e.g. a double-submitted form or a client retry.
        for ($i = 0; $i < 5; $i++) {
            $this->complete();
        }

        $after = ConsultationMaternitySnapshot::query()->get();
        $this->assertCount(1, $after);
        $this->assertSame($before->id, $after->first()->id);
        $this->assertSame($before->payload_hash, $after->first()->payload_hash);
    }

    /** Check 9: invoking capture twice for ONE occurrence yields one snapshot. */
    public function test_capturing_twice_for_the_same_occurrence_returns_the_same_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());
        $this->assertNotNull($occurrence);

        $service = app(ConsultationMaternitySnapshotService::class);
        $first = $service->captureForCompletion($this->route->fresh(), $this->user, $occurrence);
        $second = $service->captureForCompletion($this->route->fresh(), $this->user, $occurrence);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second->id, 'One occurrence must resolve to one snapshot.');
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 10: duplicate listener/event delivery cannot fan out into versions. */
    public function test_duplicate_capture_delivery_for_one_occurrence_creates_one_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());
        $service = app(ConsultationMaternitySnapshotService::class);

        // Ten redelivered "completion" events for the same occurrence.
        for ($i = 0; $i < 10; $i++) {
            $service->captureForCompletion($this->route->fresh(), $this->user, $occurrence);
        }

        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->max('snapshot_version'));
    }

    /** Check 11: a one-second-separated cycle still creates v2 (no regression). */
    public function test_a_one_second_separated_reopen_and_recompletion_still_creates_v2(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FROZEN));
        $this->complete();

        Carbon::setTestNow(Carbon::parse(self::FROZEN)->addSecond());
        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $this->assertSame(2, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame([1, 2], ConsultationMaternitySnapshot::query()
            ->orderBy('snapshot_version')->pluck('snapshot_version')->all());
    }

    /*
    |--------------------------------------------------------------------------
    | Concurrency (checks 12–15)
    |--------------------------------------------------------------------------
    */

    /**
     * Check 12: two concurrent completion requests create one snapshot.
     *
     * Real thread-level concurrency is not available in this suite, so the
     * guarantee is tested where it actually lives: the unique index. A second
     * insert for the same occurrence is forced and must be absorbed, not
     * duplicated. This tests the LAST line of defence, which is the one that
     * has to hold when the row lock is bypassed.
     */
    public function test_a_racing_insert_for_the_same_occurrence_cannot_create_a_second_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        $existing = ConsultationMaternitySnapshot::query()->firstOrFail();

        $duplicate = fn () => ConsultationMaternitySnapshot::create([
            'consultation_route_id' => $existing->consultation_route_id,
            'pregnancy_profile_id' => $existing->pregnancy_profile_id,
            'snapshot_version' => 2,
            'schema_version' => $existing->schema_version,
            'context_status' => $existing->context_status,
            'resolution_source' => $existing->resolution_source,
            // The SAME occurrence reference a racing request would compute.
            'completion_reference' => $existing->completion_reference,
            'completion_occurrence_id' => $existing->completion_occurrence_id,
            'payload' => $existing->payload,
            'payload_hash' => $existing->payload_hash,
            'captured_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        try {
            $duplicate();
        } finally {
            $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        }
    }

    /**
     * Check 12 (real double-submit): two request-scoped bindings of the SAME
     * route, both loaded while still ACTIVE, must produce ONE occurrence.
     *
     * Found by independent review of this phase. Before the fix, the status
     * guard sat OUTSIDE the completion transaction, so the second request
     * re-ran the completion write, minted occurrence #2 and produced a
     * duplicate v2 with byte-identical clinical content — and a ledger row
     * asserting a second completion that never happened.
     */
    public function test_a_double_submitted_completion_creates_only_one_occurrence(): void
    {
        $this->freeze();

        // Two independently loaded bindings, exactly as two HTTP requests hold.
        $first = VisitConsultationRoute::query()->findOrFail($this->route->id);
        $second = VisitConsultationRoute::query()->findOrFail($this->route->id);

        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $first->status);
        $this->assertSame(VisitConsultationRoute::STATUS_ACTIVE, $second->status);

        $service = app(ConsultationRouteService::class);
        $service->completeRoute($first, $this->user);
        $service->completeRoute($second, $this->user);

        $this->assertSame(
            1,
            ConsultationCompletionOccurrence::query()->count(),
            'A double-submitted completion must not fabricate a second occurrence.'
        );
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 13: concurrent capture calls for one occurrence resolve to one row. */
    public function test_concurrent_capture_calls_resolve_to_a_single_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());

        // Two service instances, as two workers would hold.
        $a = app()->make(ConsultationMaternitySnapshotService::class);
        $b = app()->make(ConsultationMaternitySnapshotService::class);

        $first = $a->captureForCompletion($this->route->fresh(), $this->user, $occurrence);
        $second = $b->captureForCompletion($this->route->fresh(), $this->user, $occurrence);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 14: version numbers stay unique and monotonic across many cycles. */
    public function test_version_numbers_remain_unique_and_monotonic(): void
    {
        $this->freeze();

        for ($i = 0; $i < 5; $i++) {
            if ($i > 0) {
                $this->reopen();
                $this->profile->forceFill(['gravida' => 2 + $i])->save();
            }
            $this->complete();
        }

        $versions = ConsultationMaternitySnapshot::query()
            ->orderBy('snapshot_version')->pluck('snapshot_version')->all();

        $this->assertSame([1, 2, 3, 4, 5], $versions);
        $this->assertSame($versions, array_values(array_unique($versions)));

        // Occurrence numbers advance in lockstep, all inside one second.
        $this->assertSame([1, 2, 3, 4, 5], ConsultationCompletionOccurrence::query()
            ->orderBy('occurrence_number')->pluck('occurrence_number')->all());
    }

    /** Check 15: a unique conflict resolves only through VERIFIED lineage. */
    public function test_a_unique_conflict_resolves_only_when_the_lineage_belongs_to_this_route(): void
    {
        $this->freeze();
        $this->complete();

        $service = app(ConsultationCompletionOccurrenceService::class);
        $route = $this->route->fresh();

        // The winner of a race must be a real occurrence for THIS route.
        $winner = $service->latestFor($route);
        $this->assertNotNull($winner);
        $this->assertSame((int) $route->id, (int) $winner->consultation_route_id);

        // A different route's occurrence is never accepted as this route's.
        $otherRoute = $this->consultationRoute();
        app(ConsultationRouteService::class)->completeRoute($otherRoute->fresh(), $this->user);

        $this->assertNull($service->latestFor($otherRoute)->previous_occurrence_id);
        $this->assertNotSame(
            $service->latestFor($route)->id,
            $service->latestFor($otherRoute->fresh())->id,
            'Occurrence lineage must never cross routes.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Failure and retry (checks 16–20)
    |--------------------------------------------------------------------------
    */

    /** Check 16: failure before occurrence persistence leaves no false success. */
    public function test_a_failure_inside_the_completion_transaction_rolls_back_everything(): void
    {
        $this->freeze();

        // Force the snapshot capture (which runs after the occurrence insert,
        // inside the same transaction) to blow up.
        $this->app->bind(ConsultationMaternitySnapshotService::class, function () {
            throw new \RuntimeException('capture exploded');
        });

        try {
            $this->complete();
            $this->fail('The completion should have propagated the failure.');
        } catch (\Throwable $e) {
            $this->assertSame('capture exploded', $e->getMessage());
        }

        // Nothing survived: no completion, no occurrence, no snapshot.
        $this->assertNotSame(
            VisitConsultationRoute::STATUS_COMPLETED,
            $this->route->fresh()->status,
            'A failed capture must not leave a falsely completed consultation.'
        );
        $this->assertSame(0, ConsultationCompletionOccurrence::query()->count());
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 17: after a recovered occurrence, resume creates the snapshot once. */
    public function test_a_completion_whose_snapshot_is_missing_can_resume_against_the_same_occurrence(): void
    {
        $this->freeze();
        $this->complete();

        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());

        // Simulate "occurrence committed, snapshot lost" using the fixture-only
        // raw delete. This is manual-test cleanup, never a clinical path.
        DB::table('consultation_maternity_snapshots')->delete();
        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());

        $service = app(ConsultationMaternitySnapshotService::class);
        $recovered = $service->captureForCompletion($this->route->fresh(), $this->user, $occurrence);
        $again = $service->captureForCompletion($this->route->fresh(), $this->user, $occurrence);

        $this->assertNotNull($recovered);
        // Resumed against the SAME occurrence — not a fabricated new one.
        $this->assertSame($occurrence->id, $recovered->completion_occurrence_id);
        $this->assertSame($occurrence->snapshotReference(), $recovered->completion_reference);
        $this->assertSame($recovered->id, $again->id);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(1, ConsultationCompletionOccurrence::query()->count());
    }

    /** Check 18: retry after a committed snapshot resolves the existing row. */
    public function test_retry_after_a_committed_snapshot_resolves_the_existing_row(): void
    {
        $this->freeze();
        $this->complete();

        $committed = ConsultationMaternitySnapshot::query()->firstOrFail();
        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());

        // The response never reached the client; the client retries.
        $resolved = app(ConsultationMaternitySnapshotService::class)
            ->captureForCompletion($this->route->fresh(), $this->user, $occurrence);

        $this->assertSame($committed->id, $resolved->id);
        $this->assertSame($committed->payload_hash, $resolved->payload_hash);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /** Check 19: a tampered hash is detected and never silently accepted. */
    public function test_a_hash_mismatch_is_detected_rather_than_falsely_accepted(): void
    {
        $this->freeze();
        $this->complete();

        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();
        $this->assertTrue($snapshot->verifyPayloadHash());

        // Raw tamper, bypassing the model guards entirely.
        DB::table('consultation_maternity_snapshots')
            ->where('id', $snapshot->id)
            ->update(['payload_hash' => str_repeat('0', 64)]);

        $this->assertFalse(
            $snapshot->fresh()->verifyPayloadHash(),
            'Tamper evidence must fail closed.'
        );
    }

    /** Check 20: a later reopen creates a new occurrence after a recovery. */
    public function test_a_reopen_after_a_recovered_completion_creates_a_new_occurrence(): void
    {
        $this->freeze();
        $this->complete();

        DB::table('consultation_maternity_snapshots')->delete();
        $occurrence = app(ConsultationCompletionOccurrenceService::class)
            ->currentFor($this->route->fresh());
        app(ConsultationMaternitySnapshotService::class)
            ->captureForCompletion($this->route->fresh(), $this->user, $occurrence);

        $this->reopen();
        $this->profile->forceFill(['gravida' => 9])->save();
        $this->complete();

        $this->assertSame(2, ConsultationCompletionOccurrence::query()->count());
        $this->assertSame(2, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(9, ConsultationMaternitySnapshot::query()
            ->orderByDesc('snapshot_version')->first()->payload['pregnancy']['gravida']);
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy compatibility (checks 21–25)
    |--------------------------------------------------------------------------
    */

    /** Checks 21–23: a legacy-reference snapshot stays readable, verifiable and ordered. */
    public function test_a_legacy_reference_snapshot_remains_readable_verifiable_and_ordered(): void
    {
        $this->freeze();
        $this->complete();

        // Rewrite v1 into its PRE-14R.8 shape: legacy reference, no occurrence
        // link. This is exactly how a row captured before this phase looks.
        $v1 = ConsultationMaternitySnapshot::query()->firstOrFail();
        $legacyReference = app(ConsultationMaternitySnapshotService::class)
            ->legacyCompletionReference($this->route->fresh());
        $originalHash = $v1->payload_hash;
        $originalPayload = $v1->payload;

        DB::table('consultation_maternity_snapshots')->where('id', $v1->id)->update([
            'completion_reference' => $legacyReference,
            'completion_occurrence_id' => null,
        ]);

        $legacy = ConsultationMaternitySnapshot::query()->findOrFail($v1->id);

        // 21. Readable, and correctly classified as legacy.
        $this->assertSame($legacyReference, $legacy->completion_reference);
        $this->assertTrue($legacy->usesLegacyCompletionReference());
        $this->assertTrue(ConsultationCompletionOccurrence::isLegacyReference($legacyReference));

        // 22. Hash and payload unchanged, still verifying.
        $this->assertSame($originalHash, $legacy->payload_hash);
        $this->assertSame($originalPayload, $legacy->payload);
        $this->assertTrue($legacy->verifyPayloadHash());

        // 23. A new occurrence still appends after it and ordering holds.
        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $history = app(ConsultationMaternitySnapshotService::class)
            ->historyFor($this->route->fresh());

        $this->assertSame([2, 1], $history->pluck('snapshot_version')->all());
        $this->assertTrue($history->last()->usesLegacyCompletionReference());
        $this->assertFalse($history->first()->usesLegacyCompletionReference());
        $this->assertTrue($history->first()->verifyPayloadHash());
        $this->assertTrue($history->last()->verifyPayloadHash());
    }

    /** Check 24: no backfilled identity is presented as a genuine historical fact. */
    public function test_legacy_rows_are_not_backfilled_with_a_fabricated_occurrence(): void
    {
        $this->freeze();
        $this->complete();

        $v1 = ConsultationMaternitySnapshot::query()->firstOrFail();
        DB::table('consultation_maternity_snapshots')->where('id', $v1->id)->update([
            'completion_reference' => 'route:'.$this->route->id.'@2026-01-01T09:00:00Z',
            'completion_occurrence_id' => null,
        ]);
        DB::table('consultation_completion_occurrences')->delete();

        // Completing again must NOT invent an occurrence for the historical row.
        $this->reopen();
        $this->complete();

        $this->assertNull(
            ConsultationMaternitySnapshot::query()->findOrFail($v1->id)->completion_occurrence_id,
            'A historical snapshot must never be given a fabricated occurrence.'
        );
        $this->assertSame(
            'route:'.$this->route->id.'@2026-01-01T09:00:00Z',
            ConsultationMaternitySnapshot::query()->findOrFail($v1->id)->completion_reference
        );

        // The new occurrence describes only the completion that really happened.
        $occurrences = ConsultationCompletionOccurrence::query()->get();
        $this->assertCount(1, $occurrences);
        $this->assertNull($occurrences->first()->previous_occurrence_id);
    }

    /** Check 25: append-only guards still reject update and delete. */
    public function test_snapshot_and_occurrence_mutation_guards_still_reject_update_and_delete(): void
    {
        $this->freeze();
        $this->complete();

        $snapshot = ConsultationMaternitySnapshot::query()->firstOrFail();
        $occurrence = ConsultationCompletionOccurrence::query()->firstOrFail();

        foreach ([$snapshot, $occurrence] as $model) {
            $class = class_basename($model);

            try {
                $model->update(['metadata' => ['tampered' => true]]);
                $this->fail("{$class} must reject update().");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('immutable', strtolower($e->getMessage()));
            }

            try {
                $model->delete();
                $this->fail("{$class} must reject delete().");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('immutable', strtolower($e->getMessage()));
            }
        }

        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame(1, ConsultationCompletionOccurrence::query()->count());

        // Neither table carries updated_at — append-only by schema, not policy.
        $this->assertNull(ConsultationMaternitySnapshot::UPDATED_AT);
        $this->assertNull(ConsultationCompletionOccurrence::UPDATED_AT);
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow protection (checks 26–38)
    |--------------------------------------------------------------------------
    */

    /** Checks 26–28: preview, summary and print all select the same version. */
    public function test_preview_summary_and_print_all_select_the_latest_version(): void
    {
        $this->freeze();
        $this->complete();
        $this->reopen();
        $this->profile->forceFill(['gravida' => 6])->save();
        $this->complete();

        $screen = $this->build();
        $print = $this->build(printMode: true);

        // 26/28. Summary and print select the SAME version — v2.
        $this->assertSame(2, $screen->snapshotVersion);
        $this->assertSame(2, $print->snapshotVersion);
        $this->assertSame([2, 1], $screen->history->pluck('version')->all());
        $this->assertSame($screen->snapshotPayload, $print->snapshotPayload);

        // 27. The preview body is server-rendered and inert: no scripts, no
        // forms, no raw JSON payload dump.
        $html = view('consultations.partials.maternity.summary-payload', [
            'payload' => $screen->snapshotPayload,
            'printMode' => false,
            'isGynaecology' => false,
        ])->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('payload_hash', $html);
        $this->assertStringNotContainsString('"pregnancy":', $html);
    }

    /** Check 29: the current live record stays separate from the snapshot. */
    public function test_the_current_live_record_remains_separate_from_the_snapshot(): void
    {
        $this->freeze();
        $this->complete();

        // Live data moves on after the snapshot is sealed.
        $this->profile->forceFill(['gravida' => 11])->save();

        $vm = $this->build();

        // The completed page offers the current record as an ACTION; it does
        // not load it inline.
        $this->assertTrue($vm->currentRecordAvailable);
        $this->assertSame(2, $vm->snapshotPayload['pregnancy']['gravida'], 'The snapshot must not follow live data.');

        // The live record is a separate, explicitly requested read.
        $current = app(ConsultationMaternitySummaryPresentationService::class)
            ->currentRecord($this->route->fresh(), $this->summaryUser());

        $this->assertNotNull($current);
        $this->assertSame(11, $current['pregnancy']['gravida']);

        // And the sealed snapshot is still intact.
        $this->assertTrue(ConsultationMaternitySnapshot::query()->firstOrFail()->verifyPayloadHash());
    }

    /** Check 30: without the summary permission, no snapshot context is exposed. */
    public function test_permission_denial_omits_the_maternity_snapshot_context(): void
    {
        $this->freeze();
        $this->complete();

        $denied = $this->userWithPermissions([], baseline: ['consultations.view']);

        $vm = app(ConsultationMaternitySummaryPresentationService::class)
            ->build($this->route->fresh(), $denied);

        $this->assertFalse($vm->enabled);
        $this->assertNull($vm->selectedSnapshot);
        $this->assertNull($vm->snapshotVersion);
        $this->assertSame([], $vm->snapshotPayload);
        $this->assertNull($vm->history);

        // And the current-record read is denied on its own permission too.
        $this->assertNull(
            app(ConsultationMaternitySummaryPresentationService::class)
                ->currentRecord($this->route->fresh(), $denied)
        );
    }

    /** Check 31: with the snapshot flag off, nothing is captured at all. */
    public function test_flag_off_behaviour_remains_unchanged(): void
    {
        config(['consultation.maternity_context.completion_snapshot_enabled' => false]);
        $this->freeze();

        $this->complete();

        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
        // The occurrence ledger is lifecycle infrastructure, not a maternity
        // feature: it records the completion regardless of the maternity flag.
        $this->assertSame(1, ConsultationCompletionOccurrence::query()->count());
        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $this->route->fresh()->status);
    }

    /** Checks 32–33: reopen and completion readiness behaviour are unchanged. */
    public function test_reopen_and_completion_readiness_behaviour_are_unchanged(): void
    {
        $this->freeze();

        // Completion is not gated on maternity readiness — it stays advisory.
        $this->profile->forceFill(['gravida' => null])->save();
        $this->complete();

        $this->assertSame(VisitConsultationRoute::STATUS_COMPLETED, $this->route->fresh()->status);

        // A cancelled route still short-circuits without minting an occurrence.
        $cancelled = $this->consultationRoute();
        $cancelled->forceFill(['status' => VisitConsultationRoute::STATUS_CANCELLED])->save();
        app(ConsultationRouteService::class)->completeRoute($cancelled->fresh(), $this->user);

        $this->assertSame(
            0,
            ConsultationCompletionOccurrence::query()
                ->where('consultation_route_id', $cancelled->id)->count(),
            'A cancelled route must not produce a completion occurrence.'
        );
    }

    /** Checks 34–38: no billing, order-set, reconciliation or flag side effects. */
    public function test_completion_creates_no_billing_orderset_or_flag_side_effects(): void
    {
        $this->freeze();

        $flagsBefore = [
            config('consultation.maternity_context.summary_projection_enabled'),
            config('consultation.maternity_context.completion_snapshot_enabled'),
        ];

        $this->complete();
        $this->reopen();
        $this->complete();

        // 34/35. No invoice items, no maternity billing events.
        $this->assertSame(0, DB::table('invoice_items')->count());
        foreach (['maternity_billing_events', 'maternity_billing_event_logs'] as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->assertSame(0, DB::table($table)->count(), "{$table} must stay empty.");
            }
        }

        // 36. No order-set writes.
        foreach (['order_set_items', 'consultation_order_sets'] as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->assertSame(0, DB::table($table)->count(), "{$table} must stay untouched.");
            }
        }

        // 37. No environment reconciliation writes.
        if (\Illuminate\Support\Facades\Schema::hasTable('consultation_specialty_service_mappings')) {
            $this->assertSame(
                0,
                DB::table('consultation_specialty_service_mappings')->count(),
                'Reconciliation must remain dry-run only.'
            );
        }

        // 38. No feature flag changed by the code path.
        $this->assertSame($flagsBefore, [
            config('consultation.maternity_context.summary_projection_enabled'),
            config('consultation.maternity_context.completion_snapshot_enabled'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Performance (checks 39–41)
    |--------------------------------------------------------------------------
    */

    /** Check 39: snapshot history does not add an N+1 as versions grow. */
    public function test_snapshot_history_does_not_add_an_n_plus_one_query(): void
    {
        $this->freeze();

        for ($i = 0; $i < 4; $i++) {
            if ($i > 0) {
                $this->reopen();
                $this->profile->forceFill(['gravida' => 2 + $i])->save();
            }
            $this->complete();
        }
        $this->assertSame(4, ConsultationMaternitySnapshot::query()->count());

        $service = app(ConsultationMaternitySnapshotService::class);
        $route = $this->route->fresh();

        DB::enableQueryLog();
        $history = $service->historyFor($route);
        $history->each(fn ($s) => $s->capturedBy?->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The history query plus one eager-load for the capturing users —
        // constant regardless of how many versions exist.
        $this->assertLessThanOrEqual(
            2,
            $queries,
            "Snapshot history must stay constant-query; used {$queries} for 4 versions."
        );
        $this->assertCount(4, $history);
    }

    /** Check 40: preview does not build a second maternity projection. */
    public function test_preview_does_not_build_a_second_projection(): void
    {
        $this->freeze();
        $this->complete();

        $route = $this->route->fresh();

        $user = $this->summaryUser();
        $service = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, []);

        DB::enableQueryLog();
        $vm = $service->build($route, $user);
        $first = count(DB::getQueryLog());
        // The preview modal asks for the same view model again.
        $service->build($route, $user);
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Reading a completed snapshot serves the STORED payload; the second
        // build is memoised, so the preview adds no second projection.
        $this->assertSame(1, $vm->snapshotVersion);
        $this->assertSame(
            $first,
            $total,
            "The preview must reuse the built view model; it issued ".($total - $first)." extra queries."
        );
        $this->assertStringNotContainsString(
            'pregnancy_profiles',
            collect(DB::getQueryLog())->pluck('query')->implode(' '),
        );
    }

    /** Check 41: with the feature off, completion adds no maternity queries. */
    public function test_feature_off_completion_adds_no_maternity_queries(): void
    {
        config([
            'consultation.maternity_context.summary_projection_enabled' => false,
            'consultation.maternity_context.completion_snapshot_enabled' => false,
        ]);
        $this->freeze();

        $route = $this->consultationRoute();
        $service = app(ConsultationRouteService::class);

        DB::enableQueryLog();
        $service->completeRoute($route->fresh(), $this->user);
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $maternityQueries = collect($log)->filter(
            fn ($q) => str_contains($q['query'], 'maternity')
                || str_contains($q['query'], 'pregnancy')
        );

        $this->assertCount(
            0,
            $maternityQueries,
            'Feature-off completion must issue no maternity queries: '
                .$maternityQueries->pluck('query')->implode(' | ')
        );

        // The occurrence ledger costs exactly one read and one insert.
        $occurrenceQueries = collect($log)->filter(
            fn ($q) => str_contains($q['query'], 'consultation_completion_occurrences')
        );
        $this->assertLessThanOrEqual(2, $occurrenceQueries->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function freeze(): void
    {
        Carbon::setTestNow(Carbon::parse(self::FROZEN));
    }

    private function complete(): void
    {
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    /** An approved reopen: the route returns to ACTIVE and clears completion. */
    private function reopen(): void
    {
        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();
    }

    private function build(?int $version = null, bool $printMode = false)
    {
        return app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $this->summaryUser(), version: $version, printMode: $printMode);
    }

    /**
     * The maternity summary sits at the READ level: `consultations.view` plus
     * the maternity summary permissions, never a write permission.
     */
    private function summaryUser()
    {
        return $this->summaryUser ??= $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
            'maternity.pregnancy.view',
        ], baseline: ['consultations.view']);
    }
}
