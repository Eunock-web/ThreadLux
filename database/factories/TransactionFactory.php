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
            'reference' => 'TXN-' . strtoupper($this->faker->unique()->bothify('????####')),
            'acheteur_id' => \App\Models\User::factory(),
            'vendeur_id' => \App\Models\User::factory(),
            'commande_id' => \App\Models\Commande::factory(),
            'amount' => $this->faker->randomFloat(2, 500, 50000),
            'currency' => 'XOF',
            'payment_method' => $this->faker->randomElement(['mobile_money', 'card', 'virement']),
            'provider' => $this->faker->randomElement(['fedapay', 'stripe']),
            'provider_ref' => 'v1_' . $this->faker->unique()->numberBetween(1000, 9999),
            'status' => $this->faker->randomElement(['initiated', 'pending', 'paid', 'failed']),
            'escrow_status' => $this->faker->randomElement(['none', 'held', 'released']),
            'description' => $this->faker->sentence(),
        ];
    }
}
