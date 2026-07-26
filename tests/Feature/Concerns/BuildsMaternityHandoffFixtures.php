<?php

namespace Tests\Feature\Concerns;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DepartmentType;
use App\Enums\PregnancyProfileStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\AntenatalVisit;
use App\Models\Bed;
use App\Models\DeliveryRecord;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\LaborEpisode;
use App\Models\Patient;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\Ward;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 14R.5 — shared fixtures for the four handoff suites.
 *
 * Deliberately builds records with explicit `create()` calls (matching the
 * existing Admission/Maternity suites) rather than factories, so the suites do
 * not depend on factory definitions that some of these models lack.
 */
trait BuildsMaternityHandoffFixtures
{
    protected User $user;

    protected Department $department;

    protected Patient $patient;

    protected Visit $visit;

    protected PregnancyProfile $profile;

    protected ?AntenatalVisit $ancVisit = null;

    protected ?LaborEpisode $labor = null;

    protected ?EmergencyCase $emergencyCase = null;

    protected ?Ward $ward = null;

    protected ?Bed $bed = null;

    protected ?Department $emergencyDepartment = null;

    private ?Patient $otherPatientCache = null;

    private ?Admission $admissionCache = null;

    private ?AdmissionRequest $admissionRequestCache = null;

    private ?PostnatalCase $postnatalCache = null;

    private ?DeliveryRecord $deliveryCache = null;

    private ?PregnancyProfile $secondProfileCache = null;

    /** Set every Phase 14R.5 integration flag explicitly. */
    protected function handoffFlags(
        bool $consultation = false,
        bool $emergencyContext = false,
        bool $admissionContext = false,
        bool $emergencyHandoffs = false,
    ): void {
        config([
            'maternity.integration.consultation_handoffs_enabled' => $consultation,
            'maternity.integration.emergency_context_enabled' => $emergencyContext,
            'maternity.integration.admission_context_enabled' => $admissionContext,
            'maternity.integration.emergency_handoffs_enabled' => $emergencyHandoffs,
        ]);
    }

    /** The four Obstetrics/Gynaecology flags stay independent of the above. */
    protected function obgynFlags(bool $obstetrics = false, bool $gynaecology = false): void
    {
        config([
            'consultation.maternity_context.obstetrics_workspace_enabled' => $obstetrics,
            'consultation.maternity_context.gynaecology_context_enabled' => $gynaecology,
        ]);
    }

    protected function buildMaternityFixture(): void
    {
        $this->department = Department::factory()->create([
            'type' => DepartmentType::CONSULTATION->value,
            'status' => 'active',
        ]);

        $this->user = User::factory()->create(['department_id' => $this->department->id]);
        $this->patient = Patient::factory()->create();

        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'visit_type' => VisitType::OUTPATIENT,
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $this->department->id,
        ]);

        $this->profile = PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'gravida' => 2,
            'para' => 1,
            'last_menstrual_period' => now()->subWeeks(20)->toDateString(),
            'estimated_due_date' => now()->addWeeks(20)->toDateString(),
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);

        $this->ancVisit = AntenatalVisit::create([
            'pregnancy_profile_id' => $this->profile->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'visit_date' => now()->subDays(3),
            'visit_number' => 1,
            'status' => 'recorded',
        ]);

        $this->labor = LaborEpisode::create([
            'pregnancy_profile_id' => $this->profile->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'started_at' => now()->subHours(2),
            'labor_stage' => 'first_stage',
            'status' => 'active',
        ]);

        // EmergencySessionService resolves an emergency department when it
        // opens a session; without one the emergency consultation route cannot
        // be created. Mirrors a real installation.
        $this->emergencyDepartment = Department::factory()->create([
            'name' => 'Emergency',
            'code' => 'ER',
            'type' => DepartmentType::EMERGENCY->value,
            'status' => 'active',
        ]);

        $this->emergencyCase = $this->makeEmergencyCase();
        $this->makeWardAndBed();
    }

    protected function makeEmergencyCase(?Visit $visit = null): EmergencyCase
    {
        $visit ??= $this->visit;

        return EmergencyCase::create([
            'emergency_number' => 'EC-'.uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'arrival_mode' => 'WALK_IN',
            'arrival_time' => now(),
            'emergency_status' => EmergencyCase::STATUS_UNDER_CARE,
            'created_by' => $this->user->id,
        ]);
    }

    protected function makeWardAndBed(): void
    {
        $inpatient = Department::factory()->create([
            'type' => DepartmentType::INPATIENT->value,
            'status' => 'active',
        ]);

        $this->ward = Ward::create([
            'name' => 'Maternity Ward',
            'code' => 'MAT-'.uniqid(),
            'department_id' => $inpatient->id,
            'capacity' => 10,
            'is_active' => true,
        ]);

        $this->bed = Bed::create([
            'ward_id' => $this->ward->id,
            'bed_number' => 'MB-'.uniqid(),
            'bed_type' => 'standard',
            'status' => BedStatus::AVAILABLE,
            'daily_rate' => 0,
        ]);
    }

    protected function consultationRoute(bool $started = true): VisitConsultationRoute
    {
        return VisitConsultationRoute::create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'status' => VisitConsultationRoute::STATUS_ACTIVE,
            'routed_by' => $this->user->id,
            'started_at' => $started ? now() : null,
            'activated_at' => now(),
        ]);
    }

    protected function otherPatient(): Patient
    {
        return $this->otherPatientCache ??= Patient::factory()->create();
    }

    protected function secondProfile(): PregnancyProfile
    {
        return $this->secondProfileCache ??= PregnancyProfile::create([
            'patient_id' => $this->patient->id,
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'profile_status' => PregnancyProfileStatus::ACTIVE->value,
        ]);
    }

    protected function delivery(): DeliveryRecord
    {
        return $this->deliveryCache ??= DeliveryRecord::create([
            'labor_episode_id' => $this->labor->id,
            'pregnancy_profile_id' => $this->profile->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'recorded_by' => $this->user->id,
            'delivery_at' => now(),
            'status' => 'draft',
        ]);
    }

    protected function postnatal(): PostnatalCase
    {
        return $this->postnatalCache ??= PostnatalCase::create([
            'delivery_record_id' => $this->delivery()->id,
            'labor_episode_id' => $this->labor->id,
            'pregnancy_profile_id' => $this->profile->id,
            'mother_patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'department_id' => $this->department->id,
            'opened_by' => $this->user->id,
        ]);
    }

    protected function admissionRequest(array $overrides = []): AdmissionRequest
    {
        return $this->admissionRequestCache ??= AdmissionRequest::create(array_merge([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'source_type' => AdmissionRequestSource::DIRECT->value,
            'source_id' => null,
            'requested_by' => $this->user->id,
            'status' => 'requested',
            'requested_at' => now(),
        ], $overrides));
    }

    protected function admission(array $overrides = []): Admission
    {
        return $this->admissionCache ??= Admission::create(array_merge([
            'admission_number' => Admission::generateAdmissionNumber(),
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'bed_id' => $this->bed?->id,
            'admitted_by' => $this->user->id,
            'admission_date' => now(),
            'status' => AdmissionStatus::ADMITTED,
        ], $overrides));
    }

    /**
     * A user holding exactly the listed permissions (plus baseline access), so
     * every test can prove that BOTH the bridge and the target permission are
     * required.
     *
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions, array $baseline = ['consultations.view']): User
    {
        $user = User::factory()->create(['department_id' => $this->department->id]);
        $role = Role::findOrCreate('Handoff '.uniqid(), 'web');

        foreach (array_merge($baseline, $permissions) as $permission) {
            Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
