<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
{
    return [
        'medicine_id' => Medicine::factory(),
        'batch_number' => $this->faker->bothify('B-####??'),
        'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+2 years'),
        'quantity' => $this->faker->numberBetween(1, 100),
    ];
}
}
