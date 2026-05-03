<?php

namespace Database\Seeders;

use App\Enums\InsuranceType;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashAndCarrySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $provider = InsuranceProvider::updateOrCreate(
                ['name' => 'Cash & Carry'],
                [
                    'short_name' => 'C&C',
                    'type' => InsuranceType::PRIVATE->value,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );

            InsuranceTier::updateOrCreate(
                [
                    'insurance_provider_id' => $provider->id,
                    'code' => 'STD',
                ],
                [
                    'name' => 'Standard',
                    'is_default' => true,
                    'is_active' => true,
                    'coverage_percentage' => 100.00,
                ]
            );
        });
    }
}
