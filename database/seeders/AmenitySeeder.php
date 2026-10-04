<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            'Wi-Fi',
            'Air conditioning',
            'Private bathroom (own CR)',
            'Kitchen access',
            'Laundry area',
            'Parking',
            'Motorcycle parking',
            'Furnished',
            'Pets allowed',
            'Water included',
            'Electricity included',
            'CCTV / security',
            'Gated',
        ];

        foreach ($amenities as $name) {
            Amenity::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
