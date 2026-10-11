<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Concerns\RendersPropertyForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Listing moderation, posting listings for owners without an account, and editing any owner's listing.
 */
class PropertyController extends Controller
{
    use RendersPropertyForm;

    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $properties = Property::with(['owner', 'street', 'barangay.district'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Property $property) => [
                'id' => $property->id,
                'slug' => $property->slug,
                'title' => $property->title,
                'owner' => $property->publicContact()['name'],
                'posted_by_admin' => $property->postedByAdmin(),
                'address' => $property->publicAddress(),
                'price_monthly' => $property->price_monthly,
                'status' => $property->status->value,
                'status_label' => $property->status->label(),
                'created_at' => $property->created_at->toDateString(),
            ]);

        return Inertia::render('Admin/Properties', [
            'properties' => $properties,
            'filters' => ['q' => $search],
        ]);
    }

    /** Post a listing for an owner who has no account. Renters contact the person named on the listing. */
    public function create(): Response
    {
        return Inertia::render('Owner/Properties/Form', [
            ...$this->propertyFormProps(),
            'adminListing' => true,
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        $property = $request->user()->properties()->create($request->propertyData());
        $property->amenities()->sync($request->validated('amenities', []));

        return redirect()->route('admin.properties.edit', $property)
            ->with('success', 'Listing saved. Now add some photos.');
    }

    public function edit(Property $property): Response
    {
        $owner = $property->owner;

        return Inertia::render('Owner/Properties/Form', [
            ...$this->propertyFormProps($property),
            ...($owner->isAdmin()
                ? ['adminListing' => true]
                : ['onBehalfOf' => $this->ownerSummary($owner)]),
        ]);
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        $property->update($request->propertyData());
        $property->amenities()->sync($request->validated('amenities', []));

        return back()->with('success', 'Listing updated.');
    }

    public function unpublish(Property $property): RedirectResponse
    {
        $property->update(['status' => PropertyStatus::Archived]);

        return back()->with('success', 'Listing unpublished.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $property->photos->each->delete();
        $property->delete();

        return back()->with('success', 'Listing removed.');
    }

    private function ownerSummary(User $owner): array
    {
        return [
            'id' => $owner->id,
            'name' => $owner->name,
            'verified' => $owner->hasVerifiedEmail(),
            'has_messenger' => filled($owner->messenger_username),
        ];
    }
}
