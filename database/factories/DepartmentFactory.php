<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'General Medicine', 'Pediatrics', 'Surgery', 'Obstetrics',
                'Emergency', 'Radiology', 'Pathology', 'Ophthalmology',
                'ENT', 'Dermatology', 'Orthopedics', 'Cardiology',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
