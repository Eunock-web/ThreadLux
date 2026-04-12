<?php

namespace Database\Seeders;

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
        // Create 5 users first if they don't exist
        if (\App\Models\User::count() === 0) {
            \App\Models\User::factory(1)->create();
        }

        // Call dedicated seeders
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
