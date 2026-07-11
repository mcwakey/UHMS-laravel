<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskLostFoundItem;
use App\Services\FrontDesk\LostFoundService;

class LostFoundTest extends FrontDeskTestCase
{
    public function test_authorized_user_can_view_lost_found(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.lost-found.index'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_lost_found(): void
    {
        $stranger = $this->userWith(['front_desk.view']);
        $this->actingAs($stranger)->get(route('admin.front-desk.lost-found.index'))->assertForbidden();
    }

    public function test_can_create_found_item_with_reference_number(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.store'), [
            'item_category' => 'phone', 'item_description' => 'Black smartphone', 'found_location' => 'Lobby',
        ])->assertRedirect();

        $item = FrontDeskLostFoundItem::first();
        $this->assertMatchesRegularExpression('/^LF-\d{8}-\d{4}$/', $item->reference_number);
        $this->assertSame('found', $item->item_status->value);
        $this->assertSame($this->user->id, $item->created_by);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_LOST_FOUND_CREATED')->exists());
    }

    public function test_can_report_lost_item(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.store'), [
            'item_status' => 'reported_lost', 'item_category' => 'wallet', 'item_description' => 'Brown wallet',
            'reported_by_name' => 'Ama', 'reported_by_phone' => '0244000000',
        ])->assertRedirect();

        $this->assertDatabaseHas('front_desk_lost_found_items', ['item_status' => 'reported_lost', 'item_category' => 'wallet']);
    }

    public function test_phone_masking(): void
    {
        $item = $this->makeItem(['reported_by_phone' => '0244123456']);
        $this->assertSame('024*****56', $item->maskedReportedPhone());

        $this->actingAs($this->user)->get(route('admin.front-desk.lost-found.show', $item))
            ->assertOk()->assertDontSee('0244123456');
    }

    public function test_can_mark_claimed_and_release(): void
    {
        $item = $this->makeItem();

        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.claim', $item), [
            'claimed_by_name' => 'Kofi', 'claimed_by_phone' => '0201112222',
        ])->assertRedirect();
        $item->refresh();
        $this->assertSame('claimed', $item->item_status->value);
        $this->assertSame($this->user->id, $item->claim_verified_by);

        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.release', $item))->assertRedirect();
        $item->refresh();
        $this->assertSame('released', $item->item_status->value);
        $this->assertNotNull($item->released_at);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_LOST_FOUND_RELEASED')->exists());
    }

    public function test_cannot_release_twice_or_release_cancelled(): void
    {
        $released = $this->makeItem(['item_status' => 'released', 'released_at' => now()]);
        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.release', $released))->assertSessionHasErrors('status');

        $cancelled = $this->makeItem(['item_status' => 'cancelled']);
        $this->actingAs($this->user)->post(route('admin.front-desk.lost-found.release', $cancelled))->assertSessionHasErrors('status');
    }

    public function test_dashboard_unclaimed_count(): void
    {
        $this->makeItem(['item_status' => 'found']);
        $this->makeItem(['item_status' => 'reported_lost']);
        $this->makeItem(['item_status' => 'released', 'released_at' => now()]);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics($this->user);
        $this->assertSame(2, $metrics['unclaimed_lost_found_count']);
    }

    private function makeItem(array $overrides = []): FrontDeskLostFoundItem
    {
        return app(LostFoundService::class)->create(array_merge([
            'item_category' => 'phone', 'item_description' => 'Item',
        ], $overrides), $this->user);
    }
}
