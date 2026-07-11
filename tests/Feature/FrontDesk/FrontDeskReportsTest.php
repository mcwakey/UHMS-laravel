<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;
use App\Services\FrontDesk\FrontDeskReportService;

class FrontDeskReportsTest extends FrontDeskTestCase
{
    /* ── Permissions ─────────────────────────────────────────────── */

    public function test_unauthorized_user_cannot_view_reports(): void
    {
        $stranger = $this->userWith(['front_desk.view']); // no reports.view

        $this->actingAs($stranger)->get(route('admin.front-desk.reports.index'))->assertForbidden();
    }

    public function test_authorized_user_can_view_reports(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.reports.index'))->assertOk();
    }

    public function test_export_requires_export_permission(): void
    {
        $viewer = $this->userWith(['front_desk.view', 'front_desk.reports.view']); // no export

        $this->actingAs($viewer)->get(route('admin.front-desk.reports.export', ['type' => 'summary']))->assertForbidden();
    }

    /* ── Rendering ───────────────────────────────────────────────── */

    public function test_report_page_and_empty_state_render(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.reports.index'))
            ->assertOk()
            ->assertSee(__('front_desk.reports.title'))
            ->assertSee(__('front_desk.reports.cards.total_visitors'));
    }

    /* ── Visitor metrics ─────────────────────────────────────────── */

    public function test_visitor_summary_counts(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->makeVisitor(['patient_id' => $patient->id, 'time_in' => now()->subHour()]);
        $this->makeVisitor(['time_in' => now()->subHours(6)]); // facility, overdue, inside
        $this->makeVisitor(['status' => 'checked_out', 'time_out' => now()]);

        $summary = app(FrontDeskReportService::class)->summary([]);

        $this->assertSame(3, $summary['total_visitors']);
        $this->assertSame(1, $summary['patient_visitors']);
        $this->assertSame(2, $summary['facility_visitors']);
        $this->assertSame(2, $summary['currently_inside']);
        $this->assertSame(1, $summary['overdue_visitors']);
    }

    public function test_visitor_date_filter(): void
    {
        $this->makeVisitor(['time_in' => now()->subDays(2), 'visitor_name' => 'Recent']);
        $this->makeVisitor(['time_in' => now()->subDays(60), 'visitor_name' => 'Old']);

        $service = app(FrontDeskReportService::class);
        $this->assertSame(1, $service->summary(['date_from' => now()->subDays(7)->toDateString()])['total_visitors']);
        $this->assertSame(2, $service->summary(['date_from' => now()->subDays(90)->toDateString()])['total_visitors']);
    }

    public function test_visitor_context_and_ward_metrics(): void
    {
        $this->makeVisitor(['visitor_context' => 'patient', 'ward_id' => null, 'patient_id' => Patient::factory()->create(['registered_by' => $this->user->id])->id]);
        $this->makeVisitor(['visitor_context' => 'facility']);

        $metrics = app(FrontDeskReportService::class)->visitorMetrics([]);
        $contexts = collect($metrics['visitors_by_context'])->pluck('count', 'label');
        $this->assertNotEmpty($metrics['visitors_by_context']);
        $this->assertIsArray($metrics['visitors_by_ward']);
    }

    /* ── Call & callback metrics ─────────────────────────────────── */

    public function test_call_metrics_by_direction_category_outcome(): void
    {
        $this->makeCall(['direction' => 'incoming', 'category' => 'complaint', 'outcome' => 'resolved']);
        $this->makeCall(['direction' => 'outgoing', 'category' => 'supplier_vendor', 'outcome' => 'answered']);

        $metrics = app(FrontDeskReportService::class)->callMetrics([]);
        $this->assertCount(2, $metrics['calls_by_direction']);
        $this->assertNotEmpty($metrics['calls_by_category']);
        $this->assertNotEmpty($metrics['daily_call_trend']);
    }

    public function test_callback_metrics(): void
    {
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'follow_up_due_at' => now()->subHours(2), 'assigned_follow_up_user_id' => $this->user->id]);
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'completed']);

        $metrics = app(FrontDeskReportService::class)->callbackMetrics([]);
        $this->assertSame(1, $metrics['pending_callbacks']);
        $this->assertSame(1, $metrics['overdue_callbacks']);
        $this->assertSame(50.0, $metrics['callback_completion_rate']);
    }

    /* ── Courier metrics ─────────────────────────────────────────── */

    public function test_courier_metrics(): void
    {
        $this->makeCourier(['direction' => 'incoming', 'courier_type' => 'parcel', 'status' => 'received']);
        $this->makeCourier(['direction' => 'outgoing', 'courier_type' => 'letter', 'status' => 'dispatched', 'handover_status' => 'in_transit', 'dispatched_at' => now()]);

        $metrics = app(FrontDeskReportService::class)->courierMetrics([]);
        $this->assertCount(2, $metrics['couriers_by_direction']);
        $this->assertSame(1, $metrics['in_transit']);
        $this->assertNotEmpty($metrics['couriers_by_handover_status']);
    }

    /* ── Exports ─────────────────────────────────────────────────── */

    public function test_visitor_csv_export_works_and_masks_phone_and_excludes_clinical(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->makeVisitor(['patient_id' => $patient->id, 'visitor_phone' => '0244123456', 'visitor_name' => 'Exported Visitor']);

        $response = $this->actingAs($this->user)->get(route('admin.front-desk.reports.export', ['type' => 'visitors']));
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Exported Visitor', $csv);
        $this->assertStringContainsString('024*****56', $csv);   // masked phone (first 3 + last 2)
        $this->assertStringNotContainsString('0244123456', $csv); // raw phone absent
        $this->assertStringNotContainsString('diagnosis', strtolower($csv));
    }

    public function test_call_callback_courier_and_workflow_exports_work(): void
    {
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending']);
        $this->makeCourier(['status' => 'received']);

        foreach (['calls', 'callbacks', 'couriers', 'courier_workflow', 'summary', 'handovers', 'lost_found', 'incidents'] as $type) {
            $this->actingAs($this->user)->get(route('admin.front-desk.reports.export', ['type' => $type]))->assertOk();
        }
    }

    public function test_export_is_audited(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.reports.export', ['type' => 'summary']))->assertOk();

        $event = ActivityLog::where('event', 'FRONT_DESK_REPORT_EXPORTED')->first();
        $this->assertNotNull($event);
        $this->assertSame('FRONT_DESK', $event->log_name);
        $this->assertSame('summary', $event->properties['metadata']['export_type']);
    }

    public function test_invalid_export_type_is_rejected(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.reports.export', ['type' => 'clinical_dump']))->assertNotFound();
    }

    /* ── Localisation ────────────────────────────────────────────── */

    public function test_en_fr_report_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $this->assertNotSame('front_desk.reports.title', __('front_desk.reports.title'));
            $this->assertNotSame('front_desk.reports.tabs.couriers', __('front_desk.reports.tabs.couriers'));
            $this->assertNotSame('front_desk.reports.cards.total_visitors', __('front_desk.reports.cards.total_visitors'));
        }
        app()->setLocale('en');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function makeVisitor(array $overrides = []): FrontDeskVisitorLog
    {
        return FrontDeskVisitorLog::create(array_merge([
            'visitor_context' => 'facility', 'visitor_name' => 'Test Visitor',
            'time_in' => now(), 'status' => 'checked_in', 'checked_in_by' => $this->user->id,
        ], $overrides));
    }

    private function makeCall(array $overrides = []): FrontDeskCallLog
    {
        return FrontDeskCallLog::create(array_merge([
            'direction' => 'incoming', 'category' => 'general_enquiry', 'outcome' => 'answered',
            'handled_by' => $this->user->id, 'started_at' => now(),
        ], $overrides));
    }

    private function makeCourier(array $overrides = []): FrontDeskCourierLog
    {
        return FrontDeskCourierLog::create(array_merge([
            'direction' => 'incoming', 'courier_type' => 'letter', 'status' => 'received',
            'handover_status' => 'awaiting_handover', 'received_or_sent_at' => now(), 'received_by' => $this->user->id,
        ], $overrides));
    }
}
