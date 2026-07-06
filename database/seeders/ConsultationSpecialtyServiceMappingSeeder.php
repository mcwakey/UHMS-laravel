<?php

namespace Database\Seeders;

use App\Models\ConsultationSpecialtyProfile;
use App\Models\ConsultationSpecialtyServiceMapping;
use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class ConsultationSpecialtyServiceMappingSeeder extends Seeder
{
    /**
     * @var array<string, array<int, array{context: string, section?: string, default?: bool, priority?: int, hints: array<int, string>}>>
     */
    private array $definitions = [
        'general_medicine' => [
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
                'default' => true,
                'hints' => ['General OPD Consultation', 'General Consultation', 'OPD Consultation'],
            ],
        ],
        'physiotherapy' => [
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
                'default' => true,
                'hints' => ['Physiotherapy Assessment', 'Physiotherapy Consultation', 'Physio Consultation'],
            ],
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_THERAPY_SESSION,
                'section' => 'therapy_session',
                'priority' => 10,
                'hints' => ['Physiotherapy Session', 'Physio Session', 'Therapy Session'],
            ],
        ],
        'ophthalmology' => [
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
                'default' => true,
                'hints' => ['Ophthalmology Consultation', 'Eye Consultation', 'Eye Clinic Consultation'],
            ],
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_INVESTIGATION,
                'section' => 'investigations',
                'priority' => 10,
                'hints' => ['Refraction', 'Visual Acuity', 'Eye Investigation'],
            ],
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_EYE_PROCEDURE,
                'section' => 'procedures',
                'priority' => 20,
                'hints' => ['Eye Procedure', 'Ophthalmic Procedure'],
            ],
        ],
        'dental' => [
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_CONSULTATION,
                'default' => true,
                'hints' => ['Dental Consultation', 'Dental OPD Consultation'],
            ],
            [
                'context' => ConsultationSpecialtyServiceMapping::CONTEXT_DENTAL_PROCEDURE,
                'section' => 'dental_procedures',
                'priority' => 10,
                'hints' => ['Dental Procedure', 'Tooth Extraction', 'Dental Extraction'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->definitions as $profileCode => $definitions) {
            $profile = ConsultationSpecialtyProfile::query()->byCode($profileCode)->first();

            if (! $profile) {
                continue;
            }

            foreach ($definitions as $definition) {
                $service = $this->findExistingService($definition['hints']);

                if (! $service) {
                    continue;
                }

                ConsultationSpecialtyServiceMapping::query()->updateOrCreate(
                    [
                        'consultation_specialty_profile_id' => $profile->id,
                        'service_id' => $service->id,
                        'mapping_context' => $definition['context'],
                        'department_id' => null,
                        'department_type' => null,
                        'consultation_route_id' => null,
                    ],
                    [
                        'section_key' => $definition['section'] ?? null,
                        'billing_trigger' => ConsultationSpecialtyServiceMapping::TRIGGER_MANUAL,
                        'priority' => $definition['priority'] ?? 0,
                        'is_default' => (bool) ($definition['default'] ?? false),
                        'auto_bill' => false,
                        'requires_confirmation' => true,
                        'is_active' => true,
                        'metadata' => [
                            'source' => 'consultation_specialty_service_mapping_seeder',
                            'hints' => $definition['hints'],
                            'safe_catalog_only' => true,
                        ],
                    ],
                );
            }
        }
    }

    /**
     * @param  array<int, string>  $hints
     */
    private function findExistingService(array $hints): ?ServiceCatalog
    {
        foreach ($hints as $hint) {
            $service = ServiceCatalog::query()
                ->where('is_active', true)
                ->where('is_billable', true)
                ->where(function ($query) use ($hint) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($hint)])
                        ->orWhereRaw('LOWER(code) = ?', [strtolower($hint)])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%'.strtolower($hint).'%']);
                })
                ->orderByRaw('LOWER(name) = ? DESC', [strtolower($hint)])
                ->orderBy('name')
                ->first();

            if ($service) {
                return $service;
            }
        }

        return null;
    }
}
