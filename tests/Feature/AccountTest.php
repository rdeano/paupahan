<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Barangay;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_owner_without_sending_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect('/owner');

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertSame(UserRole::Owner, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_owner_can_verify_their_email_later(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/owner?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_suspended_owner_cannot_log_in(): void
    {
        $user = User::factory()->create(['email' => 'bad@example.com', 'status' => UserStatus::Suspended]);

        $this->post(route('login'), ['email' => 'bad@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_owner_is_logged_out_when_suspended_mid_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->get(route('owner.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_profile_normalizes_messenger_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('owner.profile.update'), ['name' => 'Juan', 'messenger_username' => 'https://m.me/juan.delacruz?ref=x', 'show_phone' => false])
            ->assertSessionHasNoErrors();

        $this->assertSame('juan.delacruz', $user->fresh()->messenger_username);

        $this->actingAs($user)
            ->put(route('owner.profile.update'), ['name' => 'Juan', 'messenger_username' => 'not a username!'])
            ->assertSessionHasErrors('messenger_username');
    }

    public function test_only_admins_can_reach_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('owner.dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_suspend_an_owner_which_hides_their_listings(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();
        $property = Property::factory()->available()->create();

        $this->actingAs($admin)->patch(route('admin.owners.status', $property->owner), ['suspend' => true]);

        $this->assertSame(UserStatus::Suspended, $property->owner->fresh()->status);
        $this->assertSame(0, Property::publiclyVisible()->count());
    }

    public function test_admin_can_approve_a_pending_street(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();
        $street = Street::create([
            'barangay_id' => Barangay::factory()->create()->id,
            'name' => 'New Road',
            'status' => Street::PENDING,
        ]);

        $this->actingAs($admin)->patch(route('admin.streets.approve', $street));

        $this->assertSame(Street::APPROVED, $street->fresh()->status);
    }
}
