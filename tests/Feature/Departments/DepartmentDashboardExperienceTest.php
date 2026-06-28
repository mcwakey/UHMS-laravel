<?php

namespace Tests\Feature\Departments;

use App\Enums\DepartmentType;
use App\Http\Controllers\Admin\Dashboard\DepartmentDashboardController;
use App\Models\Department;
use App\Models\User;
use App\Models\Visit;
use App\Services\Department\DepartmentStatusPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DepartmentDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function department(DepartmentType $type, string $name, string $code): Department
    {
        return Department::create(['name' => $name, 'code' => $code, 'type' => $type->value, 'status' => 'active']);
    }

    private function payloadFor(User $user): array
    {
        $request = Request::create('/admin/my-dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        return app(DepartmentDashboardController::class)->index($request)->getData();
    }

    public function test_status_presenter_translates_colors_and_flags_priority(): void
    {
        $presenter = app(DepartmentStatusPresenter::class);

        $queued = $presenter->present('queued', 'visit');
        $this->assertSame(__('statuses.visit.queued'), $queued['label']);
        $this->assertSame('warning', $queued['variant']);
        $this->assertNotNull($queued['icon']);
        $this->assertNotSame('queued', $queued['label']); // not raw

        $this->assertSame('secondary', $presenter->present('cancelled', 'default')['variant']);
        // Danger statuses are treated as priority.
        $this->assertTrue($presenter->present('failed', 'default')['priority']);
        $this->assertFalse($presenter->present('completed', 'default')['priority']);
    }

    public function test_queue_rows_have_translated_badges_and_friendly_time(): void
    {
        $dept = $this->department(DepartmentType::CONSULTATION, 'OPD', 'OPD');
        $user = User::factory()->create(['department_id' => $dept->id]);
        Visit::factory()->create([
            'current_department_id' => $dept->id,
            'created_by' => $user->id,
            'status' => 'queued',
            'created_at' => now()->subMinutes(10),
        ]);

        $rows = $this->payloadFor($user)['work_queue']['rows'];

        $this->assertNotEmpty($rows);
        $this->assertSame(__('statuses.visit.queued'), $rows[0]['badge']);
        $this->assertSame('warning', $rows[0]['badge_variant']);
        $this->assertNotNull($rows[0]['badge_icon']);
        $this->assertStringContainsStringIgnoringCase('ago', $rows[0]['meta']);
    }

    public function test_quick_actions_are_type_specific(): void
    {
        $pharmacy = $this->department(DepartmentType::PHARMACY, 'Pharmacy', 'PH');
        $user = User::factory()->create(['department_id' => $pharmacy->id]);

        $labels = collect($this->payloadFor($user)['quick_actions'])->pluck('label');

        // "Dispense" is a pharmacy-only action; the old generic set never had it.
        $this->assertTrue($labels->contains(__('dashboards.department.actions.dispense')));
    }

    public function test_each_type_gets_exactly_one_identity_widget(): void
    {
        $expected = [
            ['CONSULTATION', DepartmentType::CONSULTATION, 'waiting_pressure'],
            ['PHARMACY', DepartmentType::PHARMACY, 'dispensing_efficiency'],
            ['INVESTIGATION', DepartmentType::INVESTIGATION, 'turnaround'],
            ['RADIOLOGY', DepartmentType::RADIOLOGY, 'imaging_backlog'],
            ['FINANCE', DepartmentType::FINANCE, 'financial_health'],
            ['INPATIENT', DepartmentType::INPATIENT, 'capacity_status'],
        ];

        foreach ($expected as [$code, $type, $key]) {
            $dept = $this->department($type, $type->value, $code);
            $user = User::factory()->create(['department_id' => $dept->id]);

            $widget = $this->payloadFor($user)['identity_widget'];

            $this->assertIsArray($widget);
            $this->assertSame($key, $widget['key']);
            $this->assertNotEmpty($widget['title']);
            $this->assertArrayHasKey('variant', $widget);
        }
    }

    public function test_header_carries_personalised_identity_and_context(): void
    {
        $dept = $this->department(DepartmentType::PHARMACY, 'Main Pharmacy', 'MPH');
        $user = User::factory()->create(['department_id' => $dept->id]);

        $dashboard = $this->payloadFor($user)['dashboard'];

        $this->assertNotEmpty($dashboard['menu_heading']);
        $this->assertNotEmpty($dashboard['shift']);
        $this->assertNotNull($dashboard['last_updated']);
        $this->assertSame('Main Pharmacy', $dashboard['department_name']);
    }

    public function test_experience_strings_are_localized_to_french(): void
    {
        app()->setLocale('fr');
        try {
            $this->assertSame('Nouvelle visite', __('dashboards.department.actions.new_visit'));
            $this->assertSame('File', __('dashboards.department.actions.queue_management'));
            $this->assertSame('Cas actifs', __('dashboards.department.widget.active_load'));
        } finally {
            app()->setLocale('en');
        }
    }
}
