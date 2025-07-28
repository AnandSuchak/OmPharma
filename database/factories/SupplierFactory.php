<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'phone_number' => $this->faker->unique()->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'gst' => $this->faker->unique()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
            'dln' => $this->faker->unique()->bothify('DLN-????-#####'),
            'address' => $this->faker->address,
        ];
    }
}