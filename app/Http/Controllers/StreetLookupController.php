<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Street;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreetLookupController extends Controller
{
    /**
     * Streets in a barangay, for the owner's address autocomplete.
     */
    public function __invoke(Request $request, Barangay $barangay): JsonResponse
    {
        $streets = $barangay->streets()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get(['id', 'name', 'status'])
            ->map(fn (Street $street) => [
                'id' => $street->id,
                'name' => $street->name,
                'pending' => $street->status === Street::PENDING,
            ]);

        return response()->json($streets);
    }
}
