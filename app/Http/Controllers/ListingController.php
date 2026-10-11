<?php

namespace App\Http\Controllers;

use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Support\AddressOptions;
use App\Support\Geocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    /** Most markers drawn on the search map at once. */
    private const MAP_MARKER_LIMIT = 500;

    /** How far around a searched place (in degrees, about 1.1 km) listings count as near it. */
    private const NEAR_DEGREES = 0.01;

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
            'view' => ['nullable', Rule::in(['list', 'map'])],
            // Map area, all four or none.
            'north' => ['nullable', 'required_with:south,east,west', 'numeric', 'between:-90,90'],
            'south' => ['nullable', 'required_with:north,east,west', 'numeric', 'between:-90,90', 'lte:north'],
            'east' => ['nullable', 'required_with:north,south,west', 'numeric', 'between:-180,180'],
            'west' => ['nullable', 'required_with:north,south,east', 'numeric', 'between:-180,180', 'lte:east'],
        ]);

        // Where the typed text is on the map. Listings around it match even if their text doesn't.
        $place = filled($filters['q'] ?? null) ? Geocoder::locate($filters['q']) : null;
        $search = $place ? [...$filters, 'near' => self::areaAround($place)] : $filters;

        $listings = Property::publiclyVisible()
            ->with(['coverPhoto', 'street', 'barangay.district'])
            ->search($search)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $property) => $property->toCard());

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            'place' => $place,
            // Every matching pinned listing (not just this page), for the map markers. Map is the default view.
            // When the typed text was found on the map it only moves the map, so markers elsewhere stay visible.
            'mapListings' => ($filters['view'] ?? 'map') === 'map'
                ? Property::publiclyVisible()
                    ->with(['coverPhoto', 'street', 'barangay.district'])
                    ->search($place ? Arr::except($filters, 'q') : $filters)
                    ->whereNotNull('approx_latitude')
                    ->limit(self::MAP_MARKER_LIMIT)
                    ->get()
                    ->map(fn (Property $property) => $property->toCard())
                : [],
            'filters' => (object) $filters,
            'options' => [
                'districts' => AddressOptions::districts(),
                'types' => PropertyType::options(),
                'amenities' => Amenity::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * The found place's own area, widened to at least NEAR_DEGREES (about 1.1 km) in every direction.
     *
     * @param  array{lat: float, lng: float, bounds: array{south: float, north: float, west: float, east: float}}  $place
     * @return array{south: float, north: float, west: float, east: float}
     */
    private static function areaAround(array $place): array
    {
        return [
            'south' => min($place['bounds']['south'], $place['lat'] - self::NEAR_DEGREES),
            'north' => max($place['bounds']['north'], $place['lat'] + self::NEAR_DEGREES),
            'west' => min($place['bounds']['west'], $place['lng'] - self::NEAR_DEGREES),
            'east' => max($place['bounds']['east'], $place['lng'] + self::NEAR_DEGREES),
        ];
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
                'owner' => $property->publicContact(),
                'location_radius' => Property::APPROX_RADIUS_METRES,
            ],
            'preview' => ! $isPublic,
            'editUrl' => match (true) {
                (bool) $user?->isAdmin() => route('admin.properties.edit', $property),
                $user?->id === $property->owner_id => route('owner.properties.edit', $property),
                default => null,
            },
        ])->withViewData('meta', [
            'title' => $property->title,
            'description' => "{$property->type->label()} for rent · {$price} · {$property->publicAddress()}",
            'image' => $cover?->toFrontend()['url'],
            'url' => route('listings.show', $property),
        ]);
    }
}
