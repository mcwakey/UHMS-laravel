<?php

namespace Database\Seeders;

use App\Models\MedicalPattern;
use App\Models\MedicalPatternItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds reusable medical patterns (a.k.a. clinical templates) for the
 * primary demo doctor. Each pattern is a bundle of complaints + diagnosis
 * + treatment + prescription items that can be applied in one click on the
 * consultation page.
 *
 * Item.data shape is a free-form JSON used by the consultation UI; the
 * shapes below match what `resources/views/consultations/show.blade.php`
 * expects (see the "Apply Pattern" handler).
 */
class MedicalPatternSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = User::where('email', 'doctor@uhms.local')->first();
        if (! $doctor) {
            return;
        }

        $patterns = [
            [
                'name' => 'Uncomplicated Malaria',
                'items' => [
                    ['complaint',          ['name' => 'Fever',                    'duration' => '3 days']],
                    ['complaint',          ['name' => 'Headache',                 'duration' => '2 days']],
                    ['complaint',          ['name' => 'General body weakness',    'duration' => '3 days']],
                    ['diagnosis',          ['name' => 'Uncomplicated Malaria',    'icd_code' => 'B54']],
                    ['treatment',          ['name' => 'Adequate hydration; rest; tepid sponging if febrile']],
                    ['prescription_item',  ['drug' => 'Artemether-Lumefantrine',  'dosage' => '4 tabs', 'frequency' => 'BD',  'duration' => '3 days']],
                    ['prescription_item',  ['drug' => 'Paracetamol 500mg',        'dosage' => '1g',    'frequency' => 'QID', 'duration' => '3 days']],
                ],
            ],
            [
                'name' => 'Upper Respiratory Tract Infection',
                'items' => [
                    ['complaint',          ['name' => 'Sore throat',              'duration' => '4 days']],
                    ['complaint',          ['name' => 'Cough',                    'duration' => '3 days']],
                    ['complaint',          ['name' => 'Nasal congestion',         'duration' => '4 days']],
                    ['diagnosis',          ['name' => 'Acute URTI',               'icd_code' => 'J06.9']],
                    ['treatment',          ['name' => 'Steam inhalation; warm fluids; salt-water gargles']],
                    ['prescription_item',  ['drug' => 'Amoxicillin 500mg',        'dosage' => '500mg', 'frequency' => 'TDS', 'duration' => '5 days']],
                    ['prescription_item',  ['drug' => 'Paracetamol 500mg',        'dosage' => '1g',    'frequency' => 'QID', 'duration' => '5 days']],
                    ['prescription_item',  ['drug' => 'Cough Linctus',            'dosage' => '10mL',  'frequency' => 'TDS', 'duration' => '5 days']],
                ],
            ],
            [
                'name' => 'Hypertension Follow-up',
                'items' => [
                    ['complaint',          ['name' => 'Routine review',           'duration' => 'follow-up']],
                    ['diagnosis',          ['name' => 'Essential Hypertension',   'icd_code' => 'I10']],
                    ['treatment',          ['name' => 'Lifestyle counselling; low-salt diet; daily exercise']],
                    ['prescription_item',  ['drug' => 'Amlodipine 5mg',           'dosage' => '5mg',   'frequency' => 'OD',  'duration' => '30 days']],
                    ['prescription_item',  ['drug' => 'Hydrochlorothiazide 25mg', 'dosage' => '25mg',  'frequency' => 'OD',  'duration' => '30 days']],
                ],
            ],
            [
                'name' => 'Type 2 Diabetes Review',
                'items' => [
                    ['complaint',          ['name' => 'Diabetic review',          'duration' => 'follow-up']],
                    ['diagnosis',          ['name' => 'Type 2 Diabetes Mellitus', 'icd_code' => 'E11']],
                    ['treatment',          ['name' => 'Diet counselling; foot care; regular glucose monitoring']],
                    ['prescription_item',  ['drug' => 'Metformin 500mg',          'dosage' => '500mg', 'frequency' => 'BD',  'duration' => '30 days']],
                ],
            ],
            [
                'name' => 'Acute Gastroenteritis',
                'items' => [
                    ['complaint',          ['name' => 'Diarrhea',                 'duration' => '2 days']],
                    ['complaint',          ['name' => 'Vomiting',                 'duration' => '2 days']],
                    ['complaint',          ['name' => 'Abdominal cramps',         'duration' => '2 days']],
                    ['diagnosis',          ['name' => 'Acute Gastroenteritis',    'icd_code' => 'K52.9']],
                    ['treatment',          ['name' => 'Oral rehydration; bland diet; monitor hydration status']],
                    ['prescription_item',  ['drug' => 'ORS Sachets',              'dosage' => '1 sachet', 'frequency' => 'after each loose stool', 'duration' => '3 days']],
                    ['prescription_item',  ['drug' => 'Metronidazole 200mg',      'dosage' => '400mg', 'frequency' => 'TDS', 'duration' => '5 days']],
                ],
            ],
        ];

        foreach ($patterns as $p) {
            $pattern = MedicalPattern::updateOrCreate(
                ['name' => $p['name'], 'doctor_id' => $doctor->id],
                ['is_active' => true, 'usage_count' => 0]
            );

            // Wipe & re-seed items so re-running the seeder leaves a clean set.
            $pattern->items()->delete();
            foreach ($p['items'] as $i => [$type, $data]) {
                MedicalPatternItem::create([
                    'medical_pattern_id' => $pattern->id,
                    'type' => $type,
                    'data' => $data,
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }
}
