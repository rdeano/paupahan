<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Concerns\RendersPropertyForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use App\Models\User;
use App\Rules\WithinPublicListingLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    use RendersPropertyForm;

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
        return Inertia::render('Owner/Properties/Form', $this->propertyFormProps());
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        $property = $request->user()->properties()->create($request->propertyData());
        $property->amenities()->sync($request->validated('amenities', []));

        return redirect()->route('owner.properties.edit', $property)
            ->with('success', 'Property saved. Now add some photos.');
    }

    public function edit(Property $property): Response
    {
        $this->authorize('update', $property);

        return Inertia::render('Owner/Properties/Form', $this->propertyFormProps($property));
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
            'status' => ['required', Rule::enum(PropertyStatus::class), new WithinPublicListingLimit($property->owner, $property)],
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
}
