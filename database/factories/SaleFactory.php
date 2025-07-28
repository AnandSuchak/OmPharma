<?php

// File: database/factories/SaleFactory.php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Find a customer or create a new one to associate with the sale
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();

        return [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'sale_date' => $this->faker->dateTimeThisMonth(),
            'bill_number' => 'CASH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'total_amount' => $this->faker->randomFloat(2, 100, 1000),
            'total_gst_amount' => $this->faker->randomFloat(2, 10, 100),
            'notes' => $this->faker->optional()->sentence,
        ];
    }
}
