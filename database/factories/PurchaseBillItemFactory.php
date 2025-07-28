<?php

namespace Database\Factories;

use App\Models\Medicine;
use App\Models\PurchaseBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseBillItem>
 */
class PurchaseBillItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
     public function definition(): array
    {
        return [
            'purchase_bill_id' => PurchaseBill::factory(),
            'medicine_id' => Medicine::factory(),
            'batch_number' => $this->faker->unique()->bothify('B##??##'),
            'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'quantity' => $this->faker->numberBetween(1, 50),
            'free_quantity' => $this->faker->optional(0.2, 0)->numberBetween(1, 5), // 20% chance of free quantity
            'purchase_price' => $this->faker->randomFloat(2, 10, 200),
            'sale_price' => $this->faker->randomFloat(2, 20, 300),
            'ptr' => $this->faker->optional()->randomFloat(2, 15, 250),
            'gst_rate' => $this->faker->randomElement([0, 5, 12, 18]),
            'discount_percentage' => $this->faker->optional(0.3, 0)->randomFloat(2, 1, 10),
            'our_discount_percentage' => $this->faker->optional(0.5, 0)->randomFloat(2, 1, 15),
        ];
    }
}
