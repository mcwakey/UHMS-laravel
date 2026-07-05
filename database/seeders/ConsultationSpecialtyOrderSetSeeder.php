<?php

namespace Database\Seeders;

use App\Models\ConsultationSpecialtyOrderSet;
use App\Models\ConsultationSpecialtyProfile;
use Illuminate\Database\Seeder;

class ConsultationSpecialtyOrderSetSeeder extends Seeder
{
    private array $orderSets = [
        'physiotherapy' => [
            [
                'code' => 'physio_low_back_pain',
                'name' => 'Low Back Pain Rehab',
                'description' => 'Common physiotherapy workflow for low back pain rehabilitation.',
                'category' => 'rehab',
                'icon' => 'ti-stretching',
                'color' => 'success',
                'items' => [
                    ['diagnosis', 'Low back pain'],
                    ['specialty_entry_patch', 'Presenting problem hint', 'patch_specialty_entry', ['section_key' => 'presenting_problem', 'merge' => ['problem_description' => 'Low back pain affecting function']]],
                    ['specialty_entry_patch', 'Pain location: lower back', 'patch_specialty_entry', ['section_key' => 'pain_assessment', 'merge' => ['pain_location' => 'Lower back']]],
                    ['specialty_entry_patch', 'Treatment frequency: three times weekly', 'patch_specialty_entry', ['section_key' => 'treatment_plan', 'merge' => ['session_frequency' => 'Three times weekly']]],
                    ['specialty_entry_patch', 'Treatment sessions: 6', 'patch_specialty_entry', ['section_key' => 'treatment_plan', 'merge' => ['number_of_sessions' => 6]]],
                    ['task', 'Perform therapy session', 'create_task', ['title' => 'Perform therapy session', 'frequency' => 'THREE_TIMES_WEEKLY', 'priority' => 'medium']],
                    ['task', 'Review pain score', 'create_task', ['title' => 'Review pain score', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                    ['follow_up_instruction', 'Continue home exercises as instructed.'],
                    ['follow_up_instruction', 'Avoid activities that worsen pain.'],
                ],
            ],
            [
                'code' => 'physio_stroke_rehab',
                'name' => 'Stroke Rehabilitation Review',
                'description' => 'Functional review bundle for stroke rehabilitation.',
                'category' => 'rehab',
                'icon' => 'ti-walk',
                'color' => 'success',
                'items' => [
                    ['diagnosis', 'Stroke rehabilitation'],
                    ['specialty_entry_patch', 'Functional goal hint', 'patch_specialty_entry', ['section_key' => 'functional_limitation', 'merge' => ['functional_goal' => 'Improve independence and mobility after stroke']]],
                    ['specialty_entry_patch', 'Gait assessment hint', 'patch_specialty_entry', ['section_key' => 'physical_assessment', 'merge' => ['gait' => 'Assess gait pattern, balance, and assistive device needs']]],
                    ['task', 'Review gait and balance', 'create_task', ['title' => 'Review gait and balance', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                    ['task', 'Reassess functional goal', 'create_task', ['title' => 'Reassess functional goal', 'frequency' => 'REVIEW_2W', 'priority' => 'medium']],
                    ['follow_up_instruction', 'Attend all scheduled therapy sessions.'],
                ],
            ],
        ],
        'ophthalmology' => [
            [
                'code' => 'eye_conjunctivitis',
                'name' => 'Conjunctivitis Care',
                'description' => 'Common eye clinic workflow for conjunctivitis care.',
                'category' => 'acute_eye',
                'icon' => 'ti-eye',
                'color' => 'info',
                'items' => [
                    ['diagnosis', 'Conjunctivitis'],
                    ['drug', 'Antibiotic eye drops'],
                    ['frequency', 'Four times daily'],
                    ['specialty_entry_patch', 'Follow-up reason', 'patch_specialty_entry', ['section_key' => 'follow_up', 'merge' => ['follow_up_reason' => 'Review eye redness/discharge']]],
                    ['specialty_entry_patch', 'Warning signs', 'patch_specialty_entry', ['section_key' => 'follow_up', 'merge' => ['warning_signs' => 'Return immediately if vision worsens or severe pain develops.']]],
                    ['follow_up_instruction', 'Avoid rubbing the eye.'],
                    ['follow_up_instruction', 'Use eye drops as prescribed.'],
                    ['task', 'Review in 3 days', 'create_task', ['title' => 'Review in 3 days', 'frequency' => 'REVIEW_3D', 'priority' => 'medium']],
                ],
            ],
            [
                'code' => 'eye_glaucoma_review',
                'name' => 'Glaucoma Review',
                'description' => 'Review bundle for glaucoma monitoring.',
                'category' => 'chronic_eye',
                'icon' => 'ti-eye-check',
                'color' => 'info',
                'items' => [
                    ['diagnosis', 'Glaucoma'],
                    ['investigation', 'Intraocular pressure measurement'],
                    ['investigation', 'Visual field test'],
                    ['investigation', 'OCT'],
                    ['specialty_entry_patch', 'IOP method: Tonometry', 'patch_specialty_entry', ['section_key' => 'iop', 'merge' => ['method' => 'Tonometry']]],
                    ['task', 'Review eye pressure', 'create_task', ['title' => 'Review eye pressure', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                    ['follow_up_instruction', 'Attend follow-up for eye pressure or vision review.'],
                ],
            ],
        ],
        'dental' => [
            [
                'code' => 'dental_extraction_prep',
                'name' => 'Dental Extraction Preparation',
                'description' => 'Preparation bundle for dental extraction review.',
                'category' => 'procedure_prep',
                'icon' => 'ti-dental',
                'color' => 'warning',
                'items' => [
                    ['diagnosis', 'Dental caries'],
                    ['investigation', 'Periapical X-ray'],
                    ['procedure', 'Tooth extraction'],
                    ['specialty_entry_patch', 'Consent required', 'patch_specialty_entry', ['section_key' => 'consent', 'merge' => ['consent_required' => true]]],
                    ['specialty_entry_patch', 'Consent type', 'patch_specialty_entry', ['section_key' => 'consent', 'merge' => ['consent_type' => 'Dental extraction consent']]],
                    ['drug', 'Oral analgesic'],
                    ['follow_up_instruction', 'Do not rinse mouth vigorously for 24 hours after extraction.'],
                    ['follow_up_instruction', 'Return if bleeding persists.'],
                    ['task', 'Dental review in 1 week', 'create_task', ['title' => 'Dental review in 1 week', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                ],
            ],
            [
                'code' => 'dental_abscess',
                'name' => 'Dental Abscess Care',
                'description' => 'Common dental abscess care suggestions.',
                'category' => 'acute_dental',
                'icon' => 'ti-dental-broken',
                'color' => 'warning',
                'items' => [
                    ['diagnosis', 'Dental abscess'],
                    ['investigation', 'Periapical X-ray'],
                    ['procedure', 'Incision and drainage'],
                    ['drug', 'Antibiotic'],
                    ['drug', 'Oral analgesic'],
                    ['follow_up_instruction', 'Return if swelling or fever develops.'],
                    ['task', 'Review in 3 days', 'create_task', ['title' => 'Review in 3 days', 'frequency' => 'REVIEW_3D', 'priority' => 'medium']],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->orderSets as $profileCode => $sets) {
            $profile = ConsultationSpecialtyProfile::query()->byCode($profileCode)->first();
            if (! $profile) {
                continue;
            }

            foreach ($sets as $setIndex => $definition) {
                $orderSet = ConsultationSpecialtyOrderSet::query()->updateOrCreate(
                    ['consultation_specialty_profile_id' => $profile->id, 'code' => $definition['code']],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'category' => $definition['category'],
                        'icon' => $definition['icon'],
                        'color' => $definition['color'],
                        'is_active' => true,
                        'sort_order' => ($setIndex + 1) * 10,
                        'metadata' => ['seeded' => true, 'profile' => $profileCode],
                    ],
                );

                foreach ($definition['items'] as $itemIndex => $item) {
                    [$type, $label, $applyMode, $payload] = array_pad($item, 4, null);
                    $applyMode ??= 'suggest';
                    $payload ??= ['label' => $label];

                    $orderSet->items()->updateOrCreate(
                        ['code' => $this->codeFor($type, $label)],
                        [
                            'item_type' => $type,
                            'label' => $label,
                            'description' => null,
                            'target_section' => $payload['section_key'] ?? null,
                            'target_field' => isset($payload['merge']) ? array_key_first($payload['merge']) : null,
                            'payload' => $payload,
                            'apply_mode' => $applyMode,
                            'is_required' => false,
                            'sort_order' => ($itemIndex + 1) * 10,
                            'is_active' => true,
                            'metadata' => ['seeded' => true],
                        ],
                    );
                }
            }
        }
    }

    private function codeFor(string $type, string $label): string
    {
        return $type.':'.str($label)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }
}
