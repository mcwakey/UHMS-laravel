<?php

namespace Database\Seeders;

use App\Models\ComplaintCatalogue;
use Illuminate\Database\Seeder;

class ComplaintCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 1;

        foreach ($this->complaints() as $category => $items) {
            foreach ($items as $name) {
                ComplaintCatalogue::updateOrCreate(
                    ['name' => $name, 'category' => $category],
                    [
                        'body_system' => $category,
                        'keywords' => $this->keywords($name, $category),
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ]
                );
            }
        }
    }

    private function complaints(): array
    {
        return [
            'General' => ['Fever', 'General weakness', 'Body pain', 'Fatigue', 'Loss of appetite', 'Weight loss', 'Night sweats', 'Malaise'],
            'Respiratory' => ['Cough', 'Shortness of breath', 'Chest tightness', 'Wheezing', 'Sore throat', 'Runny nose', 'Nasal congestion', 'Coughing blood'],
            'Cardiovascular' => ['Chest pain', 'Palpitations', 'Leg swelling', 'Fainting', 'Dizziness', 'High blood pressure complaint'],
            'Gastrointestinal' => ['Abdominal pain', 'Vomiting', 'Nausea', 'Diarrhea', 'Constipation', 'Heartburn', 'Blood in stool', 'Loss of appetite', 'Abdominal swelling'],
            'Neurological' => ['Headache', 'Convulsion', 'Loss of consciousness', 'Confusion', 'Weakness of limb', 'Numbness', 'Tremors', 'Dizziness'],
            'Genitourinary' => ['Painful urination', 'Frequent urination', 'Blood in urine', 'Flank pain', 'Urinary retention', 'Incontinence'],
            'Musculoskeletal' => ['Back pain', 'Joint pain', 'Neck pain', 'Limb pain', 'Swelling of joint', 'Difficulty walking', 'Trauma injury'],
            'Dermatology' => ['Skin rash', 'Itching', 'Skin wound', 'Burn', 'Swelling', 'Ulcer', 'Skin infection'],
            'ENT' => ['Ear pain', 'Ear discharge', 'Hearing loss', 'Nose bleeding', 'Sore throat', 'Difficulty swallowing'],
            'Eye' => ['Eye pain', 'Red eye', 'Blurred vision', 'Eye discharge', 'Loss of vision', 'Foreign body in eye'],
            'Dental' => ['Toothache', 'Gum bleeding', 'Facial swelling', 'Mouth ulcer', 'Dental trauma'],
            'Obstetrics/Gynecology' => ['Vaginal bleeding', 'Lower abdominal pain in pregnancy', 'Labour pains', 'Reduced fetal movement', 'Vaginal discharge', 'Missed period', 'Pregnancy-related complaint'],
            'Pediatrics' => ['Poor feeding', 'Excessive crying', 'Fever in child', 'Diarrhea in child', 'Vomiting in child', 'Convulsion in child', 'Difficulty breathing in child'],
            'Psychiatric' => ['Anxiety', 'Insomnia', 'Depressed mood', 'Aggression', 'Confusion', 'Substance use concern'],
            'Emergency/Trauma' => ['Road traffic accident', 'Fall injury', 'Assault', 'Burn injury', 'Poisoning', 'Snake bite', 'Animal bite', 'Severe bleeding', 'Unconsciousness', 'Seizure', 'Breathing difficulty', 'Severe pain'],
        ];
    }

    private function keywords(string $name, string $category): array
    {
        return collect(preg_split('/\s+/', mb_strtolower($name)) ?: [])
            ->merge([mb_strtolower($name), mb_strtolower($category)])
            ->map(fn ($keyword) => trim($keyword, " ,.;:/\\|"))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}