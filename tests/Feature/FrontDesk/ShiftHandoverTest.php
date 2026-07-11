<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskCallLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskShiftHandover;
use App\Models\FrontDeskVisitorLog;
use App\Services\FrontDesk\ShiftHandoverService;

class ShiftHandoverTest extends FrontDeskTestCase
{
    public function test_authorized_user_can_view_handovers(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.handovers.index'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_handovers(): void
    {
        $stranger = $this->userWith(['front_desk.view']);
        $this->actingAs($stranger)->get(route('admin.front-desk.handovers.index'))->assertForbidden();
    }

    public function test_can_create_draft_handover_with_snapshot(): void
    {
        FrontDeskVisitorLog::create(['visitor_context' => 'facility', 'visitor_name' => 'Inside', 'time_in' => now(), 'status' => 'checked_in', 'checked_in_by' => $this->user->id]);
        FrontDeskCallLog::create(['direction' => 'incoming', 'category' => 'general_enquiry', 'outcome' => 'callback_required', 'handled_by' => $this->user->id, 'started_at' => now(), 'follow_up_required' => true, 'follow_up_status' => 'pending']);

        $this->actingAs($this->user)->post(route('admin.front-desk.handovers.store'), [
            'shift_name' => 'Morning', 'summary_notes' => 'All quiet.',
        ])->assertRedirect();

        $handover = FrontDeskShiftHandover::first();
        $this->assertSame('draft', $handover->status->value);
        $this->assertSame($this->user->id, $handover->outgoing_user_id);
        $this->assertSame(1, $handover->visitors_inside_count);
        $this->assertSame(1, $handover->pending_callbacks_count);
        $this->assertIsArray($handover->open_items_snapshot);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_HANDOVER_CREATED')->exists());
    }

    public function test_can_submit_and_accept_handover(): void
    {
        $handover = app(ShiftHandoverService::class)->createDraft(['shift_name' => 'Night'], $this->user);

        $this->actingAs($this->user)->post(route('admin.front-desk.handovers.submit', $handover))->assertRedirect();
        $this->assertSame('submitted', $handover->fresh()->status->value);

        $incoming = $this->userWith(['front_desk.view', 'front_desk.handovers.view', 'front_desk.handovers.accept']);
        $this->actingAs($incoming)->post(route('admin.front-desk.handovers.accept', $handover))->assertRedirect();

        $handover->refresh();
        $this->assertSame('accepted', $handover->status->value);
        $this->assertSame($incoming->id, $handover->accepted_by);
        $this->assertNotNull($handover->accepted_at);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_HANDOVER_ACCEPTED')->exists());
    }

    public function test_cannot_accept_cancelled_handover(): void
    {
        $handover = app(ShiftHandoverService::class)->createDraft([], $this->user);
        app(ShiftHandoverService::class)->cancel($handover, $this->user, 'Not needed');

        $this->actingAs($this->user)->post(route('admin.front-desk.handovers.accept', $handover))
            ->assertSessionHasErrors('status');
    }

    public function test_dashboard_handover_counts(): void
    {
        $service = app(ShiftHandoverService::class);
        $draft = $service->createDraft([], $this->user);
        $submitted = $service->submit($service->createDraft([], $this->user), $this->user);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics($this->user);
        $this->assertSame(2, $metrics['pending_handovers_count']);
        $this->assertSame(1, $metrics['handovers_waiting_acceptance_count']);
    }
}
