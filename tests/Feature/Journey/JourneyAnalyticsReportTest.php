<?php

namespace Tests\Feature\Journey;

use App\Models\JourneyFlowSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JourneyAnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    private function oversightUser(): User
    {
        Permission::firstOrCreate(['name' => 'journey.oversight', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('journey.oversight');

        return $user;
    }

    private function snap(array $attrs = []): void
    {
        JourneyFlowSnapshot::create(array_merge([
            'snapshot_date' => today()->toDateString(), 'granularity' => 'day',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'cause' => 'awaiting_lab_result', 'sla_status' => 'breached',
            'handoff_count' => 12, 'breached_count' => 5, 'critical_breach_count' => 2,
            'unassigned_count' => 4, 'assigned_count' => 3, 'acknowledged_count' => 2, 'resolved_count' => 0,
            'total_elapsed_minutes' => 1200,
        ], $attrs));
    }

    public function test_report_renders_for_oversight(): void
    {
        $this->snap();

        $this->actingAs($this->oversightUser())
            ->get(route('admin.journey.analytics'))
            ->assertOk()
            ->assertSee(__('journey.analytics.matrix'), false)
            ->assertSee(__('journey.analytics.metric.handoff_volume'), false);
    }

    public function test_empty_state_renders_when_no_snapshots(): void
    {
        $this->actingAs($this->oversightUser())
            ->get(route('admin.journey.analytics'))
            ->assertOk()
            ->assertSee(__('journey.analytics.no_data'), false);
    }

    public function test_export_contains_no_patient_columns(): void
    {
        $this->snap();

        $response = $this->actingAs($this->oversightUser())
            ->get(route('admin.journey.analytics.export', ['dataset' => 'matrix']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $this->assertStringContainsString('from,to', $content);
        $this->assertStringNotContainsStringIgnoringCase('patient', $content);
        $this->assertStringNotContainsStringIgnoringCase('visit', $content);
    }
}
