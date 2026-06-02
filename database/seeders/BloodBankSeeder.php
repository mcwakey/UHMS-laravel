<?php

namespace Database\Seeders;

use App\Models\BloodStorageLocation;
use Illuminate\Database\Seeder;

class BloodBankSeeder extends Seeder
{
    public function run(): void
    {
        BloodStorageLocation::updateOrCreate(
            ['code' => 'BB-MAIN'],
            [
                'name' => 'Main Blood Bank Refrigerator',
                'location_type' => 'BLOOD_BANK',
                'temperature_min' => 2,
                'temperature_max' => 6,
                'is_active' => true,
                'notes' => 'Default storage for screened blood units.',
            ]
        );
    }
}
