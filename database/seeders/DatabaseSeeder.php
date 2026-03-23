<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 5 users first
        \App\Models\User::factory(5)->create();

        // Call dedicated seeders
        $this->call([
            ProductSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
