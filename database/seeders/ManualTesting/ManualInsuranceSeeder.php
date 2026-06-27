<?php

namespace Database\Seeders\ManualTesting;

class ManualInsuranceSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('insurance_providers')) {
            return;
        }

        $providers = [
            ['NHIS Manual', 'NHIS-MT', 'nhia', true],
            ['Private Insurance A Manual', 'PRIVA-MT', 'private', true],
            ['Private Insurance B Manual', 'PRIVB-MT', 'private', true],
            ['Corporate Sponsor A Manual', 'CORPA-MT', 'corporate', true],
            ['Corporate Sponsor B Manual', 'CORPB-MT', 'corporate', true],
            ['Staff Scheme Manual', 'STAFF-MT', 'private', true],
            ['Charity Fund Manual', 'CHARITY-MT', 'corporate', true],
        ];

        foreach ($providers as [$name, $code, $type, $claim]) {
            $this->updateOrInsert('insurance_providers', ['code' => $code], [
                'name' => $name,
                'short_name' => $code,
                'code' => $code,
                'type' => $type,
                'description' => 'Manual test insurance/sponsor provider.',
                'contact_phone' => '0550000000',
                'contact_email' => strtolower($code).'@uhms.test',
                'is_active' => true,
                'is_default' => false,
                'requires_claim_submission' => $claim,
                'requires_verification_code' => $type === 'nhia',
                'verification_code_label' => $type === 'nhia' ? 'CCC Code' : 'Verification Code',
                'verification_driver' => 'manual_test',
                'verification_method' => 'mock',
                'verification_channel' => 'sandbox',
                'verification_config' => $this->metadata(['provider' => $code]),
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }
}
