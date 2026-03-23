<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::all()->each(function ($user) {
            \App\Models\Transaction::factory(5)->create([
                'user_id' => $user->id,
                'product_id' => \App\Models\Product::all()->random()->id,
            ]);
        });
    }
}
