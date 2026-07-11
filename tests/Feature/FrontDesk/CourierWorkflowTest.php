<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskCourierHandoff;
use App\Models\FrontDeskCourierLog;

class CourierWorkflowTest extends FrontDeskTestCase
{
    /* ── Access ──────────────────────────────────────────────────── */

    public function test_authorized_user_can_view_courier_workflow(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.couriers.workflow'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_courier_workflow(): void
    {
        $viewer = $this->userWith(['front_desk.view', 'front_desk.couriers.view']); // no workflow.view

        $this->actingAs($viewer)->get(route('admin.front-desk.couriers.workflow'))->assertForbidden();
    }

    /* ── Dispatch ────────────────────────────────────────────────── */

    public function test_can_dispatch_courier(): void
    {
        $log = $this->makeCourier(['status' => 'received']);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.dispatch', $log))->assertRedirect();

        $log->refresh();
        $this->assertSame('dispatched', $log->status->value);
        $this->assertSame('in_transit', $log->handover_status->value);
        $this->assertNotNull($log->dispatched_at);
        $this->assertSame($this->user->id, $log->dispatched_by);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_DISPATCHED')->exists());
    }

    public function test_cannot_dispatch_delivered_courier(): void
    {
        $log = $this->makeCourier(['status' => 'delivered', 'delivered_at' => now()]);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.dispatch', $log))
            ->assertSessionHasErrors('status');
    }

    /* ── Handover + timeline ─────────────────────────────────────── */

    public function test_can_hand_over_courier_and_timeline_records_event(): void
    {
        $log = $this->makeCourier(['status' => 'received']);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.handover', $log), [
            'to_user_id' => $this->user->id,
            'note' => 'Handed to records',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame('handed_over', $log->handover_status->value);
        $this->assertTrue(
            FrontDeskCourierHandoff::where('courier_log_id', $log->id)->where('action', 'handed_over')->exists()
        );
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_HANDED_OVER')->exists());
    }

    public function test_create_records_received_handoff(): void
    {
        $log = app(\App\Services\FrontDesk\CourierLogService::class)->create([
            'direction' => 'incoming', 'courier_type' => 'parcel',
        ], $this->user);

        $this->assertSame('awaiting_handover', $log->handover_status->value);
        $this->assertTrue(FrontDeskCourierHandoff::where('courier_log_id', $log->id)->where('action', 'received')->exists());
    }

    /* ── Delivery with proof ─────────────────────────────────────── */

    public function test_can_mark_courier_delivered_with_proof_reference(): void
    {
        $log = $this->makeCourier(['status' => 'dispatched', 'handover_status' => 'in_transit']);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.mark-delivered', $log), [
            'proof_reference' => 'POD-12345',
            'delivery_note' => 'Signed by ward clerk',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame('delivered', $log->status->value);
        $this->assertSame('delivered', $log->handover_status->value);
        $this->assertSame('POD-12345', $log->proof_reference);
        $this->assertSame('Signed by ward clerk', $log->deliveryNote());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_DELIVERED')->exists());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_DELIVERY_PROOF_RECORDED')->exists());
    }

    public function test_cannot_deliver_twice(): void
    {
        $log = $this->makeCourier(['status' => 'delivered', 'delivered_at' => now()]);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.mark-delivered', $log))
            ->assertSessionHasErrors('status');
    }

    /* ── Return ──────────────────────────────────────────────────── */

    public function test_can_mark_courier_returned(): void
    {
        $log = $this->makeCourier(['status' => 'dispatched', 'handover_status' => 'in_transit']);

        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.mark-returned', $log), [
            'reason' => 'Recipient unavailable',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame('returned', $log->status->value);
        $this->assertSame('returned', $log->handover_status->value);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_RETURNED')->exists());
    }

    /* ── Filters + dashboard ─────────────────────────────────────── */

    public function test_workflow_filters(): void
    {
        $this->makeCourier(['status' => 'received', 'recipient_name' => 'Awaiting One']);
        $this->makeCourier(['status' => 'dispatched', 'handover_status' => 'in_transit', 'recipient_name' => 'Transit One', 'dispatched_at' => now()]);

        $this->assertSame(1, FrontDeskCourierLog::query()->pendingDispatch()->count());
        $this->assertSame(1, FrontDeskCourierLog::query()->inTransit()->count());

        $this->actingAs($this->user)->get(route('admin.front-desk.couriers.workflow', ['quick' => 'in_transit']))
            ->assertOk()->assertSee('Transit One');
    }

    public function test_dashboard_courier_workflow_counts(): void
    {
        $this->makeCourier(['status' => 'received']);
        $this->makeCourier(['status' => 'dispatched', 'handover_status' => 'in_transit', 'dispatched_at' => now()->subHours(30)]); // overdue
        $this->makeCourier(['status' => 'received', 'handover_status' => 'awaiting_handover']);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics($this->user);

        $this->assertSame(2, $metrics['pending_dispatch_count']);
        $this->assertSame(1, $metrics['in_transit_couriers_count']);
        $this->assertSame(1, $metrics['overdue_couriers_count']);
    }

    public function test_audit_logs_are_written(): void
    {
        $log = $this->makeCourier(['status' => 'received']);
        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.dispatch', $log));

        $event = ActivityLog::where('event', 'FRONT_DESK_COURIER_DISPATCHED')->first();
        $this->assertNotNull($event);
        $this->assertSame('FRONT_DESK', $event->log_name);
    }

    private function makeCourier(array $overrides = []): FrontDeskCourierLog
    {
        return FrontDeskCourierLog::create(array_merge([
            'direction' => 'incoming',
            'courier_type' => 'letter',
            'status' => 'received',
            'handover_status' => 'awaiting_handover',
            'received_or_sent_at' => now(),
            'received_by' => $this->user->id,
        ], $overrides));
    }
}
