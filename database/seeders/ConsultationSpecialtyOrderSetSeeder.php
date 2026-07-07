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
        'obstetrics' => [
            [
                'code' => 'obstetrics_antenatal_booking',
                'name' => 'Antenatal Booking Review',
                'description' => 'Starter antenatal review bundle with screening and risk prompts.',
                'category' => 'antenatal',
                'icon' => 'ti-baby-carriage',
                'color' => 'danger',
                'items' => [
                    ['investigation', 'Full blood count'],
                    ['investigation', 'Blood group and rhesus'],
                    ['investigation', 'Obstetric ultrasound'],
                    ['specialty_entry_patch', 'Pregnancy confirmed', 'patch_specialty_entry', ['section_key' => 'current_pregnancy', 'merge' => ['pregnancy_confirmed' => true]]],
                    ['specialty_entry_patch', 'Danger signs counselling', 'patch_specialty_entry', ['section_key' => 'birth_plan', 'merge' => ['danger_signs_counseling' => true]]],
                    ['task', 'Review antenatal screening results', 'create_task', ['title' => 'Review antenatal screening results', 'frequency' => 'REVIEW_2W', 'priority' => 'medium']],
                ],
            ],
        ],
        'gynecology' => [
            [
                'code' => 'gyne_abnormal_bleeding',
                'name' => 'Abnormal Uterine Bleeding Review',
                'description' => 'Gynecology review bundle for abnormal bleeding.',
                'category' => 'gyne_review',
                'icon' => 'ti-calendar-heart',
                'color' => 'danger',
                'items' => [
                    ['diagnosis', 'Abnormal uterine bleeding'],
                    ['investigation', 'Pregnancy test'],
                    ['investigation', 'Pelvic ultrasound'],
                    ['specialty_entry_patch', 'Bleeding pattern prompt', 'patch_specialty_entry', ['section_key' => 'menstrual_history', 'merge' => ['bleeding_pattern' => 'Document bleeding pattern and severity']]],
                    ['task', 'Review investigation results', 'create_task', ['title' => 'Review gynecology investigation results', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                ],
            ],
        ],
        'ent' => [
            [
                'code' => 'ent_ear_pain',
                'name' => 'Ear Pain Assessment',
                'description' => 'ENT review bundle for ear pain and discharge.',
                'category' => 'ent_ear',
                'icon' => 'ti-ear',
                'color' => 'info',
                'items' => [
                    ['diagnosis', 'Otitis media'],
                    ['procedure', 'Ear examination'],
                    ['specialty_entry_patch', 'Ear pain present', 'patch_specialty_entry', ['section_key' => 'ear_assessment', 'merge' => ['ear_pain' => true]]],
                    ['task', 'Review ear symptoms', 'create_task', ['title' => 'Review ear symptoms', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
                ],
            ],
        ],
        'pediatrics' => [
            [
                'code' => 'peds_fever_review',
                'name' => 'Pediatric Fever Review',
                'description' => 'Pediatric review bundle for fever and danger signs.',
                'category' => 'acute_child',
                'icon' => 'ti-baby-bottle',
                'color' => 'success',
                'items' => [
                    ['diagnosis', 'Fever in child'],
                    ['investigation', 'Malaria test'],
                    ['specialty_entry_patch', 'Caregiver danger signs', 'patch_specialty_entry', ['section_key' => 'caregiver_instructions', 'merge' => ['danger_signs' => 'Return immediately if convulsions, poor feeding, lethargy, or breathing difficulty occur.']]],
                    ['task', 'Review child in 48 hours', 'create_task', ['title' => 'Review child in 48 hours', 'frequency' => 'REVIEW_48H', 'priority' => 'medium']],
                ],
            ],
        ],
        'emergency' => [
            [
                'code' => 'emergency_primary_survey',
                'name' => 'Primary Survey Stabilisation',
                'description' => 'Emergency bundle for ABCDE review and stabilisation tasks.',
                'category' => 'emergency',
                'icon' => 'ti-urgent',
                'color' => 'danger',
                'items' => [
                    ['specialty_entry_patch', 'ABCDE prompt', 'patch_specialty_entry', ['section_key' => 'primary_survey', 'merge' => ['airway' => 'Assess airway', 'breathing' => 'Assess breathing', 'circulation' => 'Assess circulation']]],
                    ['procedure', 'Oxygen therapy'],
                    ['procedure', 'IV fluids'],
                    ['task', 'Repeat vital signs', 'create_task', ['title' => 'Repeat vital signs', 'frequency' => 'Q15MIN', 'priority' => 'high']],
                ],
            ],
        ],
        'orthopedics' => [
            [
                'code' => 'ortho_fracture_review',
                'name' => 'Fracture Review',
                'description' => 'Orthopedic bundle for suspected fracture review.',
                'category' => 'orthopedics',
                'icon' => 'ti-bone',
                'color' => 'secondary',
                'items' => [
                    ['diagnosis', 'Fracture'],
                    ['investigation', 'X-ray'],
                    ['specialty_entry_patch', 'Neurovascular check', 'patch_specialty_entry', ['section_key' => 'neurovascular_status', 'merge' => ['neurovascular_notes' => 'Document distal pulse, sensation, motor function, and capillary refill']]],
                    ['task', 'Review X-ray', 'create_task', ['title' => 'Review X-ray', 'frequency' => 'REVIEW_1D', 'priority' => 'medium']],
                ],
            ],
        ],
        'surgery' => [
            [
                'code' => 'surgery_wound_review',
                'name' => 'Wound Review',
                'description' => 'Surgical OPD bundle for wound assessment and dressing review.',
                'category' => 'surgical_opd',
                'icon' => 'ti-scalpel',
                'color' => 'primary',
                'items' => [
                    ['diagnosis', 'Wound infection'],
                    ['procedure', 'Wound dressing'],
                    ['specialty_entry_patch', 'Wound care instructions', 'patch_specialty_entry', ['section_key' => 'post_op_instructions', 'merge' => ['wound_care' => 'Keep wound clean and dry. Return if redness, swelling, fever, or discharge worsens.']]],
                    ['task', 'Review wound', 'create_task', ['title' => 'Review wound', 'frequency' => 'REVIEW_1W', 'priority' => 'medium']],
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
