<?php

namespace Tests\Feature\FrontDesk;

use App\Models\ActivityLog;
use App\Models\FrontDeskIncidentLog;
use App\Models\FrontDeskVisitorLog;
use App\Services\FrontDesk\IncidentLogService;

class IncidentDeskTest extends FrontDeskTestCase
{
    public function test_authorized_user_can_view_incident_desk(): void
    {
        $this->actingAs($this->user)->get(route('admin.front-desk.incidents.index'))->assertOk();
    }

    public function test_unauthorized_user_cannot_view_incident_desk(): void
    {
        $stranger = $this->userWith(['front_desk.view']);
        $this->actingAs($stranger)->get(route('admin.front-desk.incidents.index'))->assertForbidden();
    }

    public function test_can_create_incident_with_number(): void
    {
        $this->actingAs($this->user)->post(route('admin.front-desk.incidents.store'), [
            'incident_type' => 'aggressive_visitor', 'severity' => 'high', 'description' => 'Visitor became aggressive at the desk.',
            'location' => 'Main Reception',
        ])->assertRedirect();

        $incident = FrontDeskIncidentLog::first();
        $this->assertMatchesRegularExpression('/^INC-\d{8}-\d{4}$/', $incident->incident_number);
        $this->assertSame('open', $incident->status->value);
        $this->assertTrue(ActivityLog::where('event', 'FRONT_DESK_INCIDENT_CREATED')->exists());
    }

    public function test_can_assign_escalate_resolve(): void
    {
        $incident = $this->makeIncident();

        $this->actingAs($this->user)->post(route('admin.front-desk.incidents.assign', $incident), ['assigned_to_user_id' => $this->user->id])->assertRedirect();
        $this->assertSame('in_progress', $incident->fresh()->status->value);

        $this->actingAs($this->user)->post(route('admin.front-desk.incidents.escalate', $incident), ['escalated_to_user_id' => $this->user->id, 'note' => 'Needs security'])->assertRedirect();
        $incident->refresh();
        $this->assertSame('escalated', $incident->status->value);
        $this->assertNotNull($incident->escalated_at);

        $this->actingAs($this->user)->post(route('admin.front-desk.incidents.resolve', $incident), ['resolution_note' => 'Handled'])->assertRedirect();
        $incident->refresh();
        $this->assertSame('resolved', $incident->status->value);
        $this->assertSame($this->user->id, $incident->resolved_by);

        foreach (['FRONT_DESK_INCIDENT_ASSIGNED', 'FRONT_DESK_INCIDENT_ESCALATED', 'FRONT_DESK_INCIDENT_RESOLVED'] as $event) {
            $this->assertTrue(ActivityLog::where('event', $event)->exists());
        }
    }

    public function test_can_cancel_incident(): void
    {
        $incident = $this->makeIncident();
        $this->actingAs($this->user)->post(route('admin.front-desk.incidents.cancel', $incident), ['reason' => 'Duplicate'])->assertRedirect();
        $this->assertSame('cancelled', $incident->fresh()->status->value);
    }

    public function test_critical_incident_dashboard_count(): void
    {
        $this->makeIncident(['severity' => 'critical']);
        $this->makeIncident(['severity' => 'low']);

        $metrics = app(\App\Services\FrontDesk\FrontDeskDashboardService::class)->metrics($this->user);
        $this->assertSame(2, $metrics['open_incidents_count']);
        $this->assertSame(1, $metrics['critical_open_incidents_count']);
    }

    public function test_linked_visitor_safe_context(): void
    {
        $visitor = FrontDeskVisitorLog::create(['visitor_context' => 'facility', 'visitor_name' => 'Linked Visitor', 'time_in' => now(), 'status' => 'checked_in', 'checked_in_by' => $this->user->id]);
        $incident = $this->makeIncident(['related_visitor_log_id' => $visitor->id]);

        $this->actingAs($this->user)->get(route('admin.front-desk.incidents.show', $incident))
            ->assertOk()->assertSee('Linked Visitor');
    }

    private function makeIncident(array $overrides = []): FrontDeskIncidentLog
    {
        return app(IncidentLogService::class)->create(array_merge([
            'incident_type' => 'security_concern', 'severity' => 'medium', 'description' => 'Test incident.',
        ], $overrides), $this->user);
    }
}
