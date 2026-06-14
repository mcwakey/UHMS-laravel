<?php

namespace Database\Seeders;

use App\Models\InvestigationCriterion;
use App\Models\InvestigationHeader;
use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class InvestigationServiceCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $seededServices = 0;
        $seededCriteria = 0;

        foreach ($this->catalogue() as $serviceCode => $headers) {
            $service = ServiceCatalog::where('code', $serviceCode)->first();

            if (! $service) {
                $this->command?->warn("Skipped {$serviceCode}: service not found.");
                continue;
            }

            $seededServices++;

            foreach ($headers as $headerIndex => $headerData) {
                $header = InvestigationHeader::updateOrCreate(
                    ['service_id' => $service->id, 'name' => $headerData['name']],
                    [
                        'description' => $headerData['description'] ?? null,
                        'sort_order' => ($headerIndex + 1) * 10,
                        'is_active' => true,
                    ]
                );

                foreach ($headerData['criteria'] as $criterionIndex => $criterionData) {
                    InvestigationCriterion::updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'header_id' => $header->id,
                            'name' => $criterionData['name'],
                        ],
                        [
                            'unit' => $criterionData['unit'] ?? null,
                            'reference_range' => $criterionData['reference_range'] ?? null,
                            'default_value' => $criterionData['default_value'] ?? null,
                            'input_type' => $criterionData['input_type'] ?? 'text',
                            'options' => $criterionData['options'] ?? null,
                            'sort_order' => ($criterionIndex + 1) * 10,
                            'is_required' => $criterionData['is_required'] ?? false,
                            'is_active' => true,
                        ]
                    );

                    $seededCriteria++;
                }
            }
        }

        $this->command?->info(
            "Seeded investigation criteria for {$seededServices} services ({$seededCriteria} criteria)."
        );
    }

    /**
     * These example adult ranges are starter data. Each facility should
     * replace them with its laboratory's validated reference ranges.
     */
    private function catalogue(): array
    {
        return [
            'LAB-FBC' => [
                [
                    'name' => 'Haematology',
                    'criteria' => [
                        ['name' => 'Haemoglobin', 'unit' => 'g/dL', 'reference_range' => '12.0 - 17.5', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'White Blood Cell Count', 'unit' => 'x10^9/L', 'reference_range' => '4.0 - 11.0', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'Red Blood Cell Count', 'unit' => 'x10^12/L', 'reference_range' => '4.0 - 6.0', 'input_type' => 'number'],
                        ['name' => 'Haematocrit', 'unit' => '%', 'reference_range' => '36 - 52', 'input_type' => 'number'],
                        ['name' => 'Platelet Count', 'unit' => 'x10^9/L', 'reference_range' => '150 - 450', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'Mean Cell Volume', 'unit' => 'fL', 'reference_range' => '80 - 100', 'input_type' => 'number'],
                        ['name' => 'Mean Cell Haemoglobin', 'unit' => 'pg', 'reference_range' => '27 - 33', 'input_type' => 'number'],
                        ['name' => 'Mean Cell Haemoglobin Concentration', 'unit' => 'g/dL', 'reference_range' => '32 - 36', 'input_type' => 'number'],
                    ],
                ],
                [
                    'name' => 'Differential Count',
                    'criteria' => [
                        ['name' => 'Neutrophils', 'unit' => '%', 'reference_range' => '40 - 75', 'input_type' => 'number'],
                        ['name' => 'Lymphocytes', 'unit' => '%', 'reference_range' => '20 - 45', 'input_type' => 'number'],
                        ['name' => 'Monocytes', 'unit' => '%', 'reference_range' => '2 - 10', 'input_type' => 'number'],
                        ['name' => 'Eosinophils', 'unit' => '%', 'reference_range' => '1 - 6', 'input_type' => 'number'],
                        ['name' => 'Basophils', 'unit' => '%', 'reference_range' => '0 - 2', 'input_type' => 'number'],
                    ],
                ],
            ],
            'LAB-MAL' => [
                [
                    'name' => 'Malaria Screen',
                    'criteria' => [
                        ['name' => 'Malaria Antigen', 'input_type' => 'select', 'options' => ['Negative', 'P. falciparum positive', 'Pan-malarial positive', 'Mixed infection'], 'is_required' => true],
                        ['name' => 'Parasite Density', 'unit' => 'parasites/uL', 'input_type' => 'number'],
                        ['name' => 'Comments', 'input_type' => 'textarea'],
                    ],
                ],
            ],
            'LAB-URI' => [
                [
                    'name' => 'Physical Examination',
                    'criteria' => [
                        ['name' => 'Colour', 'input_type' => 'select', 'options' => ['Pale yellow', 'Yellow', 'Amber', 'Red', 'Brown', 'Other']],
                        ['name' => 'Appearance', 'input_type' => 'select', 'options' => ['Clear', 'Slightly cloudy', 'Cloudy', 'Turbid']],
                        ['name' => 'Specific Gravity', 'reference_range' => '1.005 - 1.030', 'input_type' => 'number'],
                        ['name' => 'pH', 'reference_range' => '4.5 - 8.0', 'input_type' => 'number'],
                    ],
                ],
                [
                    'name' => 'Chemical Examination',
                    'criteria' => [
                        ['name' => 'Protein', 'input_type' => 'select', 'options' => ['Negative', 'Trace', '+', '++', '+++', '++++']],
                        ['name' => 'Glucose', 'input_type' => 'select', 'options' => ['Negative', 'Trace', '+', '++', '+++', '++++']],
                        ['name' => 'Ketones', 'input_type' => 'select', 'options' => ['Negative', 'Trace', '+', '++', '+++']],
                        ['name' => 'Blood', 'input_type' => 'select', 'options' => ['Negative', 'Trace', '+', '++', '+++']],
                        ['name' => 'Bilirubin', 'input_type' => 'select', 'options' => ['Negative', '+', '++', '+++']],
                        ['name' => 'Urobilinogen', 'input_type' => 'select', 'options' => ['Normal', '+', '++', '+++']],
                        ['name' => 'Nitrite', 'input_type' => 'select', 'options' => ['Negative', 'Positive']],
                        ['name' => 'Leukocyte Esterase', 'input_type' => 'select', 'options' => ['Negative', 'Trace', '+', '++', '+++']],
                    ],
                ],
                [
                    'name' => 'Microscopy',
                    'criteria' => [
                        ['name' => 'White Blood Cells', 'unit' => '/HPF', 'reference_range' => '0 - 5', 'input_type' => 'text'],
                        ['name' => 'Red Blood Cells', 'unit' => '/HPF', 'reference_range' => '0 - 2', 'input_type' => 'text'],
                        ['name' => 'Epithelial Cells', 'unit' => '/HPF', 'input_type' => 'text'],
                        ['name' => 'Casts', 'input_type' => 'text'],
                        ['name' => 'Crystals', 'input_type' => 'text'],
                        ['name' => 'Bacteria', 'input_type' => 'select', 'options' => ['None seen', 'Few', 'Moderate', 'Many']],
                        ['name' => 'Yeast Cells', 'input_type' => 'select', 'options' => ['None seen', 'Present']],
                    ],
                ],
            ],
            'LAB-LFT' => [
                [
                    'name' => 'Liver Function',
                    'criteria' => [
                        ['name' => 'ALT', 'unit' => 'U/L', 'reference_range' => '7 - 56', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'AST', 'unit' => 'U/L', 'reference_range' => '10 - 40', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'Alkaline Phosphatase', 'unit' => 'U/L', 'reference_range' => '44 - 147', 'input_type' => 'number'],
                        ['name' => 'Gamma GT', 'unit' => 'U/L', 'reference_range' => '9 - 48', 'input_type' => 'number'],
                        ['name' => 'Total Bilirubin', 'unit' => 'umol/L', 'reference_range' => '5 - 21', 'input_type' => 'number'],
                        ['name' => 'Direct Bilirubin', 'unit' => 'umol/L', 'reference_range' => '0 - 5', 'input_type' => 'number'],
                        ['name' => 'Total Protein', 'unit' => 'g/L', 'reference_range' => '60 - 83', 'input_type' => 'number'],
                        ['name' => 'Albumin', 'unit' => 'g/L', 'reference_range' => '35 - 50', 'input_type' => 'number'],
                    ],
                ],
            ],
            'LAB-RFT' => [
                [
                    'name' => 'Renal Function',
                    'criteria' => [
                        ['name' => 'Urea', 'unit' => 'mmol/L', 'reference_range' => '2.5 - 7.1', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'Creatinine', 'unit' => 'umol/L', 'reference_range' => '60 - 110', 'input_type' => 'number', 'is_required' => true],
                        ['name' => 'eGFR', 'unit' => 'mL/min/1.73m2', 'reference_range' => '>= 90', 'input_type' => 'number'],
                        ['name' => 'Sodium', 'unit' => 'mmol/L', 'reference_range' => '135 - 145', 'input_type' => 'number'],
                        ['name' => 'Potassium', 'unit' => 'mmol/L', 'reference_range' => '3.5 - 5.0', 'input_type' => 'number'],
                        ['name' => 'Chloride', 'unit' => 'mmol/L', 'reference_range' => '98 - 106', 'input_type' => 'number'],
                        ['name' => 'Bicarbonate', 'unit' => 'mmol/L', 'reference_range' => '22 - 29', 'input_type' => 'number'],
                    ],
                ],
            ],
            'LAB-BGX' => [
                [
                    'name' => 'Blood Grouping',
                    'criteria' => [
                        ['name' => 'ABO Group', 'input_type' => 'select', 'options' => ['A', 'B', 'AB', 'O'], 'is_required' => true],
                        ['name' => 'Rhesus Factor', 'input_type' => 'select', 'options' => ['Positive', 'Negative'], 'is_required' => true],
                    ],
                ],
                [
                    'name' => 'Cross Match',
                    'criteria' => [
                        ['name' => 'Donor Unit Number', 'input_type' => 'text'],
                        ['name' => 'Compatibility', 'input_type' => 'select', 'options' => ['Compatible', 'Incompatible'], 'is_required' => true],
                        ['name' => 'Cross-match Method', 'input_type' => 'select', 'options' => ['Immediate spin', 'AHG', 'Electronic', 'Other']],
                        ['name' => 'Comments', 'input_type' => 'textarea'],
                    ],
                ],
            ],
            'LAB-WID' => [
                [
                    'name' => 'Widal Agglutination',
                    'criteria' => [
                        ['name' => 'Salmonella typhi O', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                        ['name' => 'Salmonella typhi H', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                        ['name' => 'Salmonella paratyphi AO', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                        ['name' => 'Salmonella paratyphi AH', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                        ['name' => 'Salmonella paratyphi BO', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                        ['name' => 'Salmonella paratyphi BH', 'input_type' => 'select', 'options' => ['Negative', '1:20', '1:40', '1:80', '1:160', '1:320']],
                    ],
                ],
            ],
            'LAB-HIV' => [
                [
                    'name' => 'HIV Screening',
                    'criteria' => [
                        ['name' => 'Screening Result', 'input_type' => 'select', 'options' => ['Non-reactive', 'Reactive', 'Indeterminate'], 'is_required' => true],
                        ['name' => 'Test Method', 'input_type' => 'select', 'options' => ['Rapid antibody test', 'Antigen/antibody test', 'Other']],
                        ['name' => 'Comments', 'input_type' => 'textarea'],
                    ],
                ],
            ],
            'LAB-HBV' => [
                [
                    'name' => 'Hepatitis B Screening',
                    'criteria' => [
                        ['name' => 'HBsAg Result', 'input_type' => 'select', 'options' => ['Non-reactive', 'Reactive', 'Indeterminate'], 'is_required' => true],
                        ['name' => 'Test Method', 'input_type' => 'select', 'options' => ['Rapid test', 'ELISA', 'Other']],
                        ['name' => 'Comments', 'input_type' => 'textarea'],
                    ],
                ],
            ],
            'LAB-HCG' => [
                [
                    'name' => 'Pregnancy Test',
                    'criteria' => [
                        ['name' => 'HCG Result', 'input_type' => 'select', 'options' => ['Negative', 'Positive', 'Indeterminate'], 'is_required' => true],
                        ['name' => 'Specimen', 'input_type' => 'select', 'options' => ['Urine', 'Serum']],
                        ['name' => 'Comments', 'input_type' => 'textarea'],
                    ],
                ],
            ],
        ];
    }
}
