<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Street;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'owners' => User::where('role', UserRole::Owner->value)->count(),
                'listings' => Property::count(),
                'published' => Property::publiclyVisible()->count(),
                'pending_streets' => Street::where('status', Street::PENDING)->count(),
            ],
        ]);
    }
}
