<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\UserStatus;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'type', 'description', 'price_monthly', 'deposit_months', 'advance_months',
    'min_stay_months', 'bedrooms', 'bathrooms', 'floor_area_sqm', 'max_occupants',
    'barangay_id', 'street_id', 'house_number', 'landmark', 'latitude', 'longitude',
    'house_rules', 'status', 'available_from',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'available_from' => 'date',
            'published_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Barangay, $this> */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /** @return BelongsTo<Street, $this> */
    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    /** @return HasMany<PropertyPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('sort_order');
    }

    /** @return HasOne<PropertyPhoto, $this> */
    public function coverPhoto(): HasOne
    {
        return $this->hasOne(PropertyPhoto::class)->where('is_cover', true);
    }

    /** @return BelongsToMany<Amenity, $this> */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    /** Listings visible on the public site: published, public status, owner active. */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->whereIn('status', PropertyStatus::publicValues())
            ->whereNotNull('published_at')
            ->whereHas('owner', fn (Builder $q) => $q->where('status', UserStatus::Active->value));
    }

    public function isPublicStatus(): bool
    {
        return in_array($this->status->value, PropertyStatus::publicValues(), true);
    }

    /**
     * Apply public search filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeSearch(Builder $query, array $filters): void
    {
        // Every word must match somewhere: title, landmark, street, barangay or district.
        $text = str_replace(['%', '_', '\\'], ' ', (string) ($filters['q'] ?? ''));
        $words = array_filter(preg_split('/\s+/', trim($text)));
        foreach (array_slice($words, 0, 6) as $word) {
            $like = '%'.$word.'%';
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('landmark', 'like', $like)
                ->orWhereHas('street', fn (Builder $s) => $s->where('name', 'like', $like))
                ->orWhereHas('barangay', fn (Builder $b) => $b
                    ->where('name', 'like', $like)
                    ->orWhereHas('district', fn (Builder $d) => $d->where('name', 'like', $like))));
        }

        $query
            ->when($filters['district'] ?? null, fn (Builder $q, $id) => $q->whereHas('barangay', fn (Builder $b) => $b->where('district_id', $id)))
            ->when($filters['barangay'] ?? null, fn (Builder $q, $id) => $q->where('barangay_id', $id))
            ->when($filters['street'] ?? null, fn (Builder $q, $id) => $q->where('street_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['min_price'] ?? null, fn (Builder $q, $min) => $q->where('price_monthly', '>=', $min))
            ->when($filters['max_price'] ?? null, fn (Builder $q, $max) => $q->where('price_monthly', '<=', $max))
            ->when($filters['bedrooms'] ?? null, fn (Builder $q, $n) => $q->where('bedrooms', '>=', $n));

        foreach ((array) ($filters['amenities'] ?? []) as $amenityId) {
            $query->whereHas('amenities', fn (Builder $q) => $q->where('amenities.id', $amenityId));
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price_monthly')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price_monthly')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /** Public address: street, barangay and landmark only. The house number stays private. */
    public function publicAddress(): string
    {
        return collect([
            $this->street?->name,
            $this->barangay?->name,
            $this->barangay?->district ? $this->barangay->district->name.' District' : null,
            'Davao City',
        ])->filter()->implode(', ');
    }

    /** Data for listing cards. */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'price_monthly' => $this->price_monthly,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'address' => $this->publicAddress(),
            'landmark' => $this->landmark,
            'cover_url' => $this->coverPhoto?->toFrontend()['thumb_url'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Property $property) {
            $property->slug ??= Str::slug(Str::limit($property->title, 60, '')).'-'.Str::lower(Str::random(6));
        });

        static::saving(function (Property $property) {
            if ($property->status !== PropertyStatus::Draft && $property->published_at === null) {
                $property->published_at = now();
            }
        });
    }
}
