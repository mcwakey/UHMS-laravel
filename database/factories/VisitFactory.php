<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Enums\Priority;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'visit_number' => 'VST' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'patient_id' => Patient::factory(),
            'visit_type' => fake()->randomElement(VisitType::cases()),
            'visit_date' => now()->toDateString(),
            'status' => VisitStatus::REGISTERED,
            'priority' => Priority::NORMAL,
            'chief_complaint' => fake()->sentence(),
        ];
    }
}
