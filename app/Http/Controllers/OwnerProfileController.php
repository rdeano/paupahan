<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class OwnerProfileController extends Controller
{
    public function show(User $owner): Response
    {
        abort_unless($owner->isActive() && ! $owner->isAdmin(), 404);

        $listings = $owner->properties()
            ->publiclyVisible()
            ->with(['coverPhoto', 'street', 'barangay.district'])
            ->latest('published_at')
            ->paginate(12)
            ->through(fn (Property $property) => $property->toCard());

        return Inertia::render('Owners/Show', [
            'owner' => $owner->toPublicArray(),
            'listings' => $listings,
        ]);
    }
}
