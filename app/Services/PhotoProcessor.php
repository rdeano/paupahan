<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

class PhotoProcessor
{
    private const FULL_SIZE = 1600;

    private const THUMB_SIZE = 640;

    /**
     * Resize, re-encode (which strips EXIF data such as GPS location) and store an uploaded photo.
     */
    public function store(Property $property, UploadedFile $file): PropertyPhoto
    {
        $directory = "properties/{$property->id}";
        $name = Str::random(32);
        $disk = Storage::disk('public');

        $encoder = new JpegEncoder(quality: 80, strip: true);

        $full = Image::decodePath($file->getRealPath())->scaleDown(self::FULL_SIZE, self::FULL_SIZE)->encode($encoder);
        $disk->put("{$directory}/{$name}.jpg", $full->toString());

        $thumb = Image::decodePath($file->getRealPath())->scaleDown(self::THUMB_SIZE, self::THUMB_SIZE)->encode($encoder);
        $disk->put("{$directory}/{$name}_thumb.jpg", $thumb->toString());

        $isFirst = ! $property->photos()->exists();

        return $property->photos()->create([
            'path' => "{$directory}/{$name}.jpg",
            'thumb_path' => "{$directory}/{$name}_thumb.jpg",
            'sort_order' => (int) $property->photos()->max('sort_order') + 1,
            'is_cover' => $isFirst,
        ]);
    }
}
