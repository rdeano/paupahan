<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Enums\UserStatus;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_public_listings_from_active_owners_are_shown(): void
    {
        $visible = Property::factory()->available()->create(['title' => 'Visible place']);
        $fromUnverified = Property::factory()->available()->for(User::factory()->unverified(), 'owner')->create();
        Property::factory()->create(['status' => PropertyStatus::Draft]);
        Property::factory()->create(['status' => PropertyStatus::Occupied]);
        Property::factory()->available()->for(User::factory()->state(['status' => UserStatus::Suspended]), 'owner')->create();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Index')
                ->has('listings.data', 2)
                ->where('listings.data.0.id', $fromUnverified->id)
                ->where('listings.data.1.id', $visible->id));
    }

    public function test_listing_page_shows_whether_the_owner_is_verified(): void
    {
        $verified = Property::factory()->available()->create();
        $unverified = Property::factory()->available()->for(User::factory()->unverified(), 'owner')->create();

        $this->get(route('listings.show', $verified))
            ->assertInertia(fn (Assert $page) => $page->where('listing.owner.verified', true));
        $this->get(route('listings.show', $unverified))
            ->assertInertia(fn (Assert $page) => $page->where('listing.owner.verified', false));
        $this->get(route('owners.show', $unverified->owner))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('owner.verified', false));
    }

    public function test_search_filters_by_price_type_and_barangay(): void
    {
        $matina = Barangay::factory()->create(['name' => 'Matina Crossing']);
        $buhangin = Barangay::factory()->create(['name' => 'Buhangin Proper']);

        $match = Property::factory()->available()->create(['barangay_id' => $matina->id, 'price_monthly' => 5000, 'type' => 'room']);
        Property::factory()->available()->create(['barangay_id' => $matina->id, 'price_monthly' => 15000, 'type' => 'room']);
        Property::factory()->available()->create(['barangay_id' => $buhangin->id, 'price_monthly' => 5000, 'type' => 'room']);
        Property::factory()->available()->create(['barangay_id' => $matina->id, 'price_monthly' => 5000, 'type' => 'condo']);

        $this->get('/?'.http_build_query(['barangay' => $matina->id, 'max_price' => 8000, 'type' => 'room']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $match->id));
    }

    public function test_text_search_matches_street_barangay_district_and_landmark(): void
    {
        $district = District::create(['name' => 'Talomo']);
        $barangay = Barangay::factory()->create(['name' => 'Bago Aplaya', 'district_id' => $district->id]);
        $street = Street::create(['barangay_id' => $barangay->id, 'name' => 'Quimpo Boulevard']);

        $property = Property::factory()->available()->create([
            'barangay_id' => $barangay->id,
            'street_id' => $street->id,
            'landmark' => 'near Ecoland terminal',
        ]);
        Property::factory()->available()->create([
            'barangay_id' => Barangay::factory()->create(['name' => 'Lanang'])->id,
            'landmark' => 'near SM Lanang',
        ]);

        foreach (['quimpo', 'aplaya', 'talomo', 'ecoland', 'Quimpo Talomo'] as $q) {
            $this->get('/?q='.urlencode($q))
                ->assertInertia(fn (Assert $page) => $page
                    ->has('listings.data', 1)
                    ->where('listings.data.0.id', $property->id));
        }
    }

    public function test_listing_page_hides_the_house_number(): void
    {
        $property = Property::factory()->available()->create(['house_number' => 'Unit 4B Secret Tower']);

        $this->get(route('listings.show', $property))
            ->assertOk()
            ->assertDontSee('Unit 4B Secret Tower')
            ->assertInertia(fn (Assert $page) => $page->component('Listings/Show')->missing('listing.house_number'));
    }

    public function test_listing_page_links_to_owner_messenger(): void
    {
        $owner = User::factory()->create(['messenger_username' => 'juan.delacruz']);
        $property = Property::factory()->available()->for($owner, 'owner')->create();

        $this->get(route('listings.show', $property))
            ->assertInertia(fn (Assert $page) => $page->where('listing.owner.messenger_url', 'https://m.me/juan.delacruz'));
    }

    public function test_draft_listing_is_hidden_from_guests_but_previewable_by_its_owner(): void
    {
        $property = Property::factory()->create(['status' => PropertyStatus::Draft]);

        $this->get(route('listings.show', $property))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('listings.show', $property))->assertNotFound();

        $this->actingAs($property->owner)
            ->get(route('listings.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('preview', true));
    }

    public function test_listing_page_has_server_rendered_open_graph_tags(): void
    {
        $property = Property::factory()->available()->create(['title' => 'Cozy studio in Obrero']);

        $this->get(route('listings.show', $property))
            ->assertSee('<meta property="og:title" content="Cozy studio in Obrero">', false);
    }
}
