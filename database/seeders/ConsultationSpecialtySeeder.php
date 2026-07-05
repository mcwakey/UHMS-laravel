<?php

namespace Database\Seeders;

use App\Enums\DepartmentType;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyProfileMapping;
use App\Models\Department;
use Illuminate\Database\Seeder;

class ConsultationSpecialtySeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, description: string, department_type: string, icon: string, color: string, sort_order: int, sections: array<int, string>}>
     */
    private array $profiles = [
        'general_medicine' => [
            'name' => 'General Medicine',
            'description' => 'Default general consultation workspace profile.',
            'department_type' => DepartmentType::CONSULTATION->value,
            'icon' => 'ti-stethoscope',
            'color' => 'primary',
            'sort_order' => 0,
            'sections' => [
                'patient_summary',
                'complaints',
                'hopc',
                'examination',
                'diagnosis',
                'investigations',
                'prescription',
                'procedures',
                'tasks',
                'notes',
                'summary',
                'completion_readiness',
            ],
        ],
        'physiotherapy' => [
            'name' => 'Physiotherapy',
            'description' => 'Specialty profile foundation for physiotherapy consultations.',
            'department_type' => DepartmentType::TREATMENT->value,
            'icon' => 'ti-stretching',
            'color' => 'success',
            'sort_order' => 10,
            'sections' => [
                'patient_summary',
                'presenting_problem',
                'pain_assessment',
                'functional_limitation',
                'physical_assessment',
                'treatment_plan',
                'therapy_session',
                'home_exercise_plan',
                'tasks',
                'progress_notes',
                'summary',
                'completion_readiness',
            ],
        ],
        'ophthalmology' => [
            'name' => 'Ophthalmology',
            'description' => 'Specialty profile foundation for eye clinic consultations.',
            'department_type' => DepartmentType::CONSULTATION->value,
            'icon' => 'ti-eye',
            'color' => 'info',
            'sort_order' => 20,
            'sections' => [
                'patient_summary',
                'eye_complaint',
                'visual_acuity',
                'refraction',
                'iop',
                'eye_examination',
                'diagnosis',
                'investigations',
                'procedures',
                'prescription',
                'follow_up',
                'summary',
                'completion_readiness',
            ],
        ],
        'dental' => [
            'name' => 'Dental',
            'description' => 'Specialty profile foundation for dental consultations.',
            'department_type' => DepartmentType::CONSULTATION->value,
            'icon' => 'ti-dental',
            'color' => 'warning',
            'sort_order' => 30,
            'sections' => [
                'patient_summary',
                'dental_complaint',
                'tooth_chart',
                'oral_examination',
                'dental_diagnosis',
                'dental_xray',
                'dental_procedures',
                'consent',
                'prescription',
                'follow_up',
                'summary',
                'completion_readiness',
            ],
        ],
    ];

    public function run(): void
    {
        $seededProfiles = [];

        foreach ($this->profiles as $code => $definition) {
            $profile = ConsultationSpecialtyProfile::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'department_type' => $definition['department_type'],
                    'icon' => $definition['icon'],
                    'color' => $definition['color'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );
            $seededProfiles[$code] = $profile;

            foreach ($definition['sections'] as $index => $sectionKey) {
                $profile->sections()->updateOrCreate(
                    ['section_key' => $sectionKey],
                    [
                        'label' => $this->labelFor($sectionKey),
                        'display_order' => ($index + 1) * 10,
                        'is_required' => $sectionKey === 'patient_summary',
                        'is_visible' => true,
                    ],
                );
            }
        }

        $this->seedMappings($seededProfiles);
    }

    private function labelFor(string $sectionKey): string
    {
        return str($sectionKey)->replace('_', ' ')->title()->toString();
    }

    /**
     * @param  array<string, ConsultationSpecialtyProfile>  $profiles
     */
    private function seedMappings(array $profiles): void
    {
        $general = $profiles['general_medicine'] ?? null;
        if ($general) {
            ConsultationSpecialtyProfileMapping::query()->updateOrCreate(
                [
                    'department_type' => DepartmentType::CONSULTATION->value,
                    'source' => 'seed_department_type',
                ],
                [
                    'consultation_specialty_profile_id' => $general->id,
                    'department_id' => null,
                    'consultation_route_id' => null,
                    'user_id' => null,
                    'priority' => 0,
                    'is_active' => true,
                    'metadata' => ['phase' => 'specialist_consultation_phase_2'],
                ],
            );
        }

        $departmentHints = [
            'physiotherapy' => ['codes' => ['PHY', 'PHT'], 'names' => ['physiotherapy', 'physio']],
            'ophthalmology' => ['codes' => ['EYE', 'OPH'], 'names' => ['ophthalmology', 'eye']],
            'dental' => ['codes' => ['DEN', 'DENT'], 'names' => ['dental']],
        ];

        foreach ($departmentHints as $profileCode => $hints) {
            $profile = $profiles[$profileCode] ?? null;
            if (! $profile) {
                continue;
            }

            Department::query()
                ->where(function ($query) use ($hints) {
                    $query->whereIn('code', $hints['codes'])
                        ->orWhere(function ($nameQuery) use ($hints) {
                            foreach ($hints['names'] as $name) {
                                $nameQuery->orWhereRaw('LOWER(name) LIKE ?', ['%' . $name . '%']);
                            }
                        });
                })
                ->get()
                ->each(function (Department $department) use ($profile): void {
                    ConsultationSpecialtyProfileMapping::query()->updateOrCreate(
                        [
                            'department_id' => $department->id,
                            'source' => 'seed_department',
                        ],
                        [
                            'consultation_specialty_profile_id' => $profile->id,
                            'consultation_route_id' => null,
                            'department_type' => $department->type instanceof \BackedEnum ? $department->type->value : $department->type,
                            'user_id' => null,
                            'priority' => 10,
                            'is_active' => true,
                            'metadata' => ['phase' => 'specialist_consultation_phase_2'],
                        ],
                    );
                });
        }
    }
}
