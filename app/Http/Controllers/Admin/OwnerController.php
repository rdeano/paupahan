<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OwnerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $owners = User::where('role', UserRole::Owner->value)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->withCount('properties')
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $owner) => [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'status' => $owner->status->value,
                'verified' => $owner->hasVerifiedEmail(),
                'properties_count' => $owner->properties_count,
                'joined' => $owner->created_at->toDateString(),
            ]);

        return Inertia::render('Admin/Owners', [
            'owners' => $owners,
            'filters' => ['q' => $search],
        ]);
    }

    public function updateStatus(Request $request, User $owner): RedirectResponse
    {
        abort_if($owner->isAdmin(), 403);

        $owner->status = $request->boolean('suspend') ? UserStatus::Suspended : UserStatus::Active;
        $owner->save();

        return back()->with('success', $owner->isActive() ? 'Owner reactivated.' : 'Owner suspended. Their listings are hidden.');
    }
}
