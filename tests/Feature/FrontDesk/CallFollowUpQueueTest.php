<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\FrontDeskCallLog;
use App\Services\FrontDesk\CallLogService;

class CallFollowUpQueueTest extends FrontDeskTestCase
{
    /* ── Access ──────────────────────────────────────────────────── */

    public function test_authorized_user_can_view_callback_queue(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.calls.follow-ups'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_callback_queue(): void
    {
        $viewer = $this->userWith(['front_desk.view', 'front_desk.calls.view']); // no followups.view

        $this->actingAs($viewer)->get(route('admin.front-desk.calls.follow-ups'))->assertForbidden();
    }

    /* ── Assign / complete / cancel ──────────────────────────────── */

    public function test_can_assign_follow_up_with_due_time_and_user(): void
    {
        $call = $this->makeCall(['follow_up_required' => false, 'follow_up_status' => null]);

        $this->actingAs($this->user)->post(route('admin.front-desk.calls.assign-follow-up', $call), [
            'assigned_follow_up_user_id' => $this->user->id,
            'follow_up_due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'follow_up_note' => 'Call back re: appointment',
        ])->assertRedirect();

        $call->refresh();
        $this->assertTrue($call->follow_up_required);
        $this->assertSame('pending', $call->follow_up_status);
        $this->assertSame($this->user->id, $call->assigned_follow_up_user_id);
        $this->assertNotNull($call->follow_up_due_at);
        $this->assertSame('Call back re: appointment', $call->followUpNote());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_CALL_FOLLOWUP_ASSIGNED')->exists());
    }

    public function test_can_complete_follow_up_with_note(): void
    {
        $call = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $this->actingAs($this->user)->post(route('admin.front-desk.calls.complete-follow-up', $call), [
            'completion_note' => 'Resolved on callback',
        ])->assertRedirect();

        $call->refresh();
        $this->assertSame('completed', $call->follow_up_status);
        $this->assertNotNull($call->follow_up_completed_at);
        $this->assertSame($this->user->id, $call->follow_up_completed_by);
        $this->assertSame('Resolved on callback', $call->completionNote());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_CALL_FOLLOWUP_COMPLETED')->exists());
    }

    public function test_can_cancel_follow_up_with_reason(): void
    {
        $call = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $this->actingAs($this->user)->post(route('admin.front-desk.calls.cancel-follow-up', $call), [
            'cancellation_reason' => 'Duplicate call',
        ])->assertRedirect();

        $call->refresh();
        $this->assertSame('cancelled', $call->follow_up_status);
        $this->assertNotNull($call->follow_up_cancelled_at);
        $this->assertSame('Duplicate call', $call->cancellationReason());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_CALL_FOLLOWUP_CANCELLED')->exists());
    }

    public function test_cannot_complete_follow_up_when_not_required(): void
    {
        $call = $this->makeCall(['follow_up_required' => false, 'follow_up_status' => null]);

        $this->actingAs($this->user)->post(route('admin.front-desk.calls.complete-follow-up', $call))
            ->assertSessionHasErrors('follow_up');

        $this->assertNull($call->fresh()->follow_up_completed_at);
    }

    /* ── Filters ─────────────────────────────────────────────────── */

    public function test_overdue_and_due_today_filters(): void
    {
        $overdue = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'follow_up_due_at' => now()->subHours(2), 'caller_name' => 'Overdue Caller']);
        $today = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'follow_up_due_at' => now()->addHours(2), 'caller_name' => 'Today Caller']);

        $this->assertSame(1, FrontDeskCallLog::query()->overdueCallback()->count());
        $this->assertSame(2, FrontDeskCallLog::query()->dueTodayCallback()->count());

        $this->actingAs($this->user)->get(route('admin.front-desk.calls.follow-ups', ['overdue' => 1]))->assertOk();
        $this->actingAs($this->user)->get(route('admin.front-desk.calls.follow-ups', ['due_today' => 1]))->assertOk();
    }

    public function test_assigned_to_me_filter(): void
    {
        $mine = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'assigned_follow_up_user_id' => $this->user->id, 'caller_name' => 'Mine Caller']);
        $other = $this->userWith(['front_desk.view']);
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'assigned_follow_up_user_id' => $other->id, 'caller_name' => 'Other Caller']);

        $this->assertSame(1, FrontDeskCallLog::query()->pendingCallback()->assignedTo($this->user->id)->count());
        $this->actingAs($this->user)->get(route('admin.front-desk.calls.follow-ups', ['mine' => 1]))->assertOk();
    }

    /* ── Transfer ────────────────────────────────────────────────── */

    public function test_call_transfer_records_department_and_user(): void
    {
        $call = $this->makeCall();
        $dept = Department::factory()->create();

        $this->actingAs($this->user)->post(route('admin.front-desk.calls.transfer', $call), [
            'transfer_department_id' => $dept->id,
            'transferred_to_user_id' => $this->user->id,
        ])->assertRedirect();

        $call->refresh();
        $this->assertSame($dept->id, $call->transfer_department_id);
        $this->assertSame($this->user->id, $call->transferred_to_user_id);
        $this->assertTrue($call->isTransferred());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_CALL_TRANSFERRED')->exists());
    }

    /* ── Dashboard ───────────────────────────────────────────────── */

    public function test_dashboard_callback_counts(): void
    {
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'follow_up_due_at' => now()->subHours(2), 'assigned_follow_up_user_id' => $this->user->id]);
        $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending', 'follow_up_due_at' => now()->addHours(1)]);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics($this->user);

        $this->assertSame(2, $metrics['pending_callbacks_count']);
        $this->assertSame(1, $metrics['overdue_callbacks_count']);
        $this->assertSame(2, $metrics['callbacks_due_today_count']);
        $this->assertSame(1, $metrics['assigned_to_me_callbacks_count']);
    }

    private function makeCall(array $overrides = []): FrontDeskCallLog
    {
        return FrontDeskCallLog::create(array_merge([
            'direction' => 'incoming',
            'category' => 'general_enquiry',
            'outcome' => 'answered',
            'handled_by' => $this->user->id,
            'started_at' => now(),
        ], $overrides));
    }
}
