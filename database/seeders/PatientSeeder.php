<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        // Must be created one-by-one so generatePatientNumber() increments
        // correctly between each insert (it reads from DB to get next sequence).
        for ($i = 0; $i < 100; $i++) {
            Patient::factory()->create();
        }
    }
}
