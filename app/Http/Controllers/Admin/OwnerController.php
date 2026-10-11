<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use App\Support\Messenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function create(): Response
    {
        return Inertia::render('Admin/OwnerForm', ['owner' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = User::onboardOwner($this->validatedProfile($request));

        return redirect()->route('admin.owners.edit', $owner)
            ->with('success', 'Owner added. Now add their first listing.');
    }

    public function edit(User $owner): Response
    {
        abort_if($owner->isAdmin(), 404);

        return Inertia::render('Admin/OwnerForm', [
            'owner' => [
                ...$owner->only(['id', 'name', 'email', 'phone', 'show_phone', 'messenger_username']),
                'messenger_url' => $owner->messengerUrl(),
                'verified' => $owner->hasVerifiedEmail(),
                'added_by_admin' => $owner->added_by_admin,
                'can_log_in' => filled($owner->email),
                'properties' => $owner->properties()->latest()->get()->map(fn (Property $property) => [
                    'slug' => $property->slug,
                    'title' => $property->title,
                    'status_label' => $property->status->label(),
                ]),
            ],
        ]);
    }

    public function update(Request $request, User $owner): RedirectResponse
    {
        abort_if($owner->isAdmin(), 404);

        $data = $this->validatedProfile($request, $owner);

        // An owner's login email is theirs to manage; an admin can only add one where none exists.
        if (filled($owner->email)) {
            unset($data['email']);
        }

        $owner->update($data);

        return back()->with('success', 'Owner saved.');
    }

    public function updateStatus(Request $request, User $owner): RedirectResponse
    {
        abort_if($owner->isAdmin(), 403);

        $owner->status = $request->boolean('suspend') ? UserStatus::Suspended : UserStatus::Active;
        $owner->save();

        return back()->with('success', $owner->isActive() ? 'Owner reactivated.' : 'Owner suspended. Their listings are hidden.');
    }

    private function validatedProfile(Request $request, ?User $owner = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($owner)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'show_phone' => ['boolean'],
            'messenger_username' => ['nullable', 'string', 'max:255'],
        ]);

        $username = Messenger::normalize($data['messenger_username'] ?? null);

        if (filled($data['messenger_username'] ?? null) && $username === null) {
            throw ValidationException::withMessages([
                'messenger_username' => 'Enter their Facebook username or an m.me link, e.g. m.me/juan.delacruz.',
            ]);
        }

        return [
            ...$data,
            'email' => filled($data['email'] ?? null) ? strtolower($data['email']) : null,
            'messenger_username' => $username,
        ];
    }
}
