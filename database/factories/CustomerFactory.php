<?php

// File: database/factories/CustomerFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'contact_number' => $this->faker->unique()->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            // ADDED: Provide a value for the required 'dln' column
            'dln' => $this->faker->bothify('??##########'), 
        ];
    }
}
