<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommandeFactory extends Factory
{
    protected $model = Commande::class;

    public function definition(): array
    {
        $sousTotal = $this->faker->randomFloat(2, 500, 50000);
        $livraison = $this->faker->randomFloat(2, 500, 5000);

        return [
            'reference' => 'CMD-' . strtoupper($this->faker->unique()->bothify('????####')),
            'acheteur_id' => User::factory(),
            'vendeur_id' => User::factory(),
            'address_id' => Address::factory(),
            'montant_sous_total' => $sousTotal,
            'montant_livraison' => $livraison,
            'montant_total' => $sousTotal + $livraison,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled']),
            'escrow_status' => $this->faker->randomElement(['none', 'held', 'released', 'refunded', 'disputed']),
            'tracking_number' => strtoupper($this->faker->bothify('TRK########')),
            'note_acheteur' => $this->faker->sentence(),
            'livraison_estimee' => $this->faker->dateTimeBetween('now', '+1 month'),
            'delivered_at' => null,
        ];
    }
}
