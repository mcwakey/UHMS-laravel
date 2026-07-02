<?php

namespace Tests\Feature;

use App\Enums\LogModule;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientPrivacyInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (config('patient_privacy.permissions') as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_sensitive_patient_fields_are_masked_without_field_permission(): void
    {
        $user = User::factory()->create();
        $privacy = app(PatientPrivacyService::class);

        $this->actingAs($user);

        $this->assertSame('024****567', $privacy->display('phone', '0241234567'));
        $this->assertSame('jo****@example.com', $privacy->display('email', 'john@example.com'));
        $this->assertSame('GHA******789', $privacy->display('ghana_card_number', 'GHA123456789'));
        $this->assertSame(__('patients.privacy.hidden'), $privacy->display('address', 'House 12'));
    }

    public function test_field_permission_reveals_only_that_sensitive_category(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('patients.contact.view');
        $privacy = app(PatientPrivacyService::class);

        $this->actingAs($user->fresh());

        $this->assertSame('0241234567', $privacy->display('phone', '0241234567'));
        $this->assertSame('GHA******789', $privacy->display('ghana_card_number', 'GHA123456789'));
    }

    public function test_patient_profile_privacy_audit_does_not_store_raw_sensitive_values(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'phone' => '0241234567',
            'email' => 'john@example.com',
            'allergies' => 'Peanuts',
        ]);

        $this->actingAs($user);

        app(PatientPrivacyService::class)->auditPatientProfileView($patient, ['phone', 'email', 'allergies']);

        $activity = Activity::query()
            ->where('log_name', LogModule::PATIENTS->value)
            ->where('event', config('patient_privacy.audit.profile_view_action'))
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($patient->id, $activity->properties['patient_id']);
        $this->assertSame(['contact', 'clinical_sensitive'], $activity->properties['metadata']['sensitive_categories']);

        $encoded = json_encode($activity->properties->toArray());
        $this->assertStringNotContainsString('0241234567', $encoded);
        $this->assertStringNotContainsString('john@example.com', $encoded);
        $this->assertStringNotContainsString('Peanuts', $encoded);
    }
}
