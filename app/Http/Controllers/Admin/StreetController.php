<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Street;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StreetController extends Controller
{
    public function index(): Response
    {
        $streets = Street::where('status', Street::PENDING)
            ->with(['barangay.district', 'creator'])
            ->latest()
            ->get()
            ->map(fn (Street $street) => [
                'id' => $street->id,
                'name' => $street->name,
                'barangay' => $street->barangay->name,
                'district' => $street->barangay->district?->name,
                'suggested_by' => $street->creator?->name,
                'created_at' => $street->created_at->toDateString(),
            ]);

        return Inertia::render('Admin/Streets', ['streets' => $streets]);
    }

    public function approve(Street $street): RedirectResponse
    {
        $street->update(['status' => Street::APPROVED]);

        return back()->with('success', "\"{$street->name}\" approved.");
    }

    public function destroy(Street $street): RedirectResponse
    {
        $street->delete();

        return back()->with('success', 'Street rejected.');
    }
}
