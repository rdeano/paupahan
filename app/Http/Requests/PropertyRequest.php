<?php

namespace App\Http\Requests;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Street;
use App\Models\User;
use App\Rules\WithinPublicListingLimit;
use App\Support\Messenger;
use Closure;
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
                    ->when(! $this->user()->isAdmin(), fn ($q) => $q->where(fn ($q) => $q
                        ->where('status', Street::APPROVED)
                        ->orWhere('created_by', $this->user()->id)))),
            ],
            'new_street' => ['nullable', 'string', 'max:120'],
            'house_number' => ['nullable', 'string', 'max:100'],
            'landmark' => ['nullable', 'string', 'max:150'],
            // Map pin: private; renters only see an approximate position.
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'house_rules' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(PropertyStatus::class), new WithinPublicListingLimit($this->owner(), $this->route('property'))],
            'available_from' => ['nullable', 'date'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer', Rule::exists('amenities', 'id')],
            ...($this->isAdminListing() ? $this->contactRules() : []),
        ];
    }

    /** Listings an admin posts belong to no owner account, so they carry the contact renters should reach. */
    private function contactRules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_messenger' => ['nullable', 'required_without:contact_phone', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail) {
                if (filled($value) && Messenger::normalize($value) === null) {
                    $fail('Enter their Facebook username or an m.me link, e.g. m.me/juan.delacruz.');
                }
            }],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'street_id.exists' => 'Pick a street from the list for the selected barangay.',
            'contact_messenger.required_without' => 'Add a Messenger link or a mobile number so renters can reach them.',
        ];
    }

    /** Whether this is an admin's own listing: being posted from the admin area, or already owned by an admin. */
    public function isAdminListing(): bool
    {
        return $this->routeIs('admin.properties.store') || (bool) $this->route('property')?->owner->isAdmin();
    }

    /** The account the listing belongs to: the property's owner when editing, or else the signed-in user. */
    public function owner(): User
    {
        return $this->route('property')?->owner ?? $this->user();
    }

    /**
     * Property attributes, with a typed-in street resolved to a street record.
     * A street the owner adds is saved as pending until an admin approves it;
     * a street an admin adds is approved straight away.
     */
    public function propertyData(): array
    {
        $data = collect($this->validated())->except(['new_street', 'amenities'])->all();

        if ($this->isAdminListing()) {
            $data['contact_messenger'] = Messenger::normalize($data['contact_messenger'] ?? null);
        }

        $newStreet = trim((string) $this->input('new_street'));

        if (empty($data['street_id']) && $newStreet !== '') {
            $existing = Street::where('barangay_id', $data['barangay_id'])->where('name', $newStreet)->first();

            $data['street_id'] = ($existing ?? Street::create([
                'barangay_id' => $data['barangay_id'],
                'name' => $newStreet,
                'status' => $this->user()->isAdmin() ? Street::APPROVED : Street::PENDING,
                'created_by' => $this->user()->id,
            ]))->id;
        }

        return $data;
    }
}
