<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'product_id' => \App\Models\Product::factory(),
            'amount' => $this->faker->numberBetween(100, 10000),
            'currency' => 'XOF',
            'description' => $this->faker->sentence(),
            'methode_paiement' => $this->faker->randomElement(['mtn', 'moov', 'card']),
            'status' => $this->faker->randomElement(['held', 'approved', 'declined']),
            'fedapay_id' => 'v1_' . $this->faker->unique()->numberBetween(1000, 9999),
        ];
    }
}
