<?php

namespace Database\Seeders;

use App\Models\LabTest;
use App\Models\LabTestCategory;
use App\Models\LabTestCriterion;
use Illuminate\Database\Seeder;

/**
 * Seeds lab test categories → tests → multi-criterion templates.
 * Mirrors a typical clinical-laboratory catalog (hematology, biochemistry,
 * urinalysis, microbiology, serology) so the lab module is exercisable
 * end-to-end after a fresh install.
 */
class LabCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Hematology' => [
                [
                    'name' => 'Full Blood Count (FBC)',
                    'code' => 'LAB-FBC',
                    'price' => 80,
                    'criteria' => [
                        ['name' => 'Hemoglobin (HGB)',           'unit' => 'g/dL',      'normal_range' => '12.0 - 16.0'],
                        ['name' => 'Hematocrit (HCT)',           'unit' => '%',         'normal_range' => '36 - 48'],
                        ['name' => 'WBC',                        'unit' => 'x10^9/L',   'normal_range' => '4.0 - 11.0'],
                        ['name' => 'RBC',                        'unit' => 'x10^12/L',  'normal_range' => '4.0 - 5.5'],
                        ['name' => 'Platelets',                  'unit' => 'x10^9/L',   'normal_range' => '150 - 400'],
                        ['name' => 'Neutrophils',                'unit' => '%',         'normal_range' => '40 - 75'],
                        ['name' => 'Lymphocytes',                'unit' => '%',         'normal_range' => '20 - 45'],
                    ],
                ],
                [
                    'name' => 'Erythrocyte Sedimentation Rate (ESR)',
                    'code' => 'LAB-ESR',
                    'price' => 30,
                    'criteria' => [
                        ['name' => 'ESR', 'unit' => 'mm/hr', 'normal_range' => '0 - 20'],
                    ],
                ],
                [
                    'name' => 'Blood Group & Cross Match',
                    'code' => 'LAB-BGX',
                    'price' => 80,
                    'criteria' => [
                        ['name' => 'ABO Group', 'unit' => null, 'normal_range' => 'A / B / AB / O'],
                        ['name' => 'Rh Factor', 'unit' => null, 'normal_range' => 'Positive / Negative'],
                    ],
                ],
            ],

            'Biochemistry' => [
                [
                    'name' => 'Liver Function Test (LFT)',
                    'code' => 'LAB-LFT',
                    'price' => 120,
                    'criteria' => [
                        ['name' => 'AST',                'unit' => 'U/L',   'normal_range' => '10 - 40'],
                        ['name' => 'ALT',                'unit' => 'U/L',   'normal_range' => '10 - 40'],
                        ['name' => 'ALP',                'unit' => 'U/L',   'normal_range' => '40 - 130'],
                        ['name' => 'Total Bilirubin',    'unit' => 'mg/dL', 'normal_range' => '0.1 - 1.2'],
                        ['name' => 'Direct Bilirubin',   'unit' => 'mg/dL', 'normal_range' => '0.0 - 0.3'],
                        ['name' => 'Total Protein',      'unit' => 'g/dL',  'normal_range' => '6.0 - 8.3'],
                        ['name' => 'Albumin',            'unit' => 'g/dL',  'normal_range' => '3.5 - 5.0'],
                    ],
                ],
                [
                    'name' => 'Renal Function Test (RFT)',
                    'code' => 'LAB-RFT',
                    'price' => 120,
                    'criteria' => [
                        ['name' => 'Urea',       'unit' => 'mmol/L', 'normal_range' => '2.5 - 7.5'],
                        ['name' => 'Creatinine', 'unit' => 'umol/L', 'normal_range' => '60 - 110'],
                        ['name' => 'Sodium',     'unit' => 'mmol/L', 'normal_range' => '135 - 145'],
                        ['name' => 'Potassium',  'unit' => 'mmol/L', 'normal_range' => '3.5 - 5.0'],
                        ['name' => 'Chloride',   'unit' => 'mmol/L', 'normal_range' => '98 - 106'],
                    ],
                ],
                [
                    'name' => 'Fasting Blood Sugar',
                    'code' => 'LAB-FBS',
                    'price' => 30,
                    'criteria' => [
                        ['name' => 'FBS', 'unit' => 'mmol/L', 'normal_range' => '3.9 - 6.1'],
                    ],
                ],
                [
                    'name' => 'Lipid Profile',
                    'code' => 'LAB-LIP',
                    'price' => 150,
                    'criteria' => [
                        ['name' => 'Total Cholesterol', 'unit' => 'mg/dL', 'normal_range' => '< 200'],
                        ['name' => 'HDL',               'unit' => 'mg/dL', 'normal_range' => '> 40'],
                        ['name' => 'LDL',               'unit' => 'mg/dL', 'normal_range' => '< 100'],
                        ['name' => 'Triglycerides',     'unit' => 'mg/dL', 'normal_range' => '< 150'],
                    ],
                ],
            ],

            'Urinalysis' => [
                [
                    'name' => 'Routine Urinalysis',
                    'code' => 'LAB-URI',
                    'price' => 50,
                    'criteria' => [
                        ['name' => 'Color',            'unit' => null,  'normal_range' => 'Yellow'],
                        ['name' => 'Appearance',       'unit' => null,  'normal_range' => 'Clear'],
                        ['name' => 'pH',               'unit' => null,  'normal_range' => '4.6 - 8.0'],
                        ['name' => 'Specific Gravity', 'unit' => null,  'normal_range' => '1.005 - 1.030'],
                        ['name' => 'Protein',          'unit' => null,  'normal_range' => 'Negative'],
                        ['name' => 'Glucose',          'unit' => null,  'normal_range' => 'Negative'],
                        ['name' => 'Ketones',          'unit' => null,  'normal_range' => 'Negative'],
                        ['name' => 'Blood',            'unit' => null,  'normal_range' => 'Negative'],
                        ['name' => 'Leukocytes',       'unit' => null,  'normal_range' => 'Negative'],
                        ['name' => 'Nitrites',         'unit' => null,  'normal_range' => 'Negative'],
                    ],
                ],
                [
                    'name' => 'Pregnancy Test (β-HCG)',
                    'code' => 'LAB-HCG',
                    'price' => 40,
                    'criteria' => [
                        ['name' => 'HCG', 'unit' => null, 'normal_range' => 'Negative'],
                    ],
                ],
            ],

            'Microbiology' => [
                [
                    'name' => 'Urine Culture & Sensitivity',
                    'code' => 'LAB-UCS',
                    'price' => 180,
                    'criteria' => [
                        ['name' => 'Organism Isolated',   'unit' => null, 'normal_range' => 'No growth'],
                        ['name' => 'Colony Count',        'unit' => 'CFU/mL', 'normal_range' => '< 10^5'],
                        ['name' => 'Antibiotic Sensitivity', 'unit' => null, 'normal_range' => null],
                    ],
                ],
                [
                    'name' => 'Stool R/E',
                    'code' => 'LAB-STR',
                    'price' => 50,
                    'criteria' => [
                        ['name' => 'Consistency',  'unit' => null, 'normal_range' => 'Formed'],
                        ['name' => 'Ova / Cysts',  'unit' => null, 'normal_range' => 'Not seen'],
                        ['name' => 'Occult Blood', 'unit' => null, 'normal_range' => 'Negative'],
                    ],
                ],
                [
                    'name' => 'Malaria RDT',
                    'code' => 'LAB-MAL',
                    'price' => 40,
                    'criteria' => [
                        ['name' => 'P. falciparum', 'unit' => null, 'normal_range' => 'Negative'],
                    ],
                ],
                [
                    'name' => 'Widal Test',
                    'code' => 'LAB-WID',
                    'price' => 60,
                    'criteria' => [
                        ['name' => 'S. typhi O',  'unit' => null, 'normal_range' => '< 1:80'],
                        ['name' => 'S. typhi H',  'unit' => null, 'normal_range' => '< 1:80'],
                    ],
                ],
            ],

            'Serology' => [
                [
                    'name' => 'HIV Screening',
                    'code' => 'LAB-HIV',
                    'price' => 50,
                    'criteria' => [
                        ['name' => 'HIV 1 & 2', 'unit' => null, 'normal_range' => 'Non-reactive'],
                    ],
                ],
                [
                    'name' => 'Hepatitis B Surface Antigen',
                    'code' => 'LAB-HBV',
                    'price' => 60,
                    'criteria' => [
                        ['name' => 'HBsAg', 'unit' => null, 'normal_range' => 'Non-reactive'],
                    ],
                ],
                [
                    'name' => 'Hepatitis C Antibody',
                    'code' => 'LAB-HCV',
                    'price' => 70,
                    'criteria' => [
                        ['name' => 'Anti-HCV', 'unit' => null, 'normal_range' => 'Non-reactive'],
                    ],
                ],
                [
                    'name' => 'VDRL / Syphilis',
                    'code' => 'LAB-VDR',
                    'price' => 60,
                    'criteria' => [
                        ['name' => 'VDRL', 'unit' => null, 'normal_range' => 'Non-reactive'],
                    ],
                ],
            ],
        ];

        foreach ($catalog as $categoryName => $tests) {
            $category = LabTestCategory::updateOrCreate(
                ['name' => $categoryName],
                ['is_active' => true]
            );

            foreach ($tests as $t) {
                $test = LabTest::updateOrCreate(
                    ['code' => $t['code']],
                    [
                        'category_id' => $category->id,
                        'name' => $t['name'],
                        'price' => $t['price'],
                        'is_active' => true,
                    ]
                );

                foreach (($t['criteria'] ?? []) as $i => $c) {
                    LabTestCriterion::updateOrCreate(
                        ['lab_test_id' => $test->id, 'name' => $c['name']],
                        [
                            'unit' => $c['unit'],
                            'normal_range' => $c['normal_range'],
                            'sort_order' => $i + 1,
                        ]
                    );
                }
            }
        }
    }
}
