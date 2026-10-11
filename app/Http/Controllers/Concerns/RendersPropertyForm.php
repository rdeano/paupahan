<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Controllers\Owner\PropertyPhotoController;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Models\User;
use App\Support\AddressOptions;

/**
 * Props for the property form, shared by the owner workspace and admins posting on an owner's behalf.
 */
trait RendersPropertyForm
{
    private function propertyFormProps(?Property $property = null): array
    {
        if ($property) {
            $property->load(['photos', 'amenities', 'street']);
        }

        return [
            'property' => $property ? [
                ...$property->only([
                    'id', 'slug', 'title', 'description', 'price_monthly', 'deposit_months', 'advance_months',
                    'min_stay_months', 'bedrooms', 'bathrooms', 'floor_area_sqm', 'max_occupants',
                    'barangay_id', 'street_id', 'house_number', 'landmark', 'latitude', 'longitude', 'house_rules',
                    'contact_name', 'contact_messenger', 'contact_phone',
                ]),
                'type' => $property->type->value,
                'status' => $property->status->value,
                'available_from' => $property->available_from?->toDateString(),
                'street_name' => $property->street?->name,
                'amenities' => $property->amenities->pluck('id'),
                'photos' => $property->photos->map(fn (PropertyPhoto $photo) => $photo->toFrontend()),
                'max_photos' => PropertyPhotoController::MAX_PHOTOS,
            ] : null,
            'unverifiedLimit' => User::UNVERIFIED_PUBLIC_LISTING_LIMIT,
            'options' => [
                'districts' => AddressOptions::districts(),
                'types' => PropertyType::options(),
                'statuses' => PropertyStatus::options(),
                'amenities' => Amenity::orderBy('name')->get(['id', 'name']),
            ],
        ];
    }
}
