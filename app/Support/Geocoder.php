<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Finds a typed place (street, barangay, landmark) in Davao City using OpenStreetMap's Nominatim.
 * Results are cached, and lookups are limited to one per second as Nominatim's usage policy requires.
 */
class Geocoder
{
    private const CACHE_DAYS = 30;

    /** Remember "not found" (or a failed lookup) for a short while so the same search doesn't keep retrying. */
    private const MISS_CACHE_MINUTES = 60;

    /**
     * @return array{name: string, lat: float, lng: float, bounds: array{south: float, north: float, west: float, east: float}}|null
     */
    public static function locate(string $query): ?array
    {
        $query = Str::squish($query);

        if (mb_strlen($query) < 3) {
            return null;
        }

        $key = 'geocode:'.md5(mb_strtolower($query));
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached['place'];
        }

        // Over the limit: skip the lookup this time rather than make the renter wait.
        if (RateLimiter::tooManyAttempts('nominatim', 1)) {
            return null;
        }
        RateLimiter::hit('nominatim', 1);

        $place = self::lookup($query);
        Cache::put($key, ['place' => $place], $place ? now()->addDays(self::CACHE_DAYS) : now()->addMinutes(self::MISS_CACHE_MINUTES));

        return $place;
    }

    private static function lookup(string $query): ?array
    {
        $contact = config('services.nominatim.contact');
        $userAgent = config('app.name').'/1.0 ('.config('app.url').($contact ? '; '.$contact : '').')';

        try {
            $results = Http::withUserAgent($userAgent)
                ->timeout(4)
                ->get(rtrim(config('services.nominatim.url'), '/').'/search', [
                    'q' => "{$query}, Davao City, Philippines",
                    'countrycodes' => 'ph',
                    'format' => 'jsonv2',
                    'limit' => 1,
                ])
                ->throw()
                ->json();
        } catch (Throwable $e) {
            Log::warning('Nominatim lookup failed', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }

        $result = $results[0] ?? null;

        if (! isset($result['lat'], $result['lon'], $result['boundingbox'])) {
            return null;
        }

        // Nominatim's bounding box is [south, north, west, east].
        [$south, $north, $west, $east] = array_map('floatval', $result['boundingbox']);

        return [
            'name' => (string) ($result['display_name'] ?? $query),
            'lat' => (float) $result['lat'],
            'lng' => (float) $result['lon'],
            'bounds' => compact('south', 'north', 'west', 'east'),
        ];
    }
}
