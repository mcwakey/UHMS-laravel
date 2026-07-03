<?php

namespace App\Services\Consultation;

use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\Priority;
use App\Enums\ResultType;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\Drug;
use App\Models\DrugCategory;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ConsultationBrowserFixtureService
{
    public const USER_EMAIL = 'consultation.e2e@uhms.test';

    public const USER_PASSWORD = 'password';

    public const PATIENT_NUMBER = 'E2E-CONSULTATION-000001';

    public const VISIT_NUMBER = 'E2E-CONSULTATION-VISIT';

    public const METADATA_PATH = 'app/testing/consultation-workspace-e2e.json';

    /**
     * @return array<string, mixed>
     */
    public function create(): array
    {
        $this->assertAllowedEnvironment();

        return DB::transaction(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $role = Role::findOrCreate('Consultation E2E Doctor', 'web');
            foreach ($this->permissions() as $permission) {
                $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }

            $consultationDepartment = Department::updateOrCreate(
                ['code' => 'E2ECON'],
                [
                    'name' => 'E2E General Consultation',
                    'type' => DepartmentType::CONSULTATION->value,
                    'status' => 'active',
                    'description' => 'Stable consultation workspace E2E fixture department.',
                ],
            );

            $labDepartment = Department::updateOrCreate(
                ['code' => 'E2ELAB'],
                [
                    'name' => 'E2E Laboratory',
                    'type' => DepartmentType::INVESTIGATION->value,
                    'status' => 'active',
                    'result_type' => ResultType::PARAMETERS->value,
                    'description' => 'Stable lab E2E fixture department.',
                ],
            );

            $procedureDepartment = Department::updateOrCreate(
                ['code' => 'E2EPRO'],
                [
                    'name' => 'E2E Procedure Room',
                    'type' => DepartmentType::PROCEDURE->value,
                    'status' => 'active',
                    'description' => 'Stable procedure E2E fixture department.',
                ],
            );

            $doctor = User::updateOrCreate(
                ['email' => self::USER_EMAIL],
                [
                    'first_name' => 'Consultation',
                    'last_name' => 'E2E',
                    'password' => Hash::make(self::USER_PASSWORD),
                    'status' => 'active',
                    'department_id' => $consultationDepartment->id,
                    'email_verified_at' => now(),
                ],
            );
            $doctor->syncRoles([$role]);

            $patient = Patient::updateOrCreate(
                ['patient_number' => self::PATIENT_NUMBER],
                [
                    'first_name' => 'E2E',
                    'last_name' => 'Consultation',
                    'date_of_birth' => '1990-01-01',
                    'gender' => Gender::FEMALE,
                    'phone' => '0240000000',
                    'allergies' => 'Amoxicillin',
                    'status' => 'active',
                    'is_active' => true,
                    'registered_by' => $doctor->id,
                ],
            );

            $consultationService = ServiceCatalog::updateOrCreate(
                ['code' => 'E2E-CONSULT'],
                [
                    'name' => 'E2E General Consultation',
                    'category' => ServiceType::CONSULTATION->value,
                    'department_id' => $consultationDepartment->id,
                    'department_type' => DepartmentType::CONSULTATION->value,
                    'price' => 100,
                    'is_active' => true,
                    'is_billable' => true,
                ],
            );

            $labService = ServiceCatalog::updateOrCreate(
                ['code' => 'E2E-FBC'],
                [
                    'name' => 'E2E Full Blood Count',
                    'category' => ServiceType::INVESTIGATION->value,
                    'department_id' => $labDepartment->id,
                    'department_type' => DepartmentType::INVESTIGATION->value,
                    'price' => 40,
                    'is_active' => true,
                    'is_billable' => true,
                ],
            );

            $procedureService = ServiceCatalog::updateOrCreate(
                ['code' => 'E2E-DRESSING'],
                [
                    'name' => 'E2E Wound Dressing',
                    'category' => ServiceType::PROCEDURE->value,
                    'department_id' => $procedureDepartment->id,
                    'department_type' => DepartmentType::PROCEDURE->value,
                    'price' => 75,
                    'is_active' => true,
                    'is_billable' => true,
                ],
            );

            $category = DrugCategory::firstOrCreate(
                ['name' => 'E2E Medicines'],
                ['description' => 'Stable E2E prescription medicines.', 'is_active' => true],
            );

            $drug = Drug::updateOrCreate(
                ['name' => 'E2E Amoxicillin'],
                [
                    'category_id' => $category->id,
                    'generic_name' => 'Amoxicillin',
                    'dosage_form' => 'tablet',
                    'strength' => '500mg',
                    'unit' => 'tablet',
                    'price' => 10,
                    'requires_prescription' => true,
                    'is_active' => true,
                ],
            );

            $visit = Visit::updateOrCreate(
                ['visit_number' => self::VISIT_NUMBER],
                [
                    'patient_id' => $patient->id,
                    'visit_type' => VisitType::OUTPATIENT,
                    'visit_date' => today(),
                    'status' => VisitStatus::CONSULTING,
                    'priority' => Priority::NORMAL,
                    'current_department_id' => $consultationDepartment->id,
                    'chief_complaint' => 'Stable consultation workspace smoke fixture',
                    'created_by' => $doctor->id,
                    'checked_in_at' => now(),
                    'locked_at' => null,
                    'locked_by' => null,
                    'lock_reason' => null,
                ],
            );

            $route = VisitConsultationRoute::firstOrNew([
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'department_id' => $consultationDepartment->id,
                'service_id' => $consultationService->id,
            ]);

            $route->fill([
                'doctor_id' => $doctor->id,
                'main_doctor_id' => $doctor->id,
                'status' => VisitConsultationRoute::STATUS_ACTIVE,
                'routed_by' => $doctor->id,
                'started_by' => $doctor->id,
                'started_at' => now(),
                'activated_at' => now(),
                'completed_at' => null,
                'completed_by' => null,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                'locked_at' => null,
                'locked_by' => null,
                'lock_reason' => null,
                'notes' => 'Stable E2E consultation route.',
            ])->save();

            $record = MedicalRecord::updateOrCreate(
                ['visit_id' => $visit->id],
                [
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'department_id' => $consultationDepartment->id,
                    'service_id' => $consultationService->id,
                    'consultation_route_id' => $route->id,
                ],
            );

            $metadata = [
                'email' => self::USER_EMAIL,
                'password' => self::USER_PASSWORD,
                'consultation_url' => route('admin.consultations.routes.show', [$visit, $route], false),
                'consultation_absolute_url' => route('admin.consultations.routes.show', [$visit, $route]),
                'visit_id' => $visit->id,
                'visit_number' => $visit->visit_number,
                'patient_id' => $patient->id,
                'patient_number' => $patient->patient_number,
                'consultation_route_id' => $route->id,
                'medical_record_id' => $record->id,
                'doctor_id' => $doctor->id,
                'consultation_department_id' => $consultationDepartment->id,
                'lab_department_id' => $labDepartment->id,
                'procedure_department_id' => $procedureDepartment->id,
                'consultation_service_id' => $consultationService->id,
                'lab_service_id' => $labService->id,
                'procedure_service_id' => $procedureService->id,
                'drug_id' => $drug->id,
                'metadata_path' => storage_path(self::METADATA_PATH),
            ];

            $this->writeMetadata($metadata);

            return $metadata;
        });
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return [
            'consultations.view',
            'consultations.create',
            'prescriptions.create',
            'procedure.request',
            'lab.requests.create',
            'visits.transition',
            'notifications.view',
            'consultation.followup.create',
            'consultation.followup.update',
            'consultation.followup.cancel',
        ];
    }

    public function metadataPath(): string
    {
        return storage_path(self::METADATA_PATH);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function writeMetadata(array $metadata): void
    {
        File::ensureDirectoryExists(dirname($this->metadataPath()));
        File::put($this->metadataPath(), json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function assertAllowedEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Consultation E2E fixture is only available locally or in testing.');
        }
    }
}
