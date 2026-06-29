<?php

namespace Tests\Feature\Journey;

use App\Models\Department;
use App\Models\JourneyFlowSnapshot;
use App\Models\User;
use App\Services\Journey\JourneyAnalyticsQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JourneyAnalyticsPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function snap(array $attrs): void
    {
        JourneyFlowSnapshot::create(array_merge([
            'snapshot_date' => today()->toDateString(), 'granularity' => 'day',
            'from_department_type' => 'consultation', 'to_department_type' => 'investigation',
            'cause' => 'awaiting_lab_result', 'sla_status' => 'breached',
            'handoff_count' => 0, 'breached_count' => 0, 'critical_breach_count' => 0,
            'unassigned_count' => 0, 'assigned_count' => 0, 'acknowledged_count' => 0, 'resolved_count' => 0,
            'total_elapsed_minutes' => 0,
        ], $attrs));
    }

    private function filters(array $extra = []): array
    {
        return array_merge([
            'date_from' => today()->subDays(7)->toDateString(),
            'date_to' => today()->toDateString(), 'granularity' => 'day', '_ttl' => 0,
        ], $extra);
    }

    public function test_unauthorized_user_gets_403(): void
    {
        Permission::firstOrCreate(['name' => 'journey.oversight', 'guard_name' => 'web']);
        $user = User::factory()->create(['department_id' => null]); // no capabilities, no oversight

        $this->actingAs($user)->get(route('admin.journey.analytics'))->assertForbidden();
    }

    public function test_department_user_sees_only_allowed_domains(): void
    {
        // An investigation-touching handoff + a pharmacy-only handoff.
        $this->snap(['to_department_type' => 'investigation', 'cause' => 'awaiting_lab_result', 'handoff_count' => 10]);
        $this->snap(['from_department_type' => 'inpatient', 'to_department_type' => 'pharmacy', 'cause' => 'awaiting_dispensing', 'handoff_count' => 99]);

        $summary = app(JourneyAnalyticsQueryService::class)->summary(
            $this->filters(['scope_types' => ['investigation', 'radiology', 'blood_bank']])
        );

        $this->assertSame(10, $summary['handoff_volume']); // the pharmacy-only row is excluded
    }

    public function test_oversight_sees_hospital_wide(): void
    {
        $this->snap(['to_department_type' => 'investigation', 'cause' => 'awaiting_lab_result', 'handoff_count' => 10]);
        $this->snap(['from_department_type' => 'inpatient', 'to_department_type' => 'pharmacy', 'cause' => 'awaiting_dispensing', 'handoff_count' => 99]);

        $summary = app(JourneyAnalyticsQueryService::class)->summary($this->filters(['scope_types' => null]));

        $this->assertSame(109, $summary['handoff_volume']);
    }

    public function test_cache_is_scoped_by_filters(): void
    {
        Cache::flush();
        $this->snap(['to_department_type' => 'investigation', 'cause' => 'awaiting_lab_result', 'handoff_count' => 10]);
        $this->snap(['from_department_type' => 'inpatient', 'to_department_type' => 'pharmacy', 'cause' => 'awaiting_dispensing', 'handoff_count' => 99]);

        $service = app(JourneyAnalyticsQueryService::class);
        $all = $service->summary($this->filters(['scope_types' => null, '_ttl' => 300]));
        $scoped = $service->summary($this->filters(['scope_types' => ['investigation', 'radiology', 'blood_bank'], '_ttl' => 300]));

        // Different scope → different cache key → different result (no leakage).
        $this->assertSame(109, $all['handoff_volume']);
        $this->assertSame(10, $scoped['handoff_volume']);
    }
}
