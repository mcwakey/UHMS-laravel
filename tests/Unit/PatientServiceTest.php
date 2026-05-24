<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PatientService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_patient(): void
    {
        $this->actingAs($this->user);

        $patient = $this->service->create([
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'date_of_birth' => '1990-05-15',
            'gender' => 'male',
            'phone' => '0244123456',
        ]);

        $this->assertInstanceOf(Patient::class, $patient);
        $this->assertEquals('Kofi', $patient->first_name);
        $this->assertStringStartsWith(config('patient.id_prefix', 'UHMS'), $patient->patient_number);
    }

    public function test_list_patients_with_pagination(): void
    {
        Patient::factory()->count(3)->create(['registered_by' => $this->user->id]);

        $result = $this->service->list();

        $this->assertEquals(3, $result->total());
    }

    public function test_list_patients_with_search_filter(): void
    {
        Patient::factory()->create([
            'first_name' => 'Unique',
            'last_name' => 'Patient',
            'registered_by' => $this->user->id,
        ]);
        Patient::factory()->create([
            'first_name' => 'Other',
            'last_name' => 'Person',
            'registered_by' => $this->user->id,
        ]);

        $result = $this->service->list(['search' => 'Unique']);

        $this->assertEquals(1, $result->total());
    }

    public function test_update_patient(): void
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);

        $updated = $this->service->update($patient, [
            'first_name' => 'NewName',
            'last_name' => $patient->last_name,
            'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
            'gender' => $patient->gender->value,
        ]);

        $this->assertEquals('NewName', $updated->first_name);
    }

    public function test_toggle_patient_status(): void
    {
        $patient = Patient::factory()->create([
            'status' => 'active',
            'registered_by' => $this->user->id,
        ]);

        $toggled = $this->service->toggleStatus($patient);

        $this->assertEquals('inactive', $toggled->status);
    }
}
