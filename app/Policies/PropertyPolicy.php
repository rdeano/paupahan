<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

/**
 * Tenant isolation: an owner may only manage their own properties.
 */
class PropertyPolicy
{
    public function view(User $user, Property $property): bool
    {
        return $user->id === $property->owner_id;
    }

    public function update(User $user, Property $property): bool
    {
        return $user->id === $property->owner_id;
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->id === $property->owner_id;
    }
}
