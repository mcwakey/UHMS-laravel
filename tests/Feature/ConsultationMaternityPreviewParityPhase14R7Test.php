<?php

namespace Tests\Feature;

use App\Models\ConsultationMaternitySnapshot;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\InvoiceItem;
use App\Models\MaternityBillingEvent;
use App\Models\VisitConsultationRoute;
use App\Services\Consultation\Maternity\ConsultationMaternityLinkService;
use App\Services\ConsultationRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsMaternityHandoffFixtures;
use Tests\TestCase;

/**
 * Phase 14R.7 Gate 0 — Consultation summary PREVIEW parity.
 *
 * The preview modal must follow exactly the same live-vs-snapshot rule as the
 * summary tab, from the same presentation service. The browser only places a
 * server-rendered fragment; it never re-decides the mode.
 */
class ConsultationMaternityPreviewParityPhase14R7Test extends TestCase
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
        $this->mapObstetrics();
    }

    /* ── Live ──────────────────────────────────────────────────────────── */

    public function test_active_preview_contains_the_live_maternity_block(): void
    {
        $this->link();

        $json = $this->preview();

        $this->assertNotNull($json['maternity_context']);
        $this->assertSame('live', $json['maternity_context']['mode']);
        $this->assertStringContainsString(
            __('consultation_maternity_summary.summary.current_record'),
            $json['maternity_context']['html']
        );
    }

    public function test_preview_creates_no_snapshot_entry_or_billing_record(): void
    {
        $this->link();
        $entries = ConsultationSpecialtyEntry::query()->count();

        $this->preview();
        $this->preview();

        $this->assertSame(0, ConsultationMaternitySnapshot::query()->count());
        $this->assertSame($entries, ConsultationSpecialtyEntry::query()->count());
        $this->assertSame(0, InvoiceItem::query()->count());
        $this->assertSame(0, MaternityBillingEvent::query()->count());
    }

    /* ── Completed ─────────────────────────────────────────────────────── */

    public function test_completed_preview_contains_the_snapshot_block(): void
    {
        $this->link();
        $this->complete();

        $json = $this->preview();

        $this->assertSame('completion_snapshot', $json['maternity_context']['mode']);
        $this->assertSame(1, $json['maternity_context']['snapshot_version']);
        $this->assertStringContainsString(
            __('consultation_maternity_summary.summary.completion_snapshot_title'),
            $json['maternity_context']['html']
        );
    }

    public function test_changing_maternity_after_completion_does_not_change_previewed_html(): void
    {
        $this->link();
        $this->complete();

        $before = $this->preview()['maternity_context']['html'];

        $this->profile->forceFill(['gravida' => 9])->save();

        $after = $this->preview()['maternity_context']['html'];

        $this->assertSame($before, $after);
        $this->assertStringNotContainsString('>9<', $after);
    }

    public function test_completed_preview_without_a_snapshot_is_honest(): void
    {
        config(['consultation.maternity_context.completion_snapshot_enabled' => false]);
        $this->link();
        $this->complete();

        $json = $this->preview();

        $this->assertSame('no_snapshot', $json['maternity_context']['mode']);
        $this->assertStringContainsString(
            __('consultation_maternity_summary.summary.no_snapshot_available'),
            $json['maternity_context']['html']
        );
        $this->assertStringContainsString(
            __('consultation_maternity_summary.snapshot.none_fabricated'),
            $json['maternity_context']['html']
        );
    }

    public function test_reopened_preview_shows_live_values(): void
    {
        $this->link();
        $this->complete();
        $this->route->fresh()->forceFill([
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'completed_at' => null,
        ])->save();

        $json = $this->preview();

        $this->assertSame('live', $json['maternity_context']['mode']);
        $this->assertSame(1, ConsultationMaternitySnapshot::query()->count());
    }

    /* ── Flag off ──────────────────────────────────────────────────────── */

    public function test_flag_off_leaves_the_preview_payload_unchanged(): void
    {
        config(['consultation.maternity_context.summary_projection_enabled' => false]);
        $this->link();

        $json = $this->preview();

        // Null — not an empty container the browser would still reveal.
        $this->assertNull($json['maternity_context']);
        $this->assertArrayHasKey('summary', $json);
        $this->assertTrue($json['success']);
    }

    /* ── Fragment safety ───────────────────────────────────────────────── */

    public function test_the_fragment_carries_no_script_and_no_raw_json(): void
    {
        $this->link();
        $this->complete();

        $html = $this->preview()['maternity_context']['html'];

        // Compact mode: history/current-record (and their pushed scripts) stay
        // on the main summary page, so the fragment is inert.
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('{"schema_version"', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_preview_requires_summary_permission_for_the_maternity_block(): void
    {
        $this->link();

        $json = $this->preview($this->userWithPermissions([
            'consultations.view', 'consultations.create',
        ]));

        $this->assertNull($json['maternity_context']);
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    private function preview($user = null): array
    {
        $user ??= $this->previewUser();

        return $this->actingAs($user)
            ->getJson(route('admin.consultations.specialty-summary.preview', $this->visit)
                .'?consultation_route_id='.$this->route->id)
            ->assertOk()
            ->json();
    }

    private function mapObstetrics(): void
    {
        ConsultationSpecialtyProfileMapping::create([
            'consultation_specialty_profile_id' => ConsultationSpecialtyProfile::firstOrCreate(
                ['code' => 'obstetrics'],
                ['name' => 'Obstetrics', 'is_active' => true, 'sort_order' => 40]
            )->id,
            'department_id' => $this->department->id,
            'is_active' => true,
            'priority' => 10,
        ]);
    }

    private function link(): void
    {
        app(ConsultationMaternityLinkService::class)->link($this->route, $this->profile, $this->user);
    }

    private function complete(): void
    {
        app(ConsultationRouteService::class)->completeRoute($this->route->fresh(), $this->user);
    }

    private function previewUser()
    {
        return $this->userWithPermissions([
            'consultation.maternity_context.summary.view',
            'consultation.maternity_context.view',
            'maternity.pregnancy.view',
        ], baseline: ['consultations.view', 'consultations.create']);
    }
}
