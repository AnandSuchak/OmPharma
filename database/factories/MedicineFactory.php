<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true), // e.g., "Cardiac Elixir"
            'company_name' => $this->faker->company,
            'description' => $this->faker->sentence,
            'pack' => $this->faker->randomElement(['10 strips', '50ml bottle', '1 tube']),
            'hsn_code' => $this->faker->optional()->numerify('HSN####'),
            'gst_rate' => $this->faker->randomElement([5, 12, 18]),
        ];
    }
}