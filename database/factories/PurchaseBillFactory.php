<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseBill>
 */
class PurchaseBillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'bill_number' => $this->faker->unique()->numerify('BILL-#####'),
            'bill_date' => $this->faker->date(),
            'total_amount' => $this->faker->randomFloat(2, 100, 5000),
            'total_gst_amount' => $this->faker->randomFloat(2, 10, 500),
            // FIXED: Removed ->optional() to prevent null values on a NOT NULL column.
            'extra_discount_amount' => $this->faker->randomFloat(2, 0, 100),
            // 'rounding_off_amount' is commented out for SQLite compatibility.
            'status' => $this->faker->randomElement(['Received', 'Pending']),
        ];
    }
}
