<?php

namespace Database\Seeders;

use App\Enums\DepartmentType;
use App\Models\ConsultationSpecialtyProfile;
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
    }

    private function labelFor(string $sectionKey): string
    {
        return str($sectionKey)->replace('_', ' ')->title()->toString();
    }
}
