<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Models\Amenity;
use App\Models\Barangay;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OwnerPropertyTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return [
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
            ...$overrides,
        ];
    }

    public function test_guests_cannot_access_the_workspace_but_unverified_owners_can(): void
    {
        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('verification.verified', false));
    }

    public function test_unverified_owner_can_have_at_most_three_public_listings(): void
    {
        $owner = User::factory()->unverified()->create();
        Property::factory()->count(3)->available()->for($owner, 'owner')->create();
        $draft = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner);

        // A fourth public listing is refused, whether created, edited or switched from the dashboard.
        $this->post(route('owner.properties.store'), $this->validData(['status' => 'available']))
            ->assertSessionHasErrors('status');
        $this->put(route('owner.properties.update', $draft), $this->validData(['status' => 'reserved']))
            ->assertSessionHasErrors('status');
        $this->patch(route('owner.properties.status', $draft), ['status' => 'available'])
            ->assertSessionHasErrors('status');

        // Drafts are still allowed, and editing an already-public listing keeps working.
        $this->post(route('owner.properties.store'), $this->validData(['status' => 'draft']))
            ->assertSessionHasNoErrors();
        $public = $owner->properties()->where('status', 'available')->first();
        $this->put(route('owner.properties.update', $public), $this->validData(['status' => 'available']))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $owner->properties()->publiclyVisible()->count());
    }

    public function test_verified_owner_has_no_public_listing_limit(): void
    {
        $owner = User::factory()->create();
        Property::factory()->count(3)->available()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post(route('owner.properties.store'), $this->validData(['status' => 'available']))
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $owner->properties()->publiclyVisible()->count());
    }

    public function test_dashboard_lists_only_the_owners_own_properties(): void
    {
        $owner = User::factory()->create();
        $mine = Property::factory()->for($owner, 'owner')->create();
        Property::factory()->create();

        $this->actingAs($owner)
            ->get(route('owner.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Dashboard')
                ->has('properties', 1)
                ->where('properties.0.id', $mine->id));
    }

    public function test_owner_can_create_a_property_and_it_is_published(): void
    {
        $owner = User::factory()->create();
        $amenity = Amenity::create(['name' => 'Wi-Fi', 'slug' => 'wi-fi']);

        $this->actingAs($owner)
            ->post(route('owner.properties.store'), $this->validData(['amenities' => [$amenity->id]]))
            ->assertRedirect();

        $property = $owner->properties()->first();
        $this->assertSame('Studio near Ateneo', $property->title);
        $this->assertNotNull($property->published_at);
        $this->assertTrue($property->amenities->contains($amenity));
    }

    public function test_a_typed_street_is_saved_as_pending_for_review(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('owner.properties.store'), $this->validData(['new_street' => 'Purok 5 Road']))
            ->assertRedirect();

        $street = Street::where('name', 'Purok 5 Road')->first();
        $this->assertSame(Street::PENDING, $street->status);
        $this->assertSame($owner->id, $street->created_by);
        $this->assertSame($street->id, $owner->properties()->first()->street_id);
    }

    public function test_street_must_belong_to_the_selected_barangay(): void
    {
        $otherStreet = Street::create(['barangay_id' => Barangay::factory()->create()->id, 'name' => 'Elsewhere St']);

        $this->actingAs(User::factory()->create())
            ->post(route('owner.properties.store'), $this->validData(['street_id' => $otherStreet->id]))
            ->assertSessionHasErrors('street_id');
    }

    public function test_owner_cannot_touch_another_owners_property(): void
    {
        $intruder = User::factory()->create();
        $property = Property::factory()->create();
        $photo = $property->photos()->create(['path' => 'a.jpg', 'thumb_path' => 'a_thumb.jpg']);

        $this->actingAs($intruder);

        $this->get(route('owner.properties.edit', $property))->assertForbidden();
        $this->put(route('owner.properties.update', $property), $this->validData())->assertForbidden();
        $this->patch(route('owner.properties.status', $property), ['status' => 'archived'])->assertForbidden();
        $this->delete(route('owner.properties.destroy', $property))->assertForbidden();
        $this->post(route('owner.photos.store', $property), ['photos' => [UploadedFile::fake()->image('x.jpg')]])->assertForbidden();
        $this->patch(route('owner.photos.cover', [$property, $photo]))->assertForbidden();
        $this->delete(route('owner.photos.destroy', [$property, $photo]))->assertForbidden();

        $this->assertNotSoftDeleted($property);
        $this->assertSame(PropertyStatus::Draft, $property->fresh()->status);
    }

    public function test_uploaded_photos_are_resized_and_the_first_becomes_the_cover(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $property = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post(route('owner.photos.store', $property), [
                'photos' => [UploadedFile::fake()->image('a.jpg', 3000, 2000), UploadedFile::fake()->image('b.png', 800, 600)],
            ])
            ->assertSessionHasNoErrors();

        $photos = $property->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertTrue($photos[0]->is_cover);
        $this->assertFalse($photos[1]->is_cover);

        Storage::disk('public')->assertExists([$photos[0]->path, $photos[0]->thumb_path]);
        [$width] = getimagesizefromstring(Storage::disk('public')->get($photos[0]->path));
        $this->assertSame(1600, $width);
    }

    public function test_a_property_can_have_at_most_five_photos(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $image = fn () => UploadedFile::fake()->image('p.jpg', 400, 300);

        $this->actingAs($owner);

        $this->post(route('owner.photos.store', $property), ['photos' => array_map(fn () => $image(), range(1, 6))])
            ->assertSessionHasErrors('photos');
        $this->assertSame(0, $property->photos()->count());

        $this->post(route('owner.photos.store', $property), ['photos' => array_map(fn () => $image(), range(1, 4))])
            ->assertSessionHasNoErrors();
        $this->post(route('owner.photos.store', $property), ['photos' => [$image(), $image()]])
            ->assertSessionHasErrors('photos');
        $this->post(route('owner.photos.store', $property), ['photos' => [$image()]])
            ->assertSessionHasNoErrors();
        $this->post(route('owner.photos.store', $property), ['photos' => [$image()]])
            ->assertSessionHasErrors('photos');

        $this->assertSame(5, $property->photos()->count());
    }

    public function test_deleting_the_cover_photo_promotes_the_next_one(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $cover = $property->photos()->create(['path' => 'a.jpg', 'thumb_path' => 'a_t.jpg', 'sort_order' => 1, 'is_cover' => true]);
        $next = $property->photos()->create(['path' => 'b.jpg', 'thumb_path' => 'b_t.jpg', 'sort_order' => 2]);

        $this->actingAs($owner)->delete(route('owner.photos.destroy', [$property, $cover]));

        $this->assertModelMissing($cover);
        $this->assertTrue($next->fresh()->is_cover);
    }

    public function test_street_lookup_shows_approved_streets_and_the_owners_own_pending_ones(): void
    {
        $owner = User::factory()->create();
        $barangay = Barangay::factory()->create();
        Street::create(['barangay_id' => $barangay->id, 'name' => 'Approved St']);
        Street::create(['barangay_id' => $barangay->id, 'name' => 'My Pending St', 'status' => Street::PENDING, 'created_by' => $owner->id]);
        Street::create(['barangay_id' => $barangay->id, 'name' => 'Someone Else Pending', 'status' => Street::PENDING, 'created_by' => User::factory()->create()->id]);

        $this->actingAs($owner)
            ->getJson(route('owner.streets.lookup', $barangay))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Approved St'])
            ->assertJsonFragment(['name' => 'My Pending St', 'pending' => true]);
    }
}
