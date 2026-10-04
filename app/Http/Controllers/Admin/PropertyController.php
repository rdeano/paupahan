<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $properties = Property::with(['owner', 'street', 'barangay.district'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Property $property) => [
                'id' => $property->id,
                'slug' => $property->slug,
                'title' => $property->title,
                'owner' => $property->owner->name,
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
}
