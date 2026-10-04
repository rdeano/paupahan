<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyRequest;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\User;
use App\Rules\WithinPublicListingLimit;
use App\Support\AddressOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $owner = $request->user();

        if ($owner->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $properties = $owner->properties()
            ->with(['coverPhoto', 'street', 'barangay.district'])
            ->withCount('photos')
            ->latest()
            ->get()
            ->map(fn (Property $property) => [
                ...$property->toCard(),
                'status_label' => $property->status->label(),
                'photos_count' => $property->photos_count,
                'updated_at' => $property->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Owner/Dashboard', [
            'properties' => $properties,
            'statuses' => PropertyStatus::options(),
            'missingMessenger' => ! $owner->messenger_username,
            'verification' => [
                'verified' => $owner->hasVerifiedEmail(),
                'just_verified' => $request->boolean('verified'),
                'limit' => User::UNVERIFIED_PUBLIC_LISTING_LIMIT,
                'public_count' => $properties->whereIn('status', PropertyStatus::publicValues())->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Owner/Properties/Form', [
            'property' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        $property = $request->user()->properties()->create($request->propertyData());
        $property->amenities()->sync($request->validated('amenities', []));

        return redirect()->route('owner.properties.edit', $property)
            ->with('success', 'Property saved. Now add some photos.');
    }

    public function edit(Request $request, Property $property): Response
    {
        $this->authorize('update', $property);

        $property->load(['photos', 'amenities', 'street']);

        return Inertia::render('Owner/Properties/Form', [
            'property' => [
                ...$property->only([
                    'id', 'slug', 'title', 'description', 'price_monthly', 'deposit_months', 'advance_months',
                    'min_stay_months', 'bedrooms', 'bathrooms', 'floor_area_sqm', 'max_occupants',
                    'barangay_id', 'street_id', 'house_number', 'landmark', 'house_rules',
                ]),
                'type' => $property->type->value,
                'status' => $property->status->value,
                'available_from' => $property->available_from?->toDateString(),
                'street_name' => $property->street?->name,
                'amenities' => $property->amenities->pluck('id'),
                'photos' => $property->photos->map(fn (PropertyPhoto $photo) => $photo->toFrontend()),
                'max_photos' => PropertyPhotoController::MAX_PHOTOS,
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        $property->update($request->propertyData());
        $property->amenities()->sync($request->validated('amenities', []));

        return back()->with('success', 'Property updated.');
    }

    public function updateStatus(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validate([
            'status' => ['required', Rule::enum(PropertyStatus::class), new WithinPublicListingLimit($request->user(), $property)],
        ]));

        return back()->with('success', 'Status updated.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->photos->each->delete();
        $property->delete();

        return redirect()->route('owner.dashboard')->with('success', 'Property deleted.');
    }

    private function formOptions(): array
    {
        return [
            'unverifiedLimit' => User::UNVERIFIED_PUBLIC_LISTING_LIMIT,
            'options' => [
                'districts' => AddressOptions::districts(),
                'types' => PropertyType::options(),
                'statuses' => PropertyStatus::options(),
                'amenities' => Amenity::orderBy('name')->get(['id', 'name']),
            ],
        ];
    }
}
