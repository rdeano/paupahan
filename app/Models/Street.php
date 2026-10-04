<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['barangay_id', 'name', 'status', 'created_by'])]
class Street extends Model
{
    public const APPROVED = 'approved';

    public const PENDING = 'pending';

    /** @return BelongsTo<Barangay, $this> */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Approved streets, plus pending ones the given user suggested. */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', self::APPROVED)
            ->when($user, fn (Builder $q) => $q->orWhere('created_by', $user->id)));
    }
}
