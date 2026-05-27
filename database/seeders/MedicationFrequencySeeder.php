<?php

namespace Database\Seeders;

use App\Models\MedicationFrequency;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class MedicationFrequencySeeder extends Seeder
{
    public function run(): void
    {
        $frequencies = [
            ['code' => 'OD', 'name' => 'Once daily', 'times_per_day' => 1, 'interval_hours' => null, 'default_times' => ['08:00']],
            ['code' => 'BD', 'name' => 'Twice daily', 'times_per_day' => 2, 'interval_hours' => null, 'default_times' => ['08:00', '20:00']],
            ['code' => 'TDS', 'name' => 'Three times daily', 'times_per_day' => 3, 'interval_hours' => null, 'default_times' => ['08:00', '14:00', '20:00']],
            ['code' => 'QID', 'name' => 'Four times daily', 'times_per_day' => 4, 'interval_hours' => null, 'default_times' => ['06:00', '12:00', '18:00', '22:00']],
            ['code' => 'Q6H', 'name' => 'Every 6 hours', 'times_per_day' => 4, 'interval_hours' => 6, 'default_times' => null],
            ['code' => 'Q8H', 'name' => 'Every 8 hours', 'times_per_day' => 3, 'interval_hours' => 8, 'default_times' => null],
            ['code' => 'Q12H', 'name' => 'Every 12 hours', 'times_per_day' => 2, 'interval_hours' => 12, 'default_times' => null],
            ['code' => 'STAT', 'name' => 'Immediately once', 'times_per_day' => 1, 'interval_hours' => null, 'default_times' => null, 'is_stat' => true],
            ['code' => 'PRN', 'name' => 'As needed', 'times_per_day' => null, 'interval_hours' => null, 'default_times' => null, 'requires_schedule' => false, 'is_prn' => true],
            ['code' => 'SOS', 'name' => 'As needed / emergency', 'times_per_day' => null, 'interval_hours' => null, 'default_times' => null, 'requires_schedule' => false, 'is_prn' => true],
        ];

        foreach ($frequencies as $frequency) {
            MedicationFrequency::updateOrCreate(
                ['code' => $frequency['code']],
                array_merge([
                    'requires_schedule' => true,
                    'is_prn' => false,
                    'is_stat' => false,
                    'is_active' => true,
                ], $frequency),
            );
        }

        Setting::setValue('medication', 'medication_task_upcoming_minutes', 30, 'integer');
        Setting::setValue('medication', 'medication_task_overdue_after_minutes', 15, 'integer');
        Setting::setValue('medication', 'medication_task_escalate_after_minutes', 30, 'integer');
        Setting::setValue('medication', 'medication_task_second_escalation_after_minutes', 60, 'integer');
    }
}
