<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskCallLog;

class CallLogTest extends FrontDeskTestCase
{
    public function test_can_create_incoming_call(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.calls.store'), [
            'direction' => 'incoming',
            'caller_name' => 'Anonymous Caller',
            'phone_number' => '0244000000',
            'category' => 'appointment_enquiry',
            'outcome' => 'answered',
        ])->assertRedirect(route('admin.front-desk.calls.index'));

        $log = FrontDeskCallLog::first();
        $this->assertSame('incoming', $log->direction->value);
        $this->assertSame($this->user->id, $log->handled_by);
        $this->assertNotNull($log->started_at);
    }

    public function test_can_create_outgoing_call(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.calls.store'), [
            'direction' => 'outgoing',
            'recipient_name' => 'Supplier Rep',
            'category' => 'supplier_vendor',
            'outcome' => 'resolved',
        ])->assertRedirect();

        $this->assertDatabaseHas('front_desk_call_logs', [
            'direction' => 'outgoing',
            'category' => 'supplier_vendor',
        ]);
    }

    public function test_can_flag_follow_up_required_and_defaults_pending(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.calls.store'), [
            'direction' => 'incoming',
            'category' => 'complaint',
            'outcome' => 'callback_required',
            'follow_up_required' => '1',
        ])->assertRedirect();

        $log = FrontDeskCallLog::first();
        $this->assertTrue($log->follow_up_required);
        $this->assertSame('pending', $log->follow_up_status);
        $this->assertTrue($log->hasPendingFollowUp());
    }

    public function test_can_list_search_and_filter_calls(): void
    {
        $this->makeCall(['caller_name' => 'Zebra Caller', 'direction' => 'incoming']);
        $this->makeCall(['caller_name' => 'Yak Caller', 'direction' => 'outgoing']);

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.calls.index', ['search' => 'Zebra']))
            ->assertOk()->assertSee('Incoming')->assertDontSee('Yak Caller');

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.calls.index', ['direction' => 'outgoing']))
            ->assertOk();
    }

    public function test_pending_follow_up_count_and_mark_completed(): void
    {
        $log = $this->makeCall(['follow_up_required' => true, 'follow_up_status' => 'pending']);
        $this->makeCall(['follow_up_required' => false]);

        $this->assertSame(1, FrontDeskCallLog::query()->pendingFollowUp()->count());

        $this->actingAs($this->user)
            ->post(route('admin.front-desk.calls.follow-up-complete', $log))
            ->assertRedirect();

        $this->assertSame('completed', $log->fresh()->follow_up_status);
        $this->assertSame(0, FrontDeskCallLog::query()->pendingFollowUp()->count());
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_CALL_FOLLOWUP_COMPLETED')->exists());
    }

    public function test_audit_log_is_written(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.calls.store'), [
            'direction' => 'incoming',
            'category' => 'general_enquiry',
            'outcome' => 'answered',
        ]);

        $created = ActivityLog::where('event', 'FRONT_DESK_CALL_CREATED')->first();
        $this->assertNotNull($created);
        $this->assertSame('FRONT_DESK', $created->log_name);
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
