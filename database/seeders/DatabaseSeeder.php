<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the reference data every environment needs.
     * For sample owners and listings in local development, also run DemoSeeder.
     */
    public function run(): void
    {
        $this->call([
            AddressSeeder::class,
            AmenitySeeder::class,
        ]);
    }
}
