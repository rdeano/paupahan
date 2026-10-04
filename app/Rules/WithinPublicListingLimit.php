<?php

namespace App\Rules;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Unverified owners may only have a limited number of Available/Reserved listings.
 */
class WithinPublicListingLimit implements ValidationRule
{
    public function __construct(private User $owner, private ?Property $property = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, PropertyStatus::publicValues(), true)) {
            return;
        }

        if ($this->owner->hasReachedPublicListingLimit($this->property)) {
            $fail('Unverified accounts can have up to '.User::UNVERIFIED_PUBLIC_LISTING_LIMIT.' public listings. Verify your email to publish more, or save this one as a draft.');
        }
    }
}
