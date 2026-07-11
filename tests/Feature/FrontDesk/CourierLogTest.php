<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskCourierLog;

class CourierLogTest extends FrontDeskTestCase
{
    public function test_can_create_incoming_courier(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.store'), [
            'direction' => 'incoming',
            'courier_type' => 'parcel',
            'sender_name' => 'DHL Ghana',
            'recipient_name' => 'Stores',
        ])->assertRedirect(route('admin.front-desk.couriers.index'));

        $log = FrontDeskCourierLog::first();
        $this->assertSame('incoming', $log->direction->value);
        $this->assertSame('received', $log->status->value);
        $this->assertSame($this->user->id, $log->received_by);
    }

    public function test_can_create_outgoing_courier_stamps_sent_by(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.store'), [
            'direction' => 'outgoing',
            'courier_type' => 'document',
            'recipient_name' => 'NHIA Office',
            'status' => 'pending_dispatch',
        ])->assertRedirect();

        $log = FrontDeskCourierLog::first();
        $this->assertSame('outgoing', $log->direction->value);
        $this->assertSame($this->user->id, $log->sent_by);
        $this->assertNull($log->received_by);
    }

    public function test_can_list_search_and_filter_couriers(): void
    {
        $this->makeCourier(['sender_name' => 'Unique Sender', 'courier_type' => 'letter']);
        $this->makeCourier(['sender_name' => 'Another Sender', 'courier_type' => 'parcel', 'direction' => 'outgoing']);

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.couriers.index', ['search' => 'Unique']))
            ->assertOk()->assertSee('Unique Sender')->assertDontSee('Another Sender');

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.couriers.index', ['courier_type' => 'parcel']))
            ->assertOk()->assertSee('Another Sender');
    }

    public function test_can_mark_delivered(): void
    {
        $log = $this->makeCourier(['status' => 'dispatched']);

        $this->actingAs($this->user)
            ->post(route('admin.front-desk.couriers.mark-delivered', $log))
            ->assertRedirect();

        $log->refresh();
        $this->assertSame('delivered', $log->status->value);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_cannot_mark_delivered_twice(): void
    {
        $log = $this->makeCourier(['status' => 'delivered', 'delivered_at' => now()->subHour()]);

        $this->actingAs($this->user)
            ->post(route('admin.front-desk.couriers.mark-delivered', $log))
            ->assertSessionHasErrors('status');
    }

    public function test_pending_courier_count(): void
    {
        $this->makeCourier(['status' => 'received']);
        $this->makeCourier(['status' => 'dispatched']);
        $this->makeCourier(['status' => 'delivered', 'delivered_at' => now()]);

        $this->assertSame(2, FrontDeskCourierLog::query()->pendingCourier()->count());
    }

    public function test_audit_log_is_written(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.store'), [
            'direction' => 'incoming',
            'courier_type' => 'report',
        ]);

        $created = ActivityLog::where('event', 'FRONT_DESK_COURIER_CREATED')->first();
        $this->assertNotNull($created);
        $this->assertSame('FRONT_DESK', $created->log_name);

        $log = FrontDeskCourierLog::first();
        $this->actingAs($this->user)->post(route('admin.front-desk.couriers.mark-delivered', $log));
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_COURIER_DELIVERED')->exists());
    }

    private function makeCourier(array $overrides = []): FrontDeskCourierLog
    {
        return FrontDeskCourierLog::create(array_merge([
            'direction' => 'incoming',
            'courier_type' => 'letter',
            'status' => 'received',
            'received_or_sent_at' => now(),
            'received_by' => $this->user->id,
        ], $overrides));
    }
}
