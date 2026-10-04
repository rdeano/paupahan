<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'show_phone', 'messenger_username'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Email verification is optional. Unverified owners are labelled "Unverified"
     * and can have at most this many listings on the public site.
     */
    public const UNVERIFIED_PUBLIC_LISTING_LIMIT = 3;

    protected $attributes = [
        'role' => 'owner',
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'show_phone' => 'boolean',
        ];
    }

    /** @return HasMany<Property, $this> */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /** Maximum public listings, or null for no limit. */
    public function publicListingLimit(): ?int
    {
        return $this->hasVerifiedEmail() ? null : self::UNVERIFIED_PUBLIC_LISTING_LIMIT;
    }

    /** Whether making another listing public would exceed the limit. */
    public function hasReachedPublicListingLimit(?Property $except = null): bool
    {
        $limit = $this->publicListingLimit();

        if ($limit === null) {
            return false;
        }

        return $this->properties()
            ->whereIn('status', PropertyStatus::publicValues())
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->count() >= $limit;
    }

    public function messengerUrl(): ?string
    {
        return $this->messenger_username ? 'https://m.me/'.$this->messenger_username : null;
    }

    /** Fields that are safe to show on public pages. */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'messenger_url' => $this->messengerUrl(),
            'phone' => $this->show_phone ? $this->phone : null,
            'verified' => $this->hasVerifiedEmail(),
            'member_since' => $this->created_at?->format('F Y'),
        ];
    }
}
