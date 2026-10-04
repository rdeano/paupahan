<?php

namespace App\Http\Controllers;

use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Support\AddressOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'integer'],
            'barangay' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::enum(PropertyType::class)],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc'])],
        ]);

        $listings = Property::publiclyVisible()
            ->with(['coverPhoto', 'street', 'barangay.district'])
            ->search($filters)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $property) => $property->toCard());

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            'filters' => (object) $filters,
            'options' => [
                'districts' => AddressOptions::districts(),
                'types' => PropertyType::options(),
                'amenities' => Amenity::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function show(Request $request, Property $property): Response
    {
        $isPublic = Property::publiclyVisible()->whereKey($property->id)->exists();
        $user = $request->user();
        $canPreview = $user && ($user->id === $property->owner_id || $user->isAdmin());

        abort_unless($isPublic || $canPreview, 404);

        $property->load(['photos', 'amenities', 'owner', 'street', 'barangay.district']);

        $cover = $property->photos->firstWhere('is_cover', true) ?? $property->photos->first();
        $price = '₱'.number_format($property->price_monthly).'/month';

        return Inertia::render('Listings/Show', [
            'listing' => [
                ...$property->toCard(),
                'description' => $property->description,
                'house_rules' => $property->house_rules,
                'deposit_months' => $property->deposit_months,
                'advance_months' => $property->advance_months,
                'min_stay_months' => $property->min_stay_months,
                'floor_area_sqm' => $property->floor_area_sqm,
                'max_occupants' => $property->max_occupants,
                'available_from' => $property->available_from?->toDateString(),
                'published_at' => $property->published_at?->toDateString(),
                'status_label' => $property->status->label(),
                'photos' => $property->photos->map(fn (PropertyPhoto $photo) => $photo->toFrontend()),
                'amenities' => $property->amenities->pluck('name'),
                'owner' => $property->owner->toPublicArray(),
            ],
            'preview' => ! $isPublic,
            'canEdit' => $user?->id === $property->owner_id,
        ])->withViewData('meta', [
            'title' => $property->title,
            'description' => "{$property->type->label()} for rent · {$price} · {$property->publicAddress()}",
            'image' => $cover?->toFrontend()['url'],
            'url' => route('listings.show', $property),
        ]);
    }
}
