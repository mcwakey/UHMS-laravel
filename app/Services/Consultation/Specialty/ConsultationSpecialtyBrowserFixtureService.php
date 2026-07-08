<?php

namespace App\Services\Consultation\Specialty;

use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\Priority;
use App\Enums\ServiceType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\ConsultationSpecialtyEntry;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use Database\Seeders\ConsultationSpecialtyOrderSetSeeder;
use Database\Seeders\ConsultationSpecialtySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ConsultationSpecialtyBrowserFixtureService
{
    public const PASSWORD = 'password';
    public const ADMIN_EMAIL = 'specialist.admin.e2e@uhms.test';
    public const METADATA_PATH = 'app/testing/consultation-specialty-workspaces-e2e.json';

    /**
     * @return array<string, mixed>
     */
    public function create(): array
    {
        $this->assertAllowedEnvironment();

        return DB::transaction(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            app(ConsultationSpecialtySeeder::class)->run();
            app(ConsultationSpecialtyOrderSetSeeder::class)->run();

            $doctorRole = $this->role('Specialist E2E Doctor', $this->doctorPermissions());
            $adminRole = $this->role('Specialist E2E Admin', $this->adminPermissions());
            $admin = $this->admin($adminRole);

            $profiles = collect($this->profileDefinitions())
                ->map(fn (array $definition) => $this->profileFixture($definition, $doctorRole))
                ->values()
                ->all();

            $metadata = [
                'login_url' => route('login', absolute: false),
                'admin' => [
                    'email' => self::ADMIN_EMAIL,
                    'password' => self::PASSWORD,
                    'report_url' => route('admin.reports.consultation-specialties.index', absolute: false),
                    'profiles_url' => route('admin.consultation-specialties.index', absolute: false),
                    'user_id' => $admin->id,
                ],
                'profiles' => $profiles,
                'metadata_path' => $this->metadataPath(),
            ];

            $this->writeMetadata($metadata);

            return $metadata;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function profileDefinitions(): array
    {
        return [
            $this->definition('general_medicine', 'General Medicine', 'E2ESGEN', DepartmentType::CONSULTATION, ['complaints', 'hopc', 'examination', 'diagnosis'], ['complaints', 'generate_summary'], 'complaints', [], 'general medicine'),
            $this->definition('physiotherapy', 'Physiotherapy', 'E2ESPHY', DepartmentType::TREATMENT, ['complaints', 'pain_assessment', 'physical_assessment', 'treatment_plan'], ['pain_assessment', 'treatment_plan', 'order_sets'], 'pain_assessment', ['pain_score' => 6, 'pain_location' => 'E2E lower back pain'], 'E2E lower back pain'),
            $this->definition('ophthalmology', 'Ophthalmology', 'E2ESEYE', DepartmentType::CONSULTATION, ['complaints', 'visual_acuity', 'eye_examination', 'diagnosis'], ['visual_acuity', 'iop', 'order_sets'], 'visual_acuity', ['right_eye_unaided' => '6/9', 'left_eye_unaided' => '6/12'], '6/9'),
            $this->definition('dental', 'Dental', 'E2ESDEN', DepartmentType::CONSULTATION, ['complaints', 'tooth_chart', 'oral_examination', 'consent'], ['tooth_chart', 'consent', 'order_sets'], 'tooth_chart', ['tooth_number' => '36', 'condition' => 'E2E caries'], 'E2E caries'),
            $this->definition('obstetrics', 'Obstetrics', 'E2ESOBS', DepartmentType::CONSULTATION, ['obstetric_history', 'current_pregnancy', 'fetal_assessment', 'birth_plan'], ['obstetric_history', 'fetal_assessment', 'order_sets'], 'antenatal_vitals', ['blood_pressure' => '120/80', 'weight' => 72], '120/80'),
            $this->definition('gynecology', 'Gynecology', 'E2ESGYN', DepartmentType::CONSULTATION, ['complaints', 'menstrual_history', 'pelvic_examination', 'breast_examination'], ['gyne_complaint', 'pelvic_examination', 'order_sets'], 'menstrual_history', ['cycle_length' => '28 days', 'bleeding_pattern' => 'E2E regular'], 'E2E regular'),
            $this->definition('ent', 'ENT', 'E2ESENT', DepartmentType::CONSULTATION, ['complaints', 'ear_assessment', 'nose_assessment', 'throat_assessment'], ['ent_complaint', 'ear_assessment', 'order_sets'], 'ear_assessment', ['ear_pain' => true, 'otoscopy_right' => 'E2E inflamed canal'], 'E2E inflamed canal'),
            $this->definition('pediatrics', 'Pediatrics', 'E2ESPED', DepartmentType::CONSULTATION, ['complaints', 'growth_assessment', 'immunization_status', 'caregiver_instructions'], ['pediatric_complaint', 'growth_assessment', 'order_sets'], 'growth_assessment', ['weight' => 18.5, 'growth_concern' => 'E2E no growth concern'], 'E2E no growth concern'),
            $this->definition('emergency', 'Emergency', 'E2ESEMR', DepartmentType::EMERGENCY, ['triage_summary', 'complaints', 'primary_survey', 'vitals_monitoring'], ['triage_summary', 'primary_survey', 'handover'], 'primary_survey', ['airway' => 'E2E patent airway', 'breathing' => 'Spontaneous', 'gcs' => 15], 'E2E patent airway'),
            $this->definition('orthopedics', 'Orthopedics', 'E2ESORT', DepartmentType::CONSULTATION, ['complaints', 'joint_limb_examination', 'neurovascular_status', 'cast_splint_plan'], ['ortho_complaint', 'neurovascular_status', 'order_sets'], 'neurovascular_status', ['pulse_present' => true, 'capillary_refill' => 'Less than 2 seconds'], 'Less than 2 seconds'),
            $this->definition('surgery', 'Surgery', 'E2ESSUR', DepartmentType::CONSULTATION, ['complaints', 'wound_assessment', 'local_or_abdominal_exam', 'consent'], ['surgical_complaint', 'procedures', 'order_sets'], 'consent', ['consent_required' => true, 'consent_obtained' => true, 'consent_type' => 'E2E debridement'], 'E2E debridement'),
        ];
    }

    /**
     * @return list<string>
     */
    public function doctorPermissions(): array
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
            'invoices.create',
        ];
    }

    /**
     * @return list<string>
     */
    public function adminPermissions(): array
    {
        return [
            'reports.view',
            'reports.export',
            'consultation-specialties.view',
            'consultation-specialties.configure',
            'consultation-specialties.update',
            'consultation-specialties.create',
            'consultations.view',
            'consultations.create',
        ];
    }

    public function metadataPath(): string
    {
        return storage_path(self::METADATA_PATH);
    }

    private function profileFixture(array $definition, Role $doctorRole): array
    {
        $profile = ConsultationSpecialtyProfile::query()->byCode($definition['code'])->firstOrFail();
        $department = $this->department($definition);
        $doctor = $this->doctor($definition, $department, $doctorRole);
        $service = $this->service($definition, $department);
        $patient = $this->patient($definition, $doctor);
        $visit = $this->visit($definition, $patient, $doctor, $department);
        $route = $this->route($visit, $patient, $doctor, $department, $service);
        $record = $this->medicalRecord($route, $visit, $patient, $doctor, $department, $service);

        ConsultationSpecialtyProfileMapping::query()->updateOrCreate(
            [
                'consultation_specialty_profile_id' => $profile->id,
                'department_id' => $department->id,
            ],
            [
                'source' => 'specialist_e2e_fixture',
                'priority' => 500,
                'is_active' => true,
            ],
        );

        foreach ($this->entriesFor($definition) as $section => $entry) {
            ConsultationSpecialtyEntry::query()->updateOrCreate(
                [
                    'consultation_id' => $route->id,
                    'consultation_specialty_profile_id' => $profile->id,
                    'section_key' => $section,
                ],
                [
                    'entry' => $entry,
                    'created_by' => $doctor->id,
                    'updated_by' => $doctor->id,
                ],
            );
        }

        return [
            'login_url' => route('login', absolute: false),
            'workspace_url' => route('admin.consultations.routes.show', [$visit, $route], false),
            'workspace_absolute_url' => route('admin.consultations.routes.show', [$visit, $route]),
            'profile_code' => $profile->code,
            'profile_label' => $profile->translatedName(),
            'doctor_email' => $doctor->email,
            'doctor_password' => self::PASSWORD,
            'patient_number' => $patient->patient_number,
            'visit_id' => $visit->id,
            'visit_number' => $visit->visit_number,
            'route_id' => $route->id,
            'medical_record_id' => $record->id,
            'department_id' => $department->id,
            'department_name' => $department->name,
            'expected_sections' => $definition['expected_sections'],
            'expected_quick_actions' => $definition['expected_actions'],
            'expected_structured_section' => $definition['representative_section'],
            'expected_reload_text' => $definition['reload_text'],
            'save_probe' => $this->saveProbe($definition),
        ];
    }

    private function role(string $name, array $permissions): Role
    {
        $role = Role::findOrCreate($name, 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $role;
    }

    private function admin(Role $role): User
    {
        $admin = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'first_name' => 'Specialist',
                'last_name' => 'Admin E2E',
                'password' => Hash::make(self::PASSWORD),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([$role]);

        return $admin;
    }

    private function doctor(array $definition, Department $department, Role $role): User
    {
        $doctor = User::query()->updateOrCreate(
            ['email' => 'specialist.'.$definition['code'].'.e2e@uhms.test'],
            [
                'first_name' => str($definition['label'])->headline()->toString(),
                'last_name' => 'E2E Doctor',
                'password' => Hash::make(self::PASSWORD),
                'status' => 'active',
                'department_id' => $department->id,
                'email_verified_at' => now(),
            ],
        );
        $doctor->syncRoles([$role]);
        $doctor->departments()->syncWithoutDetaching([
            $department->id => ['is_primary' => true, 'role_context' => 'specialist_e2e'],
        ]);

        return $doctor;
    }

    private function department(array $definition): Department
    {
        return Department::query()->updateOrCreate(
            ['code' => $definition['department_code']],
            [
                'name' => 'E2E '.$definition['label'],
                'type' => $definition['department_type']->value,
                'status' => 'active',
                'description' => 'Opt-in specialist workspace E2E fixture department.',
            ],
        );
    }

    private function service(array $definition, Department $department): ServiceCatalog
    {
        return ServiceCatalog::query()->updateOrCreate(
            ['code' => 'E2E-SVC-'.strtoupper(str_replace('_', '-', $definition['code']))],
            [
                'name' => 'E2E '.$definition['label'].' Consultation',
                'category' => ServiceType::CONSULTATION->value,
                'department_id' => $department->id,
                'department_type' => $definition['department_type']->value,
                'price' => 100,
                'is_active' => true,
                'is_billable' => true,
            ],
        );
    }

    private function patient(array $definition, User $doctor): Patient
    {
        return Patient::query()->updateOrCreate(
            ['patient_number' => 'E2E-SPECIALIST-'.strtoupper(str_replace('_', '-', $definition['code']))],
            [
                'first_name' => 'E2E',
                'last_name' => $definition['label'],
                'date_of_birth' => $definition['code'] === 'pediatrics' ? now()->subYears(6)->toDateString() : '1990-01-01',
                'gender' => in_array($definition['code'], ['obstetrics', 'gynecology'], true) ? Gender::FEMALE : Gender::MALE,
                'phone' => '024'.str_pad((string) (crc32($definition['code']) % 10000000), 7, '0', STR_PAD_LEFT),
                'status' => 'active',
                'is_active' => true,
                'registered_by' => $doctor->id,
            ],
        );
    }

    private function visit(array $definition, Patient $patient, User $doctor, Department $department): Visit
    {
        return Visit::query()->updateOrCreate(
            ['visit_number' => 'E2E-SPECIALIST-VISIT-'.strtoupper(str_replace('_', '-', $definition['code']))],
            [
                'patient_id' => $patient->id,
                'visit_type' => $definition['department_type'] === DepartmentType::EMERGENCY ? VisitType::EMERGENCY : VisitType::OUTPATIENT,
                'visit_date' => today(),
                'status' => VisitStatus::CONSULTING,
                'priority' => $definition['department_type'] === DepartmentType::EMERGENCY ? Priority::URGENT : Priority::NORMAL,
                'current_department_id' => $department->id,
                'chief_complaint' => 'Opt-in E2E '.$definition['label'].' workspace fixture',
                'created_by' => $doctor->id,
                'checked_in_at' => now(),
                'locked_at' => null,
                'locked_by' => null,
                'lock_reason' => null,
            ],
        );
    }

    private function route(Visit $visit, Patient $patient, User $doctor, Department $department, ServiceCatalog $service): VisitConsultationRoute
    {
        $route = VisitConsultationRoute::firstOrNew([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
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
            'notes' => 'Opt-in specialist workspace E2E route.',
        ])->save();

        return $route;
    }

    private function medicalRecord(VisitConsultationRoute $route, Visit $visit, Patient $patient, User $doctor, Department $department, ServiceCatalog $service): MedicalRecord
    {
        return MedicalRecord::query()->updateOrCreate(
            ['visit_id' => $visit->id],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'department_id' => $department->id,
                'service_id' => $service->id,
                'consultation_route_id' => $route->id,
            ],
        );
    }

    private function definition(
        string $code,
        string $label,
        string $departmentCode,
        DepartmentType $departmentType,
        array $expectedSections,
        array $expectedActions,
        string $representativeSection,
        array $representativePayload,
        string $reloadText
    ): array {
        return compact('code', 'label', 'departmentCode', 'departmentType') + [
            'department_code' => $departmentCode,
            'department_type' => $departmentType,
            'expected_sections' => $expectedSections,
            'expected_actions' => $expectedActions,
            'representative_section' => $representativeSection,
            'representative_payload' => $representativePayload,
            'reload_text' => $reloadText,
        ];
    }

    private function entriesFor(array $definition): array
    {
        if ($definition['code'] === ConsultationSpecialtyProfile::GENERAL_MEDICINE) {
            return [];
        }

        $entries = [$definition['representative_section'] => $definition['representative_payload']];

        if ($definition['code'] === 'physiotherapy') {
            $entries['presenting_problem'] = ['problem_description' => 'E2E incomplete readiness case'];
        }

        return $entries;
    }

    private function saveProbe(array $definition): array
    {
        $section = $definition['representative_section'];
        $probe = match ($section) {
            'pain_assessment' => ['field' => 'pain_location', 'value' => 'E2E saved lumbar probe', 'values' => ['pain_score' => '4', 'pain_location' => 'E2E saved lumbar probe', 'pain_character' => 'Mechanical']],
            'visual_acuity' => ['field' => 'right_eye_unaided', 'value' => '6/18', 'values' => ['right_eye_unaided' => '6/18', 'left_eye_unaided' => '6/12']],
            'tooth_chart' => ['field' => 'condition', 'value' => 'E2E restored probe', 'values' => ['tooth_number' => '36', 'condition' => 'E2E restored probe']],
            'antenatal_vitals' => ['field' => 'blood_pressure', 'value' => '118/74', 'values' => ['blood_pressure' => '118/74', 'weight' => '73']],
            'menstrual_history' => ['field' => 'cycle_length', 'value' => '30 days', 'values' => ['cycle_length' => '30 days', 'flow' => 'Moderate']],
            'ear_assessment' => ['field' => 'otoscopy_right', 'value' => 'E2E clear probe', 'values' => ['otoscopy_right' => 'E2E clear probe', 'otoscopy_left' => 'Clear']],
            'growth_assessment' => ['field' => 'growth_concern', 'value' => 'E2E thriving probe', 'values' => ['weight' => '18', 'height' => '104', 'growth_concern' => 'E2E thriving probe']],
            'primary_survey' => ['field' => 'airway', 'value' => 'E2E airway reassessed', 'values' => ['airway' => 'E2E airway reassessed', 'breathing' => 'Spontaneous', 'circulation' => 'Warm peripheries']],
            'neurovascular_status' => ['field' => 'capillary_refill', 'value' => 'E2E brisk', 'values' => ['capillary_refill' => 'E2E brisk', 'distal_pulses' => 'Present']],
            'consent' => ['field' => 'consent_type', 'value' => 'E2E consent probe', 'values' => ['consent_required' => '1', 'consent_obtained' => '1', 'consent_type' => 'E2E consent probe']],
            default => ['field' => null, 'value' => null, 'values' => []],
        };

        return ['section' => $section] + $probe;
    }

    private function writeMetadata(array $metadata): void
    {
        File::ensureDirectoryExists(dirname($this->metadataPath()));
        File::put($this->metadataPath(), json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function assertAllowedEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Consultation specialty E2E fixture is only available locally or in testing.');
        }
    }
}
