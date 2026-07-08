<?php

namespace Database\Seeders;

use App\Models\ConsultationSpecialtyFavorite;
use App\Models\ConsultationSpecialtyProfile;
use Illuminate\Database\Seeder;

class ConsultationSpecialtyFavoriteSeeder extends Seeder
{
    /**
     * @var array<string, array<string, array<int, string|array{label:string,code?:string,description?:string}>>>
     */
    private array $favorites = [
        'physiotherapy' => [
            'diagnosis' => [
                'Low back pain', 'Neck pain', 'Knee pain', 'Shoulder stiffness',
                'Stroke rehabilitation', 'Post-fracture rehabilitation', 'Sports injury',
                'Gait abnormality', 'Muscle weakness', 'Joint stiffness',
            ],
            'procedure' => [
                'Physiotherapy assessment', 'Therapeutic exercise', 'Manual therapy',
                'Gait training', 'Range of motion exercise', 'Strengthening exercise',
                'Electrotherapy', 'Heat therapy', 'Post-operative rehabilitation',
                'Home exercise instruction',
            ],
            'frequency' => [
                ['label' => 'Once daily', 'code' => 'OD'],
                ['label' => 'Twice weekly', 'code' => 'TWICE_WEEKLY'],
                ['label' => 'Three times weekly', 'code' => 'THREE_TIMES_WEEKLY'],
                ['label' => 'Weekly', 'code' => 'WEEKLY'],
                ['label' => 'Every 2 weeks', 'code' => 'Q2W'],
                ['label' => 'Review in 2 weeks', 'code' => 'REVIEW_2W'],
            ],
            'task' => [
                'Perform therapy session', 'Review pain score', 'Review range of motion',
                'Review home exercise compliance', 'Schedule next physiotherapy session',
                'Reassess functional goal',
            ],
            'follow_up_instruction' => [
                'Continue home exercises as instructed.',
                'Avoid activities that worsen pain.',
                'Return earlier if pain, weakness, or numbness worsens.',
                'Apply heat or cold as advised.',
                'Attend all scheduled therapy sessions.',
            ],
        ],
        'ophthalmology' => [
            'diagnosis' => [
                'Conjunctivitis', 'Cataract', 'Glaucoma', 'Refractive error',
                'Dry eye syndrome', 'Eye trauma', 'Corneal abrasion', 'Uveitis',
                'Diabetic retinopathy', 'Foreign body in eye',
            ],
            'investigation' => [
                'Visual acuity test', 'Refraction', 'Intraocular pressure measurement',
                'Slit lamp examination', 'Fundus examination', 'OCT', 'Visual field test',
                'Fundus photography', 'Eye ultrasound', 'Fluorescein staining',
            ],
            'procedure' => [
                'Foreign body removal', 'Eye dressing', 'Eye irrigation',
                'Refraction procedure', 'Slit lamp examination', 'Fundus examination',
                'Tonometry',
            ],
            'drug' => [
                'Lubricating eye drops', 'Antibiotic eye drops', 'Anti-allergy eye drops',
                'Steroid eye drops', 'Anti-glaucoma eye drops', 'Eye ointment',
                'Oral analgesic',
            ],
            'frequency' => [
                ['label' => 'Once daily', 'code' => 'OD'],
                ['label' => 'Twice daily', 'code' => 'BD'],
                ['label' => 'Three times daily', 'code' => 'TDS'],
                ['label' => 'Four times daily', 'code' => 'QDS'],
                ['label' => 'Every 4 hours', 'code' => 'Q4H'],
                ['label' => 'At night', 'code' => 'QHS'],
                ['label' => 'As needed', 'code' => 'PRN'],
                ['label' => 'Review in 3 days', 'code' => 'REVIEW_3D'],
                ['label' => 'Review in 1 week', 'code' => 'REVIEW_1W'],
            ],
            'follow_up_instruction' => [
                'Avoid rubbing the eye.',
                'Return immediately if vision worsens.',
                'Return immediately if severe pain develops.',
                'Use eye drops as prescribed.',
                'Avoid sharing towels or eye cosmetics.',
                'Attend follow-up for eye pressure or vision review.',
            ],
        ],
        'dental' => [
            'diagnosis' => [
                'Dental caries', 'Pulpitis', 'Periodontitis', 'Dental abscess',
                'Gingivitis', 'Impacted tooth', 'Tooth fracture', 'Pericoronitis',
                'Oral ulcer', 'Malocclusion',
            ],
            'investigation' => [
                'Periapical X-ray', 'Panoramic X-ray', 'OPG', 'Bitewing X-ray',
                'Dental vitality test', 'Dental examination',
            ],
            'procedure' => [
                'Tooth extraction', 'Dental filling', 'Scaling and polishing',
                'Root canal treatment', 'Dental dressing', 'Incision and drainage',
                'Dental review', 'Oral hygiene instruction',
            ],
            'drug' => [
                'Oral analgesic', 'Antibiotic', 'Antiseptic mouthwash',
                'Anti-inflammatory medicine', 'Local anaesthetic',
            ],
            'frequency' => [
                ['label' => 'Once daily', 'code' => 'OD'],
                ['label' => 'Twice daily', 'code' => 'BD'],
                ['label' => 'Three times daily', 'code' => 'TDS'],
                ['label' => 'Every 8 hours', 'code' => 'Q8H'],
                ['label' => 'Every 12 hours', 'code' => 'Q12H'],
                ['label' => 'After meals', 'code' => 'PC'],
                ['label' => 'As needed', 'code' => 'PRN'],
                ['label' => 'Review in 3 days', 'code' => 'REVIEW_3D'],
                ['label' => 'Review in 1 week', 'code' => 'REVIEW_1W'],
            ],
            'follow_up_instruction' => [
                'Do not rinse mouth vigorously for 24 hours after extraction.',
                'Bite on gauze as instructed.',
                'Avoid hot food and drinks after extraction.',
                'Return if bleeding persists.',
                'Return if swelling or fever develops.',
                'Maintain oral hygiene as advised.',
            ],
        ],
        'obstetrics' => [
            'diagnosis' => ['Normal pregnancy', 'High-risk pregnancy', 'Anaemia in pregnancy', 'Hypertension in pregnancy', 'Pre-eclampsia', 'Threatened miscarriage', 'Post-term pregnancy', 'Reduced fetal movement'],
            'investigation' => ['Obstetric ultrasound', 'Full blood count', 'Blood group and rhesus', 'Urinalysis', 'HIV screening', 'HBsAg', 'Syphilis screening', 'Malaria test'],
            'frequency' => [['label' => 'Review in 2 weeks', 'code' => 'REVIEW_2W'], ['label' => 'Review in 4 weeks', 'code' => 'REVIEW_4W']],
            'follow_up_instruction' => ['Return immediately if bleeding occurs.', 'Return immediately if severe headache or blurred vision occurs.', 'Return immediately if fetal movement reduces.'],
        ],
        'gynecology' => [
            'diagnosis' => ['Abnormal uterine bleeding', 'Pelvic inflammatory disease', 'Dysmenorrhea', 'Vaginal discharge', 'Infertility review', 'Contraception counselling'],
            'investigation' => ['Pregnancy test', 'Pelvic ultrasound', 'Pap smear', 'STI screening'],
            'procedure' => ['Pelvic examination', 'Contraception counselling'],
        ],
        'ent' => [
            'diagnosis' => ['Otitis media', 'Otitis externa', 'Wax impaction', 'Tonsillitis', 'Sinusitis', 'Allergic rhinitis', 'Epistaxis', 'Hearing loss'],
            'procedure' => ['Ear syringing', 'Audiometry', 'Nasal packing', 'Throat examination'],
            'investigation' => ['Audiometry', 'Tympanometry', 'Ear swab', 'Sinus X-ray', 'CT sinuses'],
        ],
        'pediatrics' => [
            'diagnosis' => ['Fever in child', 'Acute respiratory infection', 'Diarrhea', 'Dehydration', 'Malnutrition', 'Immunization review'],
            'procedure' => ['Growth monitoring', 'Danger signs counselling'],
            'follow_up_instruction' => ['ORS instructions', 'Review in 48 hours', 'Return immediately if child is unable to feed or convulses.'],
        ],
        'emergency' => [
            'diagnosis' => ['Trauma assessment', 'Chest pain', 'Severe asthma', 'Seizure', 'Hypoglycemia', 'Dehydration', 'Shock'],
            'investigation' => ['Urgent FBC', 'Urgent malaria test', 'Urgent X-ray', 'Blood glucose', 'ECG'],
            'procedure' => ['Oxygen therapy', 'IV fluids'],
            'task' => ['Repeat vital signs', 'Prepare handover'],
        ],
        'orthopedics' => [
            'diagnosis' => ['Fracture', 'Sprain', 'Dislocation', 'Back pain', 'Knee pain', 'Shoulder pain'],
            'investigation' => ['X-ray', 'CT scan', 'MRI'],
            'procedure' => ['Cast application', 'Splinting', 'Physiotherapy referral'],
        ],
        'surgery' => [
            'diagnosis' => ['Wound infection', 'Abscess', 'Hernia', 'Appendicitis review', 'Post-operative review'],
            'procedure' => ['Wound dressing', 'Incision and drainage', 'Theatre referral'],
            'clinical_instruction' => ['Consent required'],
        ],
        'general_medicine' => [
            'frequency' => [
                ['label' => 'Review in 1 week', 'code' => 'REVIEW_1W'],
                ['label' => 'Review in 2 weeks', 'code' => 'REVIEW_2W'],
                ['label' => 'As needed', 'code' => 'PRN'],
                ['label' => 'Once daily', 'code' => 'OD'],
                ['label' => 'Twice daily', 'code' => 'BD'],
                ['label' => 'Three times daily', 'code' => 'TDS'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->favorites as $profileCode => $groups) {
            $profile = ConsultationSpecialtyProfile::query()->byCode($profileCode)->first();
            if (! $profile) {
                continue;
            }

            foreach ($groups as $type => $items) {
                foreach ($items as $index => $item) {
                    $payload = is_array($item) ? $item : ['label' => $item];
                    $code = $payload['code'] ?? $this->codeFor($type, $payload['label']);

                    ConsultationSpecialtyFavorite::query()->updateOrCreate(
                        [
                            'consultation_specialty_profile_id' => $profile->id,
                            'favorite_type' => $type,
                            'code' => $code,
                        ],
                        [
                            'label' => $payload['label'],
                            'description' => $payload['description'] ?? null,
                            'search_terms' => $payload['label'],
                            'metadata' => ['seeded' => true, 'profile' => $profileCode],
                            'sort_order' => ($index + 1) * 10,
                            'is_active' => true,
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
