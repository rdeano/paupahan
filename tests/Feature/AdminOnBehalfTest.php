<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOnBehalfTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->save();

        return $admin;
    }

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

    public function test_owners_cannot_use_the_admin_pages(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->for(User::factory(), 'owner')->create();

        $this->actingAs($owner);
        $this->get(route('admin.owners.create'))->assertForbidden();
        $this->post(route('admin.owners.store'), ['name' => 'Someone'])->assertForbidden();
        $this->get(route('admin.properties.edit', $property))->assertForbidden();
        $this->put(route('admin.properties.update', $property), $this->validData())->assertForbidden();
        $this->get(route('admin.properties.create'))->assertForbidden();
        $this->post(route('admin.properties.store'), $this->validData(['owner_id' => $owner->id]))->assertForbidden();
    }

    public function test_admin_can_add_an_owner_without_an_email_who_counts_as_verified(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.owners.store'), [
                'name' => 'Aling Nena',
                'messenger_username' => 'https://m.me/aling.nena',
            ])
            ->assertSessionHasNoErrors();

        $owner = User::where('name', 'Aling Nena')->firstOrFail();
        $this->assertNull($owner->email);
        $this->assertNull($owner->password);
        $this->assertSame('aling.nena', $owner->messenger_username);
        $this->assertSame(UserRole::Owner, $owner->role);
        $this->assertTrue($owner->hasVerifiedEmail());
    }

    public function test_admin_rejects_an_invalid_messenger_link(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.owners.store'), ['name' => 'Aling Nena', 'messenger_username' => 'not a link!'])
            ->assertSessionHasErrors('messenger_username');
    }

    public function test_admin_can_set_messenger_link_but_not_change_an_existing_login_email(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $this->actingAs($this->admin())
            ->put(route('admin.owners.update', $owner), [
                'name' => 'New Name',
                'email' => 'hijack@example.com',
                'messenger_username' => 'm.me/new.handle',
            ])
            ->assertSessionHasNoErrors();

        $owner->refresh();
        $this->assertSame('New Name', $owner->name);
        $this->assertSame('owner@example.com', $owner->email);
        $this->assertSame('new.handle', $owner->messenger_username);
    }

    public function test_admin_can_add_an_email_to_an_owner_without_one(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.owners.store'), ['name' => 'Aling Nena']);
        $owner = User::where('name', 'Aling Nena')->firstOrFail();

        $this->put(route('admin.owners.update', $owner), ['name' => 'Aling Nena', 'email' => 'Nena@Example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('nena@example.com', $owner->refresh()->email);
    }

    public function test_admin_cannot_edit_another_admin_through_owner_pages(): void
    {
        $other = $this->admin();

        $this->actingAs($this->admin())
            ->get(route('admin.owners.edit', $other))
            ->assertNotFound();
    }

    public function test_admin_posts_a_listing_with_its_own_contact_not_tied_to_an_owner(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.properties.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('adminListing', true));

        $this->post(route('admin.properties.store'), $this->validData([
            'contact_name' => 'Aling Nena',
            'contact_messenger' => 'https://m.me/aling.nena',
            'contact_phone' => '0917 123 4567',
            'new_street' => 'Rizal St',
        ]))->assertSessionHasNoErrors();

        $property = Property::sole();
        $this->assertTrue($property->owner->is($admin));
        $this->assertSame('aling.nena', $property->contact_messenger);
        $this->assertSame(Street::APPROVED, $property->street->status);
        $this->assertSame(1, User::count(), 'No owner account is created.');

        // Renters see the listing's contact with "Posted by Admin"; registered owners' listings are unchanged.
        $this->get(route('listings.show', $property))
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.owner.id', null)
                ->where('listing.owner.name', 'Aling Nena')
                ->where('listing.owner.messenger_url', 'https://m.me/aling.nena')
                ->where('listing.owner.phone', '0917 123 4567')
                ->where('listing.owner.added_by_admin', true)
                ->where('editUrl', route('admin.properties.edit', $property)));
        $registered = Property::factory()->available()->create();
        $this->get(route('listings.show', $registered))
            ->assertInertia(fn (Assert $page) => $page->where('listing.owner.added_by_admin', false));
    }

    public function test_each_admin_listing_has_its_own_messenger_link(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.properties.store'), $this->validData(['title' => 'First', 'contact_name' => 'Nena', 'contact_messenger' => 'nena.santos']));
        $this->post(route('admin.properties.store'), $this->validData(['title' => 'Second', 'contact_name' => 'Pedro', 'contact_messenger' => 'm.me/pedro.cruz']));

        $this->assertSame(
            ['First' => 'https://m.me/nena.santos', 'Second' => 'https://m.me/pedro.cruz'],
            Property::orderBy('title')->get()->mapWithKeys(fn (Property $p) => [$p->title => $p->publicContact()['messenger_url']])->all(),
        );
    }

    public function test_admin_listing_needs_a_contact(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.properties.store'), $this->validData())
            ->assertSessionHasErrors(['contact_name', 'contact_messenger']);

        $this->post(route('admin.properties.store'), $this->validData(['contact_name' => 'Nena', 'contact_messenger' => 'not a link!']))
            ->assertSessionHasErrors('contact_messenger');

        // A mobile number alone is enough.
        $this->post(route('admin.properties.store'), $this->validData(['contact_name' => 'Nena', 'contact_phone' => '09171234567']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Property::count());
    }

    public function test_owners_cannot_set_a_listing_contact(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->put(route('owner.properties.update', $property), $this->validData(['contact_name' => 'Someone else']))
            ->assertSessionHasNoErrors();

        $this->assertNull($property->refresh()->contact_name);
    }

    public function test_admin_can_edit_an_owners_listing_and_manage_its_photos(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $street = Street::create([
            'barangay_id' => Barangay::factory()->create()->id,
            'name' => 'Owner Suggested St',
            'status' => Street::PENDING,
            'created_by' => $owner->id,
        ]);
        $property = Property::factory()->for($owner, 'owner')->create([
            'barangay_id' => $street->barangay_id,
            'street_id' => $street->id,
        ]);

        $this->actingAs($this->admin());

        $this->get(route('admin.properties.edit', $property))->assertOk();

        // The owner's own pending street is kept when the admin saves.
        $this->put(route('admin.properties.update', $property), $this->validData([
            'title' => 'Edited by admin',
            'barangay_id' => $street->barangay_id,
            'street_id' => $street->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Edited by admin', $property->refresh()->title);
        $this->assertTrue($property->owner->is($owner));

        $this->post(route('owner.photos.store', $property), ['photos' => [UploadedFile::fake()->image('room.jpg', 1200, 900)]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $property->photos()->count());
    }
}
