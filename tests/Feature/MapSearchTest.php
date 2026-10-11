<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MapSearchTest extends TestCase
{
    use RefreshDatabase;

    private function metresBetween(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6_371_000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function pinned(float $lat, float $lng): Property
    {
        return Property::factory()->available()->create(['latitude' => $lat, 'longitude' => $lng]);
    }

    public function test_the_public_position_is_near_but_never_exactly_the_pin(): void
    {
        $property = $this->pinned(7.0731, 125.6128);

        $distance = $this->metresBetween(7.0731, 125.6128, $property->approx_latitude, $property->approx_longitude);
        $this->assertGreaterThanOrEqual(Property::APPROX_MIN_METRES - 1, $distance);
        $this->assertLessThanOrEqual(Property::APPROX_MAX_METRES + 1, $distance);
        $this->assertLessThan(Property::APPROX_RADIUS_METRES, $distance, 'The real spot must fall inside the circle renters see.');

        // Removing the pin removes the public position too.
        $property->update(['latitude' => null, 'longitude' => null]);
        $this->assertNull($property->refresh()->approx_latitude);
    }

    public function test_renters_only_receive_the_approximate_position(): void
    {
        $property = $this->pinned(7.0812345, 125.6012345);

        $this->get(route('listings.show', $property))
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.location.lat', $property->approx_latitude)
                ->where('listing.location.lng', $property->approx_longitude)
                ->missing('listing.latitude')
                ->missing('listing.longitude'))
            ->assertDontSee('7.0812345')
            ->assertDontSee('125.6012345');
    }

    public function test_search_this_area_uses_the_approximate_position(): void
    {
        $inside = $this->pinned(7.0731, 125.6128);
        $this->pinned(7.30, 125.40); // far away
        Property::factory()->available()->create(); // no pin

        // A box of about 1 km around the pin contains the public position (at most 250 m away).
        $area = ['north' => 7.0821, 'south' => 7.0641, 'east' => 125.6218, 'west' => 125.6038];

        $this->get(route('home', $area))
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.total', 1)
                ->where('listings.data.0.id', $inside->id));
    }

    public function test_map_view_sends_every_pinned_match_as_markers(): void
    {
        $this->pinned(7.0731, 125.6128);
        $this->pinned(7.08, 125.62);
        Property::factory()->available()->create(); // no pin
        Property::factory()->create(['latitude' => 7.07, 'longitude' => 125.61]); // draft

        $this->get(route('home', ['view' => 'list']))
            ->assertInertia(fn (Assert $page) => $page->has('mapListings', 0));

        // Map is the default view.
        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('mapListings', 2)
                ->has('mapListings.0.location.lat')
                ->where('listings.total', 3));
    }

    public function test_an_incomplete_map_area_is_rejected(): void
    {
        $this->get(route('home', ['north' => 7.1]))->assertSessionHasErrors(['south', 'east', 'west']);
    }

    public function test_owner_can_pin_their_listing(): void
    {
        $owner = User::factory()->create();
        $data = [
            'title' => 'Studio near Ateneo',
            'type' => 'apartment',
            'description' => 'Clean and quiet.',
            'price_monthly' => 6500,
            'deposit_months' => 1,
            'advance_months' => 1,
            'bedrooms' => 0,
            'bathrooms' => 1,
            'barangay_id' => Barangay::factory()->create()->id,
            'status' => 'available',
        ];

        $this->actingAs($owner)
            ->post(route('owner.properties.store'), [...$data, 'latitude' => 7.0731])
            ->assertSessionHasErrors('longitude');

        $this->post(route('owner.properties.store'), [...$data, 'latitude' => 7.0731, 'longitude' => 125.6128])
            ->assertSessionHasNoErrors();

        $property = Property::sole();
        $this->assertSame(7.0731, $property->latitude);
        $this->assertNotNull($property->approx_latitude);

        $this->get(route('owner.properties.edit', $property))
            ->assertInertia(fn (Assert $page) => $page->where('property.latitude', 7.0731));
    }
}
