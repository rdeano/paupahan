<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Messenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Owner/Profile', [
            'profile' => [
                ...$user->only(['id', 'name', 'email', 'phone', 'show_phone', 'messenger_username']),
                'messenger_url' => $user->messengerUrl(),
            ],
            'verification' => [
                'verified' => $user->hasVerifiedEmail(),
                'limit' => User::UNVERIFIED_PUBLIC_LISTING_LIMIT,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'show_phone' => ['boolean'],
            'messenger_username' => ['nullable', 'string', 'max:255'],
        ]);

        $username = Messenger::normalize($data['messenger_username'] ?? null);

        if (filled($data['messenger_username'] ?? null) && $username === null) {
            return back()->withErrors([
                'messenger_username' => 'Enter your Facebook username or an m.me link, e.g. m.me/juan.delacruz.',
            ]);
        }

        $request->user()->update([...$data, 'messenger_username' => $username]);

        return back()->with('success', 'Profile saved.');
    }
}
