<?php

namespace Tests\Unit;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitServiceTest extends TestCase
{
    use RefreshDatabase;

    private VisitService $service;

    private User $user;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VisitService::class);
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $this->department->id]);
    }

    public function test_create_visit(): void
    {
        $this->actingAs($this->user);
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $visit = $this->service->create([
            'patient_id' => $patient->id,
            'visit_type' => VisitType::OUTPATIENT->value,
            'department_id' => $this->department->id,
            'chief_complaint' => 'Test complaint',
        ]);

        $this->assertInstanceOf(Visit::class, $visit);
        $this->assertEquals($patient->id, $visit->patient_id);
        $this->assertStringStartsWith('VST', $visit->visit_number);
    }

    public function test_list_visits(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        Visit::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
        ]);

        $result = $this->service->list();

        $this->assertEquals(5, $result->total());
    }

    public function test_visit_status_transition(): void
    {
        $this->actingAs($this->user);
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'status' => VisitStatus::REGISTERED,
        ]);

        $transitoned = $this->service->transition($visit, VisitStatus::QUEUED);

        $this->assertEquals(VisitStatus::QUEUED, $transitoned->status);
    }

    public function test_today_stats(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'current_department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'visit_date' => now(),
        ]);

        $stats = $this->service->todayStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
    }
}
