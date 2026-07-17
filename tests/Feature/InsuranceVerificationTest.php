<?php

namespace Tests\Feature;

use App\Enums\InsuranceType;
use App\Enums\VerificationStatus;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\InsuranceVerification;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\User;
use App\Services\Insurance\Verification\InsuranceVerificationService;
use App\Services\Insurance\Verification\VerificationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InsuranceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('Verification Tester', 'web');
        $role->givePermissionTo(Permission::findOrCreate('patient.insurance.verify', 'web'));
        $this->user->assignRole($role);
    }

    private function makeInsurance(array $providerOverrides = [], array $insuranceOverrides = []): PatientInsurance
    {
        $provider = InsuranceProvider::create(array_merge([
            'name' => 'Test Provider',
            'short_name' => 'TP',
            'type' => InsuranceType::NHIA->value,
            'is_active' => true,
            'is_default' => false,
        ], $providerOverrides));

        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_default' => true,
            'is_active' => true,
            'coverage_percentage' => 100,
        ]);

        $patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'status' => 'active',
        ]);

        return PatientInsurance::create(array_merge([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id' => $tier->id,
            'membership_number' => 'TP-100',
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_primary' => true,
            'is_active' => true,
        ], $insuranceOverrides));
    }

    public function test_provider_with_no_driver_returns_not_required(): void
    {
        $insurance = $this->makeInsurance();
        $verification = app(InsuranceVerificationService::class)->verify($insurance);

        $this->assertSame(VerificationStatus::NOT_REQUIRED, $verification->status);
        $this->assertSame('manual', $verification->driver);
    }

    public function test_manual_driver_pending_without_reference_then_manual_override_with_reference(): void
    {
        $insurance = $this->makeInsurance(['verification_driver' => 'manual']);
        $service = app(InsuranceVerificationService::class);

        $first = $service->verify($insurance);
        $this->assertSame(VerificationStatus::PENDING, $first->status);
        $this->assertFalse($first->isAcceptable());

        $second = $service->verify($insurance, null, 'MANUAL-12345');
        $this->assertSame(VerificationStatus::MANUAL_OVERRIDE, $second->status);
        $this->assertSame('MANUAL-12345', $second->reference_code);
        $this->assertTrue($second->isAcceptable());
    }

    public function test_code_driver_pending_without_reference_then_valid_with_reference(): void
    {
        $insurance = $this->makeInsurance([
            'verification_driver' => 'code',
            'verification_method' => 'code',
            'verification_channel' => 'desk',
        ]);

        $service = app(InsuranceVerificationService::class);

        $first = $service->verify($insurance);
        $this->assertSame(VerificationStatus::PENDING, $first->status);
        $this->assertFalse($first->isAcceptable());

        $second = $service->verify($insurance, null, 'AUTH-XYZ-123');
        $this->assertSame(VerificationStatus::VALID, $second->status);
        $this->assertSame('AUTH-XYZ-123', $second->reference_code);
    }

    public function test_code_driver_rejects_code_against_pattern(): void
    {
        $insurance = $this->makeInsurance([
            'verification_driver' => 'code',
            'verification_config' => ['code_pattern' => '/^[A-Z]{3}-\d{6}$/'],
        ]);

        $verification = app(InsuranceVerificationService::class)->verify($insurance, null, 'bad code');
        $this->assertSame(VerificationStatus::INVALID, $verification->status);
    }

    public function test_expired_insurance_is_marked_expired(): void
    {
        $insurance = $this->makeInsurance(
            ['verification_driver' => 'manual'],
            ['expiry_date' => now()->subDay()->toDateString()],
        );

        $verification = app(InsuranceVerificationService::class)->verify($insurance);
        $this->assertSame(VerificationStatus::EXPIRED, $verification->status);
    }

    public function test_manager_resolves_driver_from_provider_column(): void
    {
        $insurance = $this->makeInsurance(['verification_driver' => 'code']);
        $manager = app(VerificationManager::class);

        $driver = $manager->for($insurance->insuranceProvider);
        $this->assertSame('code', $driver->name());
        $this->assertTrue($driver->requiresReferenceCode());
    }

    public function test_audit_record_is_persisted(): void
    {
        $insurance = $this->makeInsurance(['verification_driver' => 'manual']);
        $this->actingAs($this->user);
        app(InsuranceVerificationService::class)->verify($insurance);

        $this->assertSame(1, InsuranceVerification::count());
        $row = InsuranceVerification::first();
        $this->assertSame($insurance->id, $row->patient_insurance_id);
        $this->assertSame($this->user->id, $row->verified_by);
    }

    public function test_verification_endpoint_accepts_patient_insurance_verify_without_claims_view(): void
    {
        $insurance = $this->makeInsurance();

        $this->assertTrue($this->user->can('patient.insurance.verify'));
        $this->assertFalse($this->user->can('claims.view'));

        $this->actingAs($this->user)
            ->postJson(route('admin.insurance.verify'), [
                'patient_insurance_id' => $insurance->id,
            ])
            ->assertOk()
            ->assertJsonPath('status', VerificationStatus::NOT_REQUIRED->value);
    }

    public function test_verification_endpoint_rejects_claims_view_without_patient_insurance_verify(): void
    {
        $insurance = $this->makeInsurance();
        $claimsUser = User::factory()->create();
        $claimsRole = Role::findOrCreate('Claims Viewer', 'web');
        $claimsRole->givePermissionTo(Permission::findOrCreate('claims.view', 'web'));
        $claimsUser->assignRole($claimsRole);

        $this->assertTrue($claimsUser->can('claims.view'));
        $this->assertFalse($claimsUser->can('patient.insurance.verify'));

        $this->actingAs($claimsUser)
            ->postJson(route('admin.insurance.verify'), [
                'patient_insurance_id' => $insurance->id,
            ])
            ->assertForbidden();
    }
}
