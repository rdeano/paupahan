<?php

namespace App\Support;

use App\Models\Barangay;
use App\Models\District;
use Illuminate\Support\Facades\Cache;

class AddressOptions
{
    public const CACHE_KEY = 'address-options';

    /**
     * Districts with their barangays, for address pickers and search filters.
     * Barangays without a district are grouped under "Other".
     *
     * @return list<array{id: int|null, name: string, barangays: list<array{id: int, name: string}>}>
     */
    public static function districts(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            $districts = District::orderBy('name')->get(['id', 'name']);
            $barangays = Barangay::orderBy('name')->get(['id', 'name', 'district_id'])->groupBy('district_id');

            $groups = $districts->map(fn (District $district) => [
                'id' => $district->id,
                'name' => $district->name,
                'barangays' => self::barangayList($barangays->get($district->id, collect())),
            ])->all();

            if ($barangays->has('')) {
                $groups[] = ['id' => null, 'name' => 'Other', 'barangays' => self::barangayList($barangays->get(''))];
            }

            return $groups;
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function barangayList($barangays): array
    {
        return $barangays->map(fn (Barangay $b) => ['id' => $b->id, 'name' => $b->name])->values()->all();
    }
}
