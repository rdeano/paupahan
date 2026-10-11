<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Support\Geocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AddressLookupTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSmLanang(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([[
            'display_name' => 'SM Lanang Premier, J.P. Laurel Avenue, Davao City',
            'lat' => '7.0990000',
            'lon' => '125.6310000',
            'boundingbox' => ['7.0980000', '7.1000000', '125.6300000', '125.6320000'],
        ]])]);
    }

    public function test_the_map_goes_to_a_typed_place_even_with_no_listings(): void
    {
        $this->fakeSmLanang();

        $this->get(route('home', ['q' => 'SM Lanang']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.total', 0)
                ->where('place.lat', 7.099)
                ->where('place.lng', 125.631)
                ->where('place.bounds.south', 7.098)
                ->where('place.bounds.east', 125.632));

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/search')
            && $request['q'] === 'SM Lanang, Davao City, Philippines'
            && $request['countrycodes'] === 'ph'
            && str_starts_with($request->header('User-Agent')[0], config('app.name').'/'));
    }

    public function test_places_are_cached_so_nominatim_is_asked_once(): void
    {
        $this->fakeSmLanang();

        Geocoder::locate('SM Lanang');
        RateLimiter::clear('nominatim');
        Geocoder::locate('  sm   lanang ');

        Http::assertSentCount(1);
    }

    public function test_lookups_are_limited_to_one_per_second(): void
    {
        $this->fakeSmLanang();

        $this->assertNotNull(Geocoder::locate('SM Lanang'));
        $this->assertNull(Geocoder::locate('Matina Town Square'), 'A second new lookup in the same second is skipped.');

        Http::assertSentCount(1);
    }

    public function test_a_found_place_shows_nearby_listings_and_keeps_the_map_full(): void
    {
        $this->fakeSmLanang();
        $nearby = Property::factory()->available()->create(['title' => 'Studio', 'latitude' => 7.1010, 'longitude' => 125.6330]);
        $mentions = Property::factory()->available()->create(['title' => 'Room across SM Lanang', 'latitude' => 7.20, 'longitude' => 125.50]);
        $elsewhere = Property::factory()->available()->create(['title' => 'House', 'latitude' => 7.05, 'longitude' => 125.58]);
        $tooPricey = Property::factory()->available()->create(['title' => 'Condo', 'price_monthly' => 99_000, 'latitude' => 7.05, 'longitude' => 125.58]);

        $this->get(route('home', ['q' => 'SM Lanang', 'max_price' => 50_000]))
            ->assertInertia(fn (Assert $page) => $page
                // The list: listings near the place, or that mention it.
                ->where('listings.total', 2)
                ->where('listings.data', fn ($data) => collect($data)->pluck('id')->sort()->values()->all() === collect([$nearby->id, $mentions->id])->sort()->values()->all())
                // The map: every listing that matches the other filters, so it never goes empty.
                ->where('mapListings', fn ($markers) => collect($markers)->pluck('id')->sort()->values()->all() === collect([$nearby->id, $mentions->id, $elsewhere->id])->sort()->values()->all()));

        $this->assertNotNull($tooPricey);
    }

    public function test_text_that_is_not_a_place_still_filters_the_map(): void
    {
        $match = Property::factory()->available()->create(['title' => 'Room with aircon', 'latitude' => 7.07, 'longitude' => 125.61]);
        Property::factory()->available()->create(['title' => 'House', 'latitude' => 7.05, 'longitude' => 125.58]);

        $this->get(route('home', ['q' => 'aircon']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('place', null)
                ->has('mapListings', 1)
                ->where('mapListings.0.id', $match->id));
    }

    public function test_short_queries_do_not_look_anything_up(): void
    {
        $this->get(route('home', ['q' => 'ab']))
            ->assertInertia(fn (Assert $page) => $page->where('place', null));

        Http::assertNothingSent();
    }

    public function test_a_failed_lookup_does_not_break_search(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response('Server error', 500)]);

        $this->get(route('home', ['q' => 'SM Lanang']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('place', null));
    }
}
