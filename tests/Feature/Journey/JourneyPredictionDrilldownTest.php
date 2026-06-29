<?php

namespace Tests\Feature\Journey;

use App\Models\Department;
use App\Models\JourneyFlowSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JourneyPredictionDrilldownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Permission::firstOrCreate(['name' => 'journey.predictions.view', 'guard_name' => 'web']);
    }

    private function snapshot(): void
    {
        JourneyFlowSnapshot::create([
            'snapshot_date' => today()->toDateString(), 'granularity' => 'day',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'cause' => 'awaiting_lab_result', 'sla_status' => 'breached',
            'handoff_count' => 10, 'breached_count' => 5, 'critical_breach_count' => 2,
            'unassigned_count' => 4, 'assigned_count' => 3, 'acknowledged_count' => 2, 'resolved_count' => 0,
            'total_elapsed_minutes' => 1200,
        ]);
    }

    private function predictUser(bool $canPredict): User
    {
        $dept = Department::create(['name' => 'Lab', 'code' => 'DD'.substr(uniqid(), -6), 'type' => 'investigation', 'status' => 'active']);
        $user = User::factory()->create(['department_id' => $dept->id]);
        if ($canPredict) {
            $user->givePermissionTo('journey.predictions.view');
        }

        return $user;
    }

    public function test_cause_drilldown_link_preserves_filter(): void
    {
        $this->snapshot();

        $this->actingAs($this->predictUser(true))
            ->get(route('admin.journey.analytics'))
            ->assertOk()
            ->assertSee('tab=sla_breaches', false)
            ->assertSee('cause=awaiting_lab_result', false);
    }

    public function test_accuracy_section_hidden_without_permission(): void
    {
        $this->snapshot();

        $this->actingAs($this->predictUser(false))
            ->get(route('admin.journey.analytics'))
            ->assertOk()
            ->assertDontSee(__('journey.accuracy.title'), false);
    }

    public function test_export_aggregate_has_no_patient_columns(): void
    {
        $this->snapshot();

        $response = $this->actingAs($this->predictUser(true))
            ->get(route('admin.journey.analytics.export', ['dataset' => 'prediction_accuracy']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsStringIgnoringCase('patient', $content);
        $this->assertStringNotContainsStringIgnoringCase('visit', $content);
    }
}
