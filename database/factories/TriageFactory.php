<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class TriageFactory extends Factory
{
    protected $model = Triage::class;

    public function definition(): array
    {
        return [
            'visit_id'   => Visit::factory(),
            'patient_id' => Patient::factory(),
            'heart_rate' => fake()->numberBetween(60, 100),
            'temperature' => fake()->randomFloat(1, 36.0, 37.5),
            'triaged_by' => User::factory(),
            'triaged_at' => now(),
        ];
    }
}
