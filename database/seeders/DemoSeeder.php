<?php

namespace Database\Seeders;

use App\Enums\PropertyStatus;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample owners and listings for local development only.
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $amenityIds = Amenity::pluck('id');

        User::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Demo Owner One', 'email' => 'owner1@example.com', 'messenger_username' => 'demo.owner.one'],
                ['name' => 'Demo Owner Two', 'email' => 'owner2@example.com', 'messenger_username' => 'demo.owner.two'],
                ['name' => 'Demo Owner Three', 'email' => 'owner3@example.com', 'messenger_username' => null],
            )
            ->create()
            ->each(function (User $owner) use ($amenityIds) {
                Property::factory()
                    ->count(5)
                    ->for($owner, 'owner')
                    ->state(['status' => PropertyStatus::Available])
                    ->create()
                    ->each(fn (Property $property) => $property->amenities()->sync(
                        $amenityIds->random(min(4, $amenityIds->count()))
                    ));
            });
    }
}
