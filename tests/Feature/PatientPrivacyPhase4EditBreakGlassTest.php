<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\PatientPrivacyOverride;
use App\Models\User;
use App\Services\PatientPrivacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PatientPrivacyPhase4EditBreakGlassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::factory()->create(['id' => 1, 'email' => 'system@example.test']);

        foreach (config('patient_privacy.permissions', []) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (['patients.view', 'patients.create', 'patients.edit'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public function test_user_without_contact_edit_cannot_update_patient_phone(): void
    {
        $user = $this->userWith('patients.view', 'patients.edit');
        $patient = Patient::factory()->create(['phone' => '0241234567']);

        $response = $this->actingAs($user)->put(route('admin.patients.update', $patient), $this->payload($patient, [
            'phone' => '0247654321',
        ]));

        $response->assertSessionHasErrors('phone');
        $this->assertSame('0241234567', $patient->fresh()->phone);
    }

    public function test_user_with_contact_edit_can_update_patient_phone(): void
    {
        $user = $this->userWith('patients.view', 'patients.edit', 'patients.contact.edit');
        $patient = Patient::factory()->create(['phone' => '0241234567']);

        $this->actingAs($user)
            ->put(route('admin.patients.update', $patient), $this->payload($patient, ['phone' => '0247654321']))
            ->assertRedirect();

        $this->assertSame('0247654321', $patient->fresh()->phone);
    }

    public function test_masked_edit_field_can_be_omitted_without_overwriting_original_value(): void
    {
        $user = $this->userWith('patients.view', 'patients.edit');
        $patient = Patient::factory()->create(['phone' => '0241234567']);

        $payload = $this->payload($patient);
        unset($payload['phone']);

        $this->actingAs($user)
            ->put(route('admin.patients.update', $patient), $payload)
            ->assertRedirect();

        $this->assertSame('0241234567', $patient->fresh()->phone);
    }

    public function test_pii_edit_does_not_allow_clinical_sensitive_edit(): void
    {
        $user = $this->userWith('patients.view', 'patients.edit', 'patients.pii.edit');
        $patient = Patient::factory()->create([
            'ghana_card_number' => 'GHA-123456789-1',
            'allergies' => 'Penicillin',
        ]);

        $response = $this->actingAs($user)->put(route('admin.patients.update', $patient), $this->payload($patient, [
            'ghana_card_number' => 'GHA-987654321-1',
            'allergies' => 'Latex',
        ]));

        $response->assertSessionHasErrors('allergies');
        $patient->refresh();
        $this->assertSame('GHA-123456789-1', $patient->ghana_card_number);
        $this->assertSame('Penicillin', $patient->allergies);
    }

    public function test_clinical_sensitive_permission_allows_level_three_edit(): void
    {
        $user = $this->userWith('patients.view', 'patients.edit', 'patients.clinical_sensitive.edit');
        $patient = Patient::factory()->create(['allergies' => 'Penicillin']);

        $this->actingAs($user)
            ->put(route('admin.patients.update', $patient), $this->payload($patient, ['allergies' => 'Latex']))
            ->assertRedirect();

        $this->assertSame('Latex', $patient->fresh()->allergies);
    }

    public function test_break_glass_requires_permission_and_reason(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->userWith('patients.view'))
            ->post(route('admin.patients.privacy.break-glass.start', $patient), ['reason' => 'Need access'])
            ->assertForbidden();

        $this->actingAs($this->userWith('patients.view', 'patients.privacy.break_glass'))
            ->post(route('admin.patients.privacy.break-glass.start', $patient), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_break_glass_creates_time_limited_audited_override_and_does_not_grant_export(): void
    {
        $user = $this->userWith('patients.view', 'patients.privacy.break_glass');
        $patient = Patient::factory()->create(['phone' => '0241234567']);

        $this->actingAs($user)
            ->post(route('admin.patients.privacy.break-glass.start', $patient), [
                'reason' => 'Clinical emergency requires contact confirmation',
            ])
            ->assertRedirect();

        $override = PatientPrivacyOverride::first();
        $this->assertNotNull($override);
        $this->assertTrue($override->expires_at->greaterThan(now()));
        $this->assertDatabaseHas('activity_log', ['event' => 'PATIENT_PRIVACY_BREAK_GLASS_STARTED']);

        $privacy = app(PatientPrivacyService::class);
        $this->assertSame('0241234567', $privacy->displayForPatient('phone', $patient->phone, $patient, $user));
        $this->assertNotSame('0241234567', $privacy->displayForExport('phone', $patient->phone, $user));
        $this->assertDatabaseHas('activity_log', ['event' => 'PATIENT_PRIVACY_BREAK_GLASS_USED']);
    }

    public function test_expired_break_glass_no_longer_reveals_protected_fields(): void
    {
        $user = $this->userWith('patients.view', 'patients.privacy.break_glass');
        $patient = Patient::factory()->create(['phone' => '0241234567']);

        PatientPrivacyOverride::create([
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'reason' => 'Expired access test',
            'starts_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertNotSame(
            '0241234567',
            app(PatientPrivacyService::class)->displayForPatient('phone', $patient->phone, $patient, $user)
        );
    }

    public function test_privacy_directive_creation_and_visibility_are_permissioned(): void
    {
        $patient = Patient::factory()->create();
        $manager = $this->userWith('patients.view', 'patients.privacy_directives.view', 'patients.privacy_directives.manage');
        $viewer = $this->userWith('patients.view', 'patients.privacy_directives.view');
        $restricted = $this->userWith('patients.view');

        $this->actingAs($manager)
            ->post(route('admin.patients.privacy-directives.store', $patient), [
                'directive_type' => 'confidential_patient',
                'summary' => 'Restrict casual disclosure',
                'details' => 'Only clinical staff should discuss this folder.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_privacy_directives', [
            'patient_id' => $patient->id,
            'directive_type' => 'confidential_patient',
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.patients.show', $patient))
            ->assertOk()
            ->assertSee('Restrict casual disclosure');

        $this->actingAs($restricted)
            ->get(route('admin.patients.show', $patient))
            ->assertOk()
            ->assertSee(__('patients.privacy.directive_details_hidden'))
            ->assertDontSee('Only clinical staff should discuss this folder.');
    }

    public function test_privacy_audit_routes_and_historical_dry_run_are_permissioned_and_non_mutating(): void
    {
        $patient = Patient::factory()->create();
        activity('PATIENTS')
            ->performedOn($patient)
            ->withProperties(['patient_id' => $patient->id, 'metadata' => ['field' => 'phone']])
            ->event('PATIENT_PRIVACY_BREAK_GLASS_USED')
            ->log('test privacy audit event');

        $this->actingAs($this->userWith('patients.view'))
            ->get(route('admin.patient-privacy.audit'))
            ->assertForbidden();

        $auditor = $this->userWith('patients.privacy_audit.view');
        $this->actingAs($auditor)
            ->get(route('admin.patient-privacy.audit'))
            ->assertOk()
            ->assertSee('PATIENT_PRIVACY_BREAK_GLASS_USED');

        $before = ActivityLog::count();
        $this->artisan('patient-privacy:audit-historical-logs', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame($before, ActivityLog::count());
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Patient $patient, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'other_names' => $patient->other_names,
            'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
            'gender' => $patient->gender->value,
            'blood_group' => $patient->blood_group?->value,
            'marital_status' => $patient->marital_status?->value,
            'religion' => $patient->religion,
            'phone' => $patient->phone,
            'phone_secondary' => $patient->phone_secondary,
            'email' => $patient->email,
            'ghana_card_number' => $patient->ghana_card_number,
            'occupation' => $patient->occupation,
            'address' => $patient->address,
            'city' => $patient->city,
            'town' => $patient->town,
            'region' => $patient->region,
            'digital_address' => $patient->digital_address,
            'allergies' => $patient->allergies,
            'chronic_conditions' => $patient->chronic_conditions,
        ], $overrides);
    }
}
