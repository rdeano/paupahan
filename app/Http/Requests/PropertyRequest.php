<?php

namespace App\Http\Requests;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Street;
use App\Rules\WithinPublicListingLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');

        return $property ? $this->user()->can('update', $property) : true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'description' => ['required', 'string', 'max:5000'],
            'price_monthly' => ['required', 'integer', 'min:1', 'max:10000000'],
            'deposit_months' => ['required', 'integer', 'min:0', 'max:12'],
            'advance_months' => ['required', 'integer', 'min:0', 'max:12'],
            'min_stay_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'floor_area_sqm' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_occupants' => ['nullable', 'integer', 'min:1', 'max:100'],
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')],
            'street_id' => [
                'nullable', 'integer',
                Rule::exists('streets', 'id')->where(fn ($q) => $q
                    ->where('barangay_id', $this->input('barangay_id'))
                    ->where(fn ($q) => $q
                        ->where('status', Street::APPROVED)
                        ->orWhere('created_by', $this->user()->id))),
            ],
            'new_street' => ['nullable', 'string', 'max:120'],
            'house_number' => ['nullable', 'string', 'max:100'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'house_rules' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(PropertyStatus::class), new WithinPublicListingLimit($this->user(), $this->route('property'))],
            'available_from' => ['nullable', 'date'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', Rule::exists('amenities', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'street_id.exists' => 'Pick a street from the list for the selected barangay.',
        ];
    }

    /**
     * Property attributes, with a typed-in street resolved to a street record.
     * A street the owner adds is saved as pending until an admin approves it.
     */
    public function propertyData(): array
    {
        $data = collect($this->validated())->except(['new_street', 'amenities'])->all();
        $newStreet = trim((string) $this->input('new_street'));

        if (empty($data['street_id']) && $newStreet !== '') {
            $existing = Street::where('barangay_id', $data['barangay_id'])->where('name', $newStreet)->first();

            $data['street_id'] = ($existing ?? Street::create([
                'barangay_id' => $data['barangay_id'],
                'name' => $newStreet,
                'status' => Street::PENDING,
                'created_by' => $this->user()->id,
            ]))->id;
        }

        return $data;
    }
}
