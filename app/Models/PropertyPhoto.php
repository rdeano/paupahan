<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['path', 'thumb_path', 'sort_order', 'is_cover'])]
class PropertyPhoto extends Model
{
    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'url' => Storage::disk('public')->url($this->path),
            'thumb_url' => Storage::disk('public')->url($this->thumb_path),
            'is_cover' => $this->is_cover,
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (PropertyPhoto $photo) {
            Storage::disk('public')->delete([$photo->path, $photo->thumb_path]);
        });
    }
}
