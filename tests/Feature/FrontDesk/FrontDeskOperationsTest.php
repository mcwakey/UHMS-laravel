<?php

namespace Tests\Feature\FrontDesk;

use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskCourierLog;
use App\Models\FrontDeskVisitorLog;

/**
 * Cross-cutting Front Desk (Phase 18A) coverage: permission enforcement,
 * dashboard aggregation, page rendering and EN/FR localisation.
 */
class FrontDeskOperationsTest extends FrontDeskTestCase
{
    /* ── Permissions ─────────────────────────────────────────────── */

    public function test_unauthorized_user_cannot_access_dashboard(): void
    {
        $stranger = $this->userWith([]); // no front desk permissions

        $this->actingAs($stranger)->get(route('admin.front-desk.index'))->assertForbidden();
    }

    public function test_authorized_user_can_access_dashboard(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.index'))->assertOk();
    }

    public function test_visitor_permission_does_not_grant_call_or_courier_access(): void
    {
        $visitorOnly = $this->userWith(['front_desk.view', 'front_desk.visitors.view']);

        $this->actingAs($visitorOnly)->get(route('admin.front-desk.visitors.index'))->assertOk();
        $this->actingAs($visitorOnly)->get(route('admin.front-desk.calls.index'))->assertForbidden();
        $this->actingAs($visitorOnly)->get(route('admin.front-desk.couriers.index'))->assertForbidden();
    }

    public function test_create_permission_is_enforced_separately_from_view(): void
    {
        $viewOnly = $this->userWith(['front_desk.view', 'front_desk.calls.view']);

        $this->actingAs($viewOnly)->get(route('admin.front-desk.calls.index'))->assertOk();
        $this->actingAs($viewOnly)->get(route('admin.front-desk.calls.create'))->assertForbidden();
    }

    /* ── Dashboard ───────────────────────────────────────────────── */

    public function test_dashboard_cards_show_correct_aggregate_counts(): void
    {
        // Two visitors inside, one of them overdue (5h > 4h threshold).
        FrontDeskVisitorLog::create([
            'visitor_context' => 'facility', 'visitor_name' => 'Inside Now',
            'time_in' => now()->subHour(), 'status' => 'checked_in', 'checked_in_by' => $this->user->id,
        ]);
        FrontDeskVisitorLog::create([
            'visitor_context' => 'facility', 'visitor_name' => 'Overdue One',
            'time_in' => now()->subHours(5), 'status' => 'checked_in', 'checked_in_by' => $this->user->id,
        ]);
        // A checked-out visitor today — counts for "today" but not "inside".
        FrontDeskVisitorLog::create([
            'visitor_context' => 'facility', 'visitor_name' => 'Left Already',
            'time_in' => now()->subHours(2), 'time_out' => now(), 'status' => 'checked_out', 'checked_in_by' => $this->user->id,
        ]);

        FrontDeskCallLog::create([
            'direction' => 'incoming', 'category' => 'general_enquiry', 'outcome' => 'callback_required',
            'handled_by' => $this->user->id, 'started_at' => now(), 'follow_up_required' => true, 'follow_up_status' => 'pending',
        ]);

        FrontDeskCourierLog::create([
            'direction' => 'incoming', 'courier_type' => 'parcel', 'status' => 'received',
            'received_or_sent_at' => now(), 'received_by' => $this->user->id,
        ]);
        FrontDeskCourierLog::create([
            'direction' => 'outgoing', 'courier_type' => 'letter', 'status' => 'delivered',
            'received_or_sent_at' => now(), 'delivered_at' => now(), 'sent_by' => $this->user->id,
        ]);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics();

        $this->assertSame(2, $metrics['visitors_inside_count']);
        $this->assertSame(3, $metrics['visitors_today_count']);
        $this->assertSame(1, $metrics['overdue_visitors_count']);
        $this->assertSame(1, $metrics['calls_today_count']);
        $this->assertSame(1, $metrics['pending_call_followups_count']);
        $this->assertSame(2, $metrics['couriers_today_count']);
        $this->assertSame(1, $metrics['pending_couriers_count']);
        $this->assertSame(1, $metrics['delivered_couriers_today']);

        $response = $this->actingAs($this->user)->get(route('admin.front-desk.index'));
        $response->assertOk()->assertSee('Inside Now');
    }

    public function test_dashboard_empty_state_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.front-desk.index'))
            ->assertOk()
            ->assertSee(__('front_desk.dashboard.no_recent_visitors'));
    }

    /* ── Views / localisation ────────────────────────────────────── */

    public function test_main_index_pages_render(): void
    {
        foreach ([
            'admin.front-desk.index',
            'admin.front-desk.visitors.index',
            'admin.front-desk.calls.index',
            'admin.front-desk.couriers.index',
            'admin.front-desk.visitors.create',
            'admin.front-desk.calls.create',
            'admin.front-desk.couriers.create',
        ] as $route) {
            $this->actingAs($this->user)->get(route($route))->assertOk();
        }
    }

    public function test_view_cache_compiles_front_desk_views(): void
    {
        $this->artisan('view:clear')->assertExitCode(0);
        $this->artisan('view:cache')->assertExitCode(0);
    }

    public function test_en_and_fr_keys_exist(): void
    {
        foreach (['en', 'fr'] as $locale) {
            app()->setLocale($locale);
            $this->assertNotSame('front_desk.title', __('front_desk.title'));
            $this->assertNotSame('front_desk.visitor_status.checked_in', __('front_desk.visitor_status.checked_in'));
            $this->assertNotSame('front_desk.courier_status.delivered', __('front_desk.courier_status.delivered'));
            $this->assertNotSame('front_desk.flash.visitor_created', __('front_desk.flash.visitor_created'));
        }
        app()->setLocale('en');
    }
}
