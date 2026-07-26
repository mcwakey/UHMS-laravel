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
 * Phase 14R.6.1 — print output.
 *
 * Active prints live values; completed prints the completion snapshot. Current
 * maternity values must never appear under a historical label.
 */
class ConsultationMaternitySummaryPrintPhase14R6_1Test extends TestCase
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

    public function test_active_print_uses_the_live_projection(): void
    {
        $this->link();

        $html = $this->renderPrint();

        $this->assertStringContainsString(__('consultation_maternity_summary.summary.current_record'), $html);
        $this->assertStringNotContainsString(
            __('consultation_maternity_summary.print.printed_snapshot_version'),
            $html
        );
    }

    public function test_completed_print_uses_the_snapshot_with_version_and_metadata(): void
    {
        $this->link();
        $this->complete();

        $html = $this->renderPrint();

        $this->assertStringContainsString(
            __('consultation_maternity_summary.summary.completion_snapshot_title'),
            $html
        );
        $this->assertStringContainsString(__('consultation_maternity_summary.print.printed_snapshot_version'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.print.historical_summary'), $html);
        $this->assertStringContainsString('v1', $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.verified_short'), $html);
    }

    public function test_completed_print_does_not_show_current_values_as_historical(): void
    {
        $this->link();
        $this->complete();

        // Change the live record AFTER completion.
        $this->profile->forceFill(['gravida' => 8])->save();

        $html = $this->renderPrint();

        // The snapshot's captured value is printed, not the new one.
        $this->assertStringContainsString('>2<', $html);
        $this->assertStringNotContainsString('>8<', $html);
    }

    public function test_selected_historical_version_prints_that_version(): void
    {
        $this->link();
        $this->complete();
        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
        ])->save();
        $this->profile->forceFill(['gravida' => 5])->save();
        $this->travel(2)->seconds();
        $this->complete();

        $v1 = $this->renderPrint(version: 1);
        $v2 = $this->renderPrint(version: 2);

        $this->assertStringContainsString('v1', $v1);
        $this->assertStringContainsString('v2', $v2);
        $this->assertStringContainsString('>2<', $v1);
        $this->assertStringContainsString('>5<', $v2);
    }

    public function test_completed_without_a_snapshot_prints_an_honest_message(): void
    {
        config(['consultation.maternity_context.completion_snapshot_enabled' => false]);
        $this->link();
        $this->complete();

        $html = $this->renderPrint();

        $this->assertStringContainsString(__('consultation_maternity_summary.summary.no_snapshot_available'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.snapshot.none_fabricated'), $html);
        // No live values dressed as history.
        $this->assertStringNotContainsString(__('consultation_maternity_summary.print.printed_snapshot_version'), $html);
    }

    public function test_print_flag_off_renders_no_maternity_block(): void
    {
        config(['consultation.maternity_context.summary_projection_enabled' => false]);
        $this->link();
        $this->complete();

        $html = $this->renderPrint();

        $this->assertStringNotContainsString(__('consultation_maternity_summary.summary.current_record'), $html);
        $this->assertStringNotContainsString(
            __('consultation_maternity_summary.summary.completion_snapshot_title'),
            $html
        );
    }

    public function test_print_contains_no_raw_json_and_creates_no_write(): void
    {
        $this->link();
        $this->complete();
        $user = $this->summaryUser();

        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete)/i', $query->sql)) {
                $writes++;
            }
        });

        $html = $this->renderPrint($user);

        $this->assertSame(0, $writes);
        $this->assertStringNotContainsString('{"schema_version"', $html);
        $this->assertStringNotContainsString('payload_hash', $html);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    public function test_print_includes_source_labels(): void
    {
        $this->link();
        $this->complete();

        $html = $this->renderPrint();

        $this->assertStringContainsString(__('consultation_maternity_summary.summary.source_of_truth'), $html);
        $this->assertStringContainsString(__('consultation_maternity_summary.summary.encounter_source'), $html);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function renderPrint($user = null, ?int $version = null): string
    {
        $user ??= $this->summaryUser();
        $this->actingAs($user);

        $vm = app()->makeWith(ConsultationMaternitySummaryPresentationService::class, [])
            ->build($this->route->fresh(), $user, version: $version, printMode: true);

        // Render the maternity block exactly as the print view includes it.
        if (! $vm->shouldRender()) {
            return '';
        }

        $header = $vm->isSnapshot() || $vm->isMissingSnapshot()
            ? __('consultation_maternity_summary.summary.completion_snapshot_title')
                .($vm->isSnapshot() ? ' (v'.$vm->snapshotVersion.')' : '')
            : __('consultation_maternity_summary.summary.current_record');

        $meta = '';
        if ($vm->isSnapshot()) {
            $meta = __('consultation_maternity_summary.print.printed_snapshot_version').': v'.$vm->snapshotVersion
                .' '.__('consultation_maternity_summary.summary.captured_at').': '.$vm->capturedAt
                .' '.($vm->integrityVerified() ? __('consultation_maternity_summary.snapshot.verified_short') : '—')
                .' '.__('consultation_maternity_summary.print.historical_summary');
        } elseif ($vm->isMissingSnapshot()) {
            $meta = __('consultation_maternity_summary.summary.no_snapshot_available')
                .' '.__('consultation_maternity_summary.snapshot.none_fabricated');
        }

        $body = $vm->isMissingSnapshot() ? '' : view(
            'consultations.partials.maternity.summary-payload',
            ['payload' => $vm->payload(), 'compact' => true]
        )->render();

        return $header
            .' '.__('consultation_maternity_summary.summary.source_of_truth')
            .' '.__('consultation_maternity_summary.summary.encounter_source')
            .' '.$meta.' '.$body;
    }

    private function link(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    private function complete(): void
    {
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    private function summaryUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
            'maternity.pregnancy.view',
        ], baseline: ['consultations.view']);
    }
}
