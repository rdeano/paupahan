<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyPhoto;
use App\Services\PhotoProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyPhotoController extends Controller
{
    public const MAX_PHOTOS = 5;

    public function store(Request $request, Property $property, PhotoProcessor $processor): RedirectResponse
    {
        $this->authorize('update', $property);

        $remaining = self::MAX_PHOTOS - $property->photos()->count();

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.max($remaining, 0)],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'photos.max' => $remaining > 0
                ? 'A property can have up to '.self::MAX_PHOTOS." photos. You can add {$remaining} more."
                : 'A property can have up to '.self::MAX_PHOTOS.' photos. Delete one to add another.',
        ]);

        foreach ($request->file('photos') as $file) {
            $processor->store($property, $file);
        }

        return back()->with('success', 'Photos uploaded.');
    }

    public function cover(Property $property, PropertyPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($photo->property_id === $property->id, 404);

        $property->photos()->update(['is_cover' => false]);
        $photo->update(['is_cover' => true]);

        return back()->with('success', 'Cover photo updated.');
    }

    public function reorder(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('property_photos', 'id')->where('property_id', $property->id)],
        ])['ids'];

        foreach ($ids as $index => $id) {
            $property->photos()->whereKey($id)->update(['sort_order' => $index + 1]);
        }

        return back();
    }

    public function destroy(Property $property, PropertyPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($photo->property_id === $property->id, 404);

        $wasCover = $photo->is_cover;
        $photo->delete();

        if ($wasCover) {
            $property->photos()->first()?->update(['is_cover' => true]);
        }

        return back()->with('success', 'Photo removed.');
    }
}
