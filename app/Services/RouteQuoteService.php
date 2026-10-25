<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RouteQuoteService
{
    public function search(string $query, int $limit = 6, string $locale = 'en'): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $locale = $locale === 'nl' ? 'nl' : 'en';
        $cacheKey = 'booking-address-search:v2:'.$locale.':'.$limit.':'.hash('sha256', mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $limit, $locale) {
            $lock = Cache::lock('booking-address-search-request', 10);
            $lock->block(3);

            try {
                $response = $this->request()
                    ->get((string) config('services.photon.base_url'), [
                        'q' => $query,
                        'limit' => $limit,
                        'lang' => $locale === 'nl' ? 'default' : 'en',
                        'bbox' => '3.2,50.7,7.3,53.7',
                    ]);
            } finally {
                $lock->release();
            }

            if (! $response->successful()) {
                throw new RuntimeException('The free address search is temporarily unavailable.');
            }

            return collect($response->json('features') ?? [])
                ->filter(fn (array $feature) => ! empty($feature['geometry']['coordinates']))
                ->filter(function (array $feature) {
                    $countryCode = $feature['properties']['countrycode'] ?? 'NL';

                    return mb_strtoupper((string) $countryCode) === 'NL';
                })
                ->map(function (array $feature, int $index) use ($query) {
                    $coordinates = $feature['geometry']['coordinates'];
                    $properties = $feature['properties'] ?? [];
                    $label = $this->photonLabel($properties, $query);
                    $detail = collect([
                        $properties['name'] ?? null,
                        trim(implode(' ', array_filter([
                            $properties['street'] ?? null,
                            $properties['housenumber'] ?? null,
                        ]))),
                        $properties['postcode'] ?? null,
                        $properties['city'] ?? $properties['town'] ?? $properties['village'] ?? null,
                        $properties['country'] ?? null,
                    ])->filter()->unique()->implode(', ');
                    $coordinate = [
                        'label' => $label,
                        'latitude' => (float) $coordinates[1],
                        'longitude' => (float) $coordinates[0],
                    ];
                    Cache::put($this->geocodeCacheKey($label), $coordinate, now()->addDays(30));

                    return [
                        'id' => ($properties['osm_type'] ?? 'place').'-'.($properties['osm_id'] ?? $index),
                        'label' => $label,
                        'detail' => $detail ?: $label,
                        'latitude' => (float) $coordinates[1],
                        'longitude' => (float) $coordinates[0],
                    ];
                })
                ->unique('label')
                ->values()
                ->all();
        });
    }

    public function quote(string $pickup, ?string $dropoff = null): array
    {
        $pickupPlace = $this->geocode($pickup);

        if (blank($dropoff)) {
            return [
                'pickup' => $pickupPlace,
                'dropoff' => null,
                'distance_km' => 0,
                'duration_minutes' => 0,
                'geometry' => [],
            ];
        }

        $dropoffPlace = $this->geocode($dropoff);
        $route = $this->request()
            ->get(sprintf(
                '%s/route/v1/driving/%F,%F;%F,%F',
                rtrim((string) config('services.osrm.base_url'), '/'),
                $pickupPlace['longitude'],
                $pickupPlace['latitude'],
                $dropoffPlace['longitude'],
                $dropoffPlace['latitude']
            ), [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
            ]);

        if (! $route->successful() || ($route->json('code') !== 'Ok') || empty($route->json('routes.0'))) {
            throw new RuntimeException('We could not calculate a driving route between these addresses.');
        }

        $details = $route->json('routes.0');
        $coordinates = $details['geometry']['coordinates'] ?? [];

        return [
            'pickup' => $pickupPlace,
            'dropoff' => $dropoffPlace,
            'distance_km' => round(((float) ($details['distance'] ?? 0)) / 1000, 1),
            'duration_minutes' => max(1, (int) ceil(((float) ($details['duration'] ?? 0)) / 60)),
            'geometry' => array_map(
                fn (array $coordinate) => [(float) $coordinate[1], (float) $coordinate[0]],
                $coordinates
            ),
        ];
    }

    private function geocode(string $query): array
    {
        $query = trim($query);
        $cacheKey = $this->geocodeCacheKey($query);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            $response = $this->request()
                ->get((string) config('services.nominatim.base_url'), [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'nl',
                    'q' => $query,
                ]);

            if (! $response->successful() || empty($response->json('0'))) {
                throw new RuntimeException("We could not find '{$query}'. Add a street, city, hotel or airport name.");
            }

            $place = $response->json('0');

            return [
                'label' => $this->placeLabel($place, $query),
                'latitude' => (float) $place['lat'],
                'longitude' => (float) $place['lon'],
            ];
        });
    }

    private function geocodeCacheKey(string $query): string
    {
        return 'booking-geocode:v3:'.hash('sha256', mb_strtolower(trim($query)));
    }

    private function placeLabel(array $place, string $fallback): string
    {
        $address = $place['address'] ?? [];
        $name = $place['name'] ?? strtok($place['display_name'] ?? $fallback, ',');
        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;
        $parts = array_values(array_filter([
            trim((string) $name),
            $city && mb_strtolower($city) !== mb_strtolower((string) $name) ? trim((string) $city) : null,
        ]));

        return $parts ? implode(', ', $parts) : trim($fallback);
    }

    private function photonLabel(array $properties, string $fallback): string
    {
        $name = $properties['name'] ?? null;
        $street = trim(implode(' ', array_filter([
            $properties['street'] ?? null,
            $properties['housenumber'] ?? null,
        ])));
        $city = $properties['city'] ?? $properties['town'] ?? $properties['village'] ?? null;
        $parts = collect([$name ?: $street, $city])
            ->filter()
            ->unique(fn (string $part) => mb_strtolower($part))
            ->values()
            ->all();

        return $parts ? implode(', ', $parts) : trim($fallback);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent((string) config('services.nominatim.user_agent'))
            ->timeout(10)
            ->retry(2, 250, throw: false);
    }
}
