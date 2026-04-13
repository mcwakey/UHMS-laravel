<?php

namespace Database\Factories;

use App\Enums\BloodGroup;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'patient_number' => 'PT' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'blood_group' => fake()->randomElement(BloodGroup::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'region' => fake()->randomElement(['Greater Accra', 'Ashanti', 'Northern', 'Eastern', 'Western']),
            'status' => 'active',
        ];
    }
}
