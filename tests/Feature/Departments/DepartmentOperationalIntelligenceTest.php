<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Department\DepartmentAlertService;
use App\Services\Department\DepartmentIdentityWidgetBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DepartmentOperationalIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function department(DepartmentType $type): Department
    {
        $this->seq++;

        return Department::create(['name' => $type->value.' '.$this->seq, 'code' => 'OI'.$this->seq, 'type' => $type->value, 'status' => 'active']);
    }

    private function payloadFor(User $user): array
    {
        $request = Request::create('/admin/my-dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        return app(DepartmentDashboardController::class)->index($request)->getData();
    }

    public function test_each_type_gets_one_widget_with_status_and_metrics(): void
    {
        foreach ([DepartmentType::CONSULTATION, DepartmentType::PHARMACY, DepartmentType::INVESTIGATION, DepartmentType::INPATIENT] as $type) {
            $user = User::factory()->create(['department_id' => $this->department($type)->id]);
            $widget = $this->payloadFor($user)['identity_widget'];

            $this->assertIsArray($widget);
            $this->assertArrayHasKey('status', $widget);
            $this->assertNotEmpty($widget['status_label']);
            $this->assertNotEmpty($widget['metrics']);
            $this->assertNotEmpty($widget['value']);
        }
    }

    public function test_alerts_are_derived_from_existing_values(): void
    {
        $alerts = app(DepartmentAlertService::class)->alertsFor(
            DepartmentType::PHARMACY,
            fn (string $k) => ['low_stock' => 5, 'pending_prescriptions' => 20][$k] ?? 0,
        );

        $keys = collect($alerts)->pluck('key');
        $this->assertTrue($keys->contains('stock_shortage'));
        $this->assertTrue($keys->contains('dispensing_backlog'));
    }

    public function test_operational_status_escalates_with_severity(): void
    {
        $service = app(DepartmentAlertService::class);

        $this->assertSame('stable', $service->statusFor(null, fn () => 0, [])['level']);
        $this->assertSame('busy', $service->statusFor(null, fn () => 0, [['variant' => 'warning']])['level']);
        $this->assertSame('critical', $service->statusFor(null, fn () => 0, [['variant' => 'danger']])['level']);
    }

    public function test_capability_restricted_values_do_not_raise_alerts(): void
    {
        // A restricted (capability-gated) value reads as 0 → no alert is raised.
        $alerts = app(DepartmentAlertService::class)->alertsFor(
            DepartmentType::CONSULTATION,
            fn (string $k) => $k === 'low_stock' ? ['restricted' => true, 'value' => null] : 0,
        );

        $this->assertEmpty($alerts);
    }

    public function test_financial_widget_is_restricted_when_revenue_is(): void
    {
        $widget = app(DepartmentIdentityWidgetBuilder::class)->build(
            DepartmentType::FINANCE,
            fn (string $k) => $k === 'department_revenue_today' ? ['restricted' => true, 'value' => null] : 0,
        );

        $this->assertSame('financial_health', $widget['key']);
        $this->assertTrue($widget['restricted']);
    }

    public function test_excessive_waiting_raises_an_alert_and_status(): void
    {
        $opd = $this->department(DepartmentType::CONSULTATION);
        $user = User::factory()->create(['department_id' => $opd->id]);
        Visit::factory()->count(16)->create([
            'current_department_id' => $opd->id,
            'created_by' => $user->id,
            'status' => 'queued',
        ]);

        $payload = $this->payloadFor($user);

        $this->assertTrue(collect($payload['alerts'])->contains('key', 'excessive_waiting'));
        $this->assertContains($payload['operational_status']['level'], ['busy', 'critical']);
    }
}
