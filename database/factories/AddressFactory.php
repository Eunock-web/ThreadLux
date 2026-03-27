<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nom_complet' => $this->faker->name(),
            'rue' => $this->faker->streetAddress(),
            'ville' => $this->faker->city(),
            'code_postal' => $this->faker->postcode(),
            'pays' => $this->faker->country(),
            'phone' => $this->faker->phoneNumber(),
            'is_default' => $this->faker->boolean(),
        ];
    }
}
