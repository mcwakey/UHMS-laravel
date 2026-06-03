<?php

namespace Tests\Feature;

use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 6 — high-risk actions must capture a reason before the database write.
 */
class HighRiskActionReasonTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $role = Role::findOrCreate('R6_'.uniqid(), 'web');
        foreach ($permissions as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $user->assignRole($role);

        return $user;
    }

    /* ── Patient merge requires a reason ── */

    public function test_patient_merge_request_requires_a_reason(): void
    {
        $user = $this->userWith(['patients.view', 'patients.merge.view', 'patients.merge.request', 'patients.merge.execute']);
        $main = Patient::factory()->create(['registered_by' => $user->id]);
        $dup = Patient::factory()->create(['registered_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('admin.patients.merge.requests.store'), [
            'main_patient_number' => $main->patient_number,
            'duplicate_patient_number' => $dup->patient_number,
            'confirmed' => '1',
            // reason intentionally omitted
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('patient_merge_requests', 0);
    }

    public function test_patient_merge_request_succeeds_with_a_reason(): void
    {
        $user = $this->userWith(['patients.view', 'patients.merge.view', 'patients.merge.request']);
        $main = Patient::factory()->create(['registered_by' => $user->id]);
        $dup = Patient::factory()->create(['registered_by' => $user->id]);

        $response = $this->actingAs($user)->post(route('admin.patients.merge.requests.store'), [
            'main_patient_number' => $main->patient_number,
            'duplicate_patient_number' => $dup->patient_number,
            'confirmed' => '1',
            'reason' => 'Same patient registered twice with different phone numbers.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('patient_merge_requests', 1);
    }

    /* ── Triage override requires a reason ── */

    public function test_triage_override_requires_a_reason(): void
    {
        $user = $this->userWith(['emergency.triage.perform', 'emergency.case.view']);
        $case = $this->emergencyCase($user);

        // final differs from computed category → override → reason required
        $response = $this->actingAs($user)->post(route('admin.emergency.triage.store', $case), [
            'triage_category' => 'GREEN',
            'final_triage_category' => 'RED',
        ]);

        $response->assertSessionHasErrors('triage_override_reason');
    }

    public function test_triage_without_override_does_not_require_reason(): void
    {
        $user = $this->userWith(['emergency.triage.perform', 'emergency.case.view']);
        $case = $this->emergencyCase($user);

        // final matches computed category → not an override → no reason error
        $response = $this->actingAs($user)->post(route('admin.emergency.triage.store', $case), [
            'triage_category' => 'GREEN',
            'final_triage_category' => 'GREEN',
        ]);

        $response->assertSessionDoesntHaveErrors('triage_override_reason');
    }

    private function emergencyCase(User $user): EmergencyCase
    {
        $patient = Patient::factory()->create(['registered_by' => $user->id]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'created_by' => $user->id]);

        return EmergencyCase::create([
            'emergency_number' => 'ER'.uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'arrival_mode' => 'WALK_IN',
            'arrival_time' => now(),
            'created_by' => $user->id,
            'emergency_status' => 'WAITING',
        ]);
    }
}
