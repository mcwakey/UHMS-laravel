<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskVisitorLog;
use App\Models\Patient;

class VisitorLogTest extends FrontDeskTestCase
{
    public function test_can_create_facility_visitor_log(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.front-desk.visitors.store'), [
            'visitor_context' => 'facility',
            'visitor_name' => 'Kwame Boateng',
            'visitor_phone' => '0200000001',
            'person_to_see' => 'Accounts Office',
            'department_id' => $this->department->id,
        ]);

        $response->assertRedirect(route('admin.front-desk.visitors.index'));

        $log = FrontDeskVisitorLog::first();
        $this->assertNotNull($log);
        $this->assertSame('Kwame Boateng', $log->visitor_name);
        $this->assertSame('checked_in', $log->status->value);
        $this->assertSame($this->user->id, $log->checked_in_by);
        $this->assertNotNull($log->time_in);
    }

    public function test_can_create_patient_linked_visitor_log(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.store'), [
            'visitor_context' => 'patient',
            'visitor_name' => 'Ama Serwaa',
            'patient_id' => $patient->id,
            'relationship_to_patient' => 'Sister',
        ])->assertRedirect();

        $this->assertDatabaseHas('front_desk_visitor_logs', [
            'visitor_name' => 'Ama Serwaa',
            'patient_id' => $patient->id,
            'visitor_context' => 'patient',
        ]);
    }

    public function test_can_list_search_and_filter_visitor_logs(): void
    {
        $this->makeVisitor(['visitor_name' => 'Findable Visitor', 'status' => 'checked_in']);
        $this->makeVisitor(['visitor_name' => 'Other Person', 'status' => 'checked_out', 'time_out' => now()]);

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.visitors.index', ['search' => 'Findable']))
            ->assertOk()
            ->assertSee('Findable Visitor')
            ->assertDontSee('Other Person');

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.visitors.index', ['status' => 'checked_out']))
            ->assertOk()
            ->assertSee('Other Person')
            ->assertDontSee('Findable Visitor');
    }

    public function test_can_show_visitor_log(): void
    {
        $log = $this->makeVisitor(['visitor_name' => 'Shown Visitor']);

        $this->actingAs($this->user)
            ->get(route('admin.front-desk.visitors.show', $log))
            ->assertOk()
            ->assertSee('Shown Visitor');
    }

    public function test_can_update_visitor_log(): void
    {
        $log = $this->makeVisitor(['visitor_name' => 'Before Name']);

        $this->actingAs($this->user)->put(route('admin.front-desk.visitors.update', $log), [
            'visitor_context' => 'facility',
            'visitor_name' => 'After Name',
            'status' => 'checked_in',
        ])->assertRedirect(route('admin.front-desk.visitors.show', $log));

        $this->assertSame('After Name', $log->fresh()->visitor_name);
    }

    public function test_can_check_out_visitor(): void
    {
        $log = $this->makeVisitor(['status' => 'checked_in']);

        $this->actingAs($this->user)
            ->post(route('admin.front-desk.visitors.check-out', $log))
            ->assertRedirect();

        $log->refresh();
        $this->assertSame('checked_out', $log->status->value);
        $this->assertNotNull($log->time_out);
        $this->assertSame($this->user->id, $log->checked_out_by);
    }

    public function test_cannot_check_out_already_checked_out_visitor(): void
    {
        $log = $this->makeVisitor(['status' => 'checked_out', 'time_out' => now()->subHour(), 'checked_out_by' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('admin.front-desk.visitors.check-out', $log))
            ->assertSessionHasErrors('status');

        // Only one checkout audit event should ever exist for this record.
        $this->assertSame(0, ActivityLog::where('event', 'FRONT_DESK_VISITOR_CHECKED_OUT')->count());
    }

    public function test_currently_inside_and_overdue_scopes(): void
    {
        $this->makeVisitor(['status' => 'checked_in', 'time_in' => now()->subHour()]);
        $this->makeVisitor(['status' => 'checked_in', 'time_in' => now()->subHours(6)]); // overdue
        $this->makeVisitor(['status' => 'checked_out', 'time_out' => now()]);

        $this->assertSame(2, FrontDeskVisitorLog::query()->currentlyInside()->count());
        $this->assertSame(1, FrontDeskVisitorLog::query()->overdue()->count());
    }

    public function test_audit_log_is_written_on_create_and_checkout(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.store'), [
            'visitor_context' => 'facility',
            'visitor_name' => 'Audited Visitor',
        ]);

        $log = FrontDeskVisitorLog::first();
        $created = ActivityLog::where('event', 'FRONT_DESK_VISITOR_CREATED')->first();
        $this->assertNotNull($created);
        $this->assertSame('FRONT_DESK', $created->log_name);
        $this->assertSame($log->id, (int) $created->subject_id);
        $this->assertSame($log->id, (int) $created->properties['metadata']['front_desk_visitor_log_id']);

        $this->actingAs($this->user)->post(route('admin.front-desk.visitors.check-out', $log));
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_VISITOR_CHECKED_OUT')->exists());
    }

    private function makeVisitor(array $overrides = []): FrontDeskVisitorLog
    {
        return FrontDeskVisitorLog::create(array_merge([
            'visitor_context' => 'facility',
            'visitor_name' => 'Test Visitor',
            'time_in' => now(),
            'status' => 'checked_in',
            'checked_in_by' => $this->user->id,
        ], $overrides));
    }
}
