<?php

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class CashAndCarrySeeder extends Seeder
{
    public function run(): void
    {
        InsuranceProvider::firstOrCreate(
            ['name' => 'Cash & Carry'],
            [
                'short_name' => 'C&C',
                'type' => 'private',
                'is_active' => true,
                'is_default' => true,
                'annual_limit' => null,
                'per_visit_limit' => null,
                'coverage_percentage' => 100.00,
                'tier' => 'default',
            ]
        );
    }
}
