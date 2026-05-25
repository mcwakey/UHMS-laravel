<?php

namespace Database\Seeders;

use App\Enums\InsuranceType;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\InsuranceType as InsuranceTypeModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds additional insurance providers beyond the mandatory Cash & Carry
 * (which lives in CashAndCarrySeeder). One default tier ("Standard") is
 * provisioned per provider; richer tier ladders are then added for the
 * private corporates that exercise them in QA.
 */
class InsuranceProviderSeeder extends Seeder
{
    public function run(): void
    {
        $nhiaType = InsuranceTypeModel::updateOrCreate(
            ['code' => 'NHIA'],
            [
                'name' => 'National Health Insurance Authority',
                'description' => 'Ghana national health insurance authority claim workflow.',
                'claim_workflow' => 'NHIA',
                'requires_claim_submission' => true,
                'requires_verification_code' => true,
                'verification_code_label' => 'CCC Code',
                'requires_diagnosis' => true,
                'requires_doctor' => true,
                'default_claim_export_format' => 'CSV',
                'is_active' => true,
            ]
        );
        $privateType = InsuranceTypeModel::firstOrCreate(
            ['code' => 'PRIVATE'],
            ['name' => 'Private Insurance', 'claim_workflow' => 'GENERIC', 'requires_claim_submission' => true, 'default_claim_export_format' => 'CSV', 'is_active' => true]
        );
        $corporateType = InsuranceTypeModel::firstOrCreate(
            ['code' => 'CORPORATE'],
            ['name' => 'Corporate Insurance', 'claim_workflow' => 'GENERIC', 'requires_claim_submission' => true, 'default_claim_export_format' => 'CSV', 'is_active' => true]
        );

        $providers = [
            [
                'name' => 'National Health Insurance Scheme',
                'short_name' => 'NHIS',
                'code' => 'NHIS',
                'type' => InsuranceType::NHIA,
                'insurance_type_id' => $nhiaType->id,
                'contact_phone' => '0302-123-456',
                'contact_email' => 'support@nhia.gov.gh',
                'tiers' => [
                    ['code' => 'STD', 'name' => 'Standard', 'is_default' => true, 'coverage_percentage' => 100, 'per_visit_limit' => 500],
                ],
            ],
            [
                'name' => 'Premium Health Insurance',
                'short_name' => 'PHI',
                'code' => 'PHI',
                'type' => InsuranceType::PRIVATE,
                'insurance_type_id' => $privateType->id,
                'contact_phone' => '0244-111-222',
                'contact_email' => 'claims@premiumhealth.com',
                'tiers' => [
                    ['code' => 'BAS', 'name' => 'Basic',    'coverage_percentage' => 80,  'per_visit_limit' => 800,  'annual_limit' => 8000,  'sort_order' => 1],
                    ['code' => 'SIL', 'name' => 'Silver',   'coverage_percentage' => 90,  'per_visit_limit' => 1500, 'annual_limit' => 18000, 'sort_order' => 2, 'is_default' => true],
                    ['code' => 'GLD', 'name' => 'Gold',     'coverage_percentage' => 95,  'per_visit_limit' => 3000, 'annual_limit' => 40000, 'sort_order' => 3],
                    ['code' => 'PLT', 'name' => 'Platinum', 'coverage_percentage' => 100, 'per_visit_limit' => null, 'annual_limit' => 80000, 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Glico Healthcare',
                'short_name' => 'GLICO',
                'code' => 'GLICO',
                'type' => InsuranceType::PRIVATE,
                'insurance_type_id' => $privateType->id,
                'contact_phone' => '0302-678-901',
                'contact_email' => 'claims@glicohealthcare.com',
                'tiers' => [
                    ['code' => 'STD', 'name' => 'Standard', 'is_default' => true, 'coverage_percentage' => 90, 'per_visit_limit' => 1000, 'annual_limit' => 15000],
                    ['code' => 'EXE', 'name' => 'Executive',                       'coverage_percentage' => 100, 'per_visit_limit' => 5000, 'annual_limit' => 60000, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Apex Mutual Health',
                'short_name' => 'APEX',
                'code' => 'APEX',
                'type' => InsuranceType::PRIVATE,
                'insurance_type_id' => $privateType->id,
                'contact_phone' => '0277-888-999',
                'contact_email' => 'support@apexmutual.com',
                'tiers' => [
                    ['code' => 'STD', 'name' => 'Standard', 'is_default' => true, 'coverage_percentage' => 85, 'per_visit_limit' => 1200, 'annual_limit' => 12000],
                ],
            ],
            [
                'name' => 'Acme Industries Corporate Plan',
                'short_name' => 'ACME-CORP',
                'code' => 'ACME-CORP',
                'type' => InsuranceType::CORPORATE,
                'insurance_type_id' => $corporateType->id,
                'contact_phone' => '0244-555-000',
                'contact_email' => 'hr@acme-industries.com',
                'contract_number' => 'CORP-ACME-2026',
                'tiers' => [
                    ['code' => 'STF', 'name' => 'Staff',     'is_default' => true, 'coverage_percentage' => 100, 'per_visit_limit' => 2000, 'annual_limit' => 25000],
                    ['code' => 'MGT', 'name' => 'Management',                      'coverage_percentage' => 100, 'per_visit_limit' => 5000, 'annual_limit' => 60000, 'sort_order' => 2],
                ],
            ],
        ];

        DB::transaction(function () use ($providers) {
            InsuranceProvider::query()
                ->where('short_name', 'NHIA')
                ->orWhere('name', 'National Health Insurance Authority')
                ->update([
                    'name' => 'National Health Insurance Scheme',
                    'short_name' => 'NHIS',
                    'code' => 'NHIS',
                ]);

            foreach ($providers as $p) {
                $tiers = $p['tiers'];
                unset($p['tiers']);

                $provider = InsuranceProvider::updateOrCreate(
                    ['code' => $p['code'] ?? $p['short_name']],
                    array_merge($p, [
                        'type' => $p['type']->value,
                        'is_active' => true,
                        'is_default' => false,
                    ])
                );

                foreach ($tiers as $tier) {
                    InsuranceTier::updateOrCreate(
                        ['insurance_provider_id' => $provider->id, 'code' => $tier['code']],
                        array_merge([
                            'is_default' => false,
                            'is_active' => true,
                            'sort_order' => 0,
                        ], $tier)
                    );
                }
            }
        });
    }
}
