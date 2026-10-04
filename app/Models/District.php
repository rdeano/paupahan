<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class District extends Model
{
    /** @return HasMany<Barangay, $this> */
    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}
