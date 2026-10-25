<?php

namespace App\Services;

use App\Models\BookingExtra;
use App\Models\BookingVehicle;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingPricingService
{
    public function publicCatalog(string $locale = 'en'): array
    {
        $locale = $locale === 'nl' ? 'nl' : 'en';

        return [
            'vehicles' => BookingVehicle::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (BookingVehicle $vehicle) => $this->vehicleData($vehicle, $locale))
                ->values(),
            'extras' => BookingExtra::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (BookingExtra $extra) => $this->extraData($extra, $locale))
                ->values(),
        ];
    }

    public function adminCatalog(): array
    {
        return [
            'vehicles' => BookingVehicle::query()->orderBy('sort_order')->orderBy('id')->get(),
            'extras' => BookingExtra::query()->orderBy('sort_order')->orderBy('id')->get(),
        ];
    }

    public function vehicleOptions(
        string $mode,
        string $tripType,
        float $distanceKm,
        int $hourlyDuration,
        int $passengers,
        int $luggage
    ): Collection {
        return BookingVehicle::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (BookingVehicle $vehicle) use ($mode, $tripType, $distanceKm, $hourlyDuration, $passengers, $luggage) {
                $available = $passengers <= $vehicle->max_passengers && $luggage <= $vehicle->max_luggage;
                $total = $available
                    ? $this->calculateVehicleTotal($vehicle, $mode, $tripType, $distanceKm, $hourlyDuration)
                    : null;

                return [
                    'id' => $vehicle->id,
                    'key' => $vehicle->key,
                    'available' => $available,
                    'price_on_request' => $available && $total === null,
                    'estimated_total' => $total,
                ];
            });
    }

    public function calculate(array $data): array
    {
        $vehicle = BookingVehicle::query()
            ->whereKey($data['vehicle_id'])
            ->where('is_active', true)
            ->first();

        if (! $vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Please select an available vehicle.',
            ]);
        }

        if (
            (int) $data['passengers'] > $vehicle->max_passengers
            || (int) $data['luggage'] > $vehicle->max_luggage
        ) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle does not have enough capacity for the selected passengers and luggage.',
            ]);
        }

        $mode = $data['booking_mode'] ?? 'transfer';
        $distance = (float) ($data['route_distance_km'] ?? 0);
        $hours = (int) ($data['hourly_duration_hours'] ?? 0);
        $vehicleTotal = $this->calculateVehicleTotal(
            $vehicle,
            $mode,
            $data['trip_type'] ?? 'one_way',
            $distance,
            $hours
        );

        $requestedExtras = collect($data['extras'] ?? [])
            ->mapWithKeys(fn ($quantity, $key) => [(string) $key => max(0, (int) $quantity)])
            ->filter();
        $selectedExtras = [];
        $extrasTotal = 0.0;

        if ($requestedExtras->isNotEmpty()) {
            $catalogExtras = BookingExtra::query()
                ->where('is_active', true)
                ->whereIn('key', $requestedExtras->keys())
                ->get()
                ->keyBy('key');

            foreach ($requestedExtras as $key => $quantity) {
                $extra = $catalogExtras->get($key);
                if (! $extra) {
                    throw ValidationException::withMessages([
                        'extras' => 'One of the selected extras is no longer available.',
                    ]);
                }

                $quantity = min($quantity, $extra->max_quantity);
                $chargedQuantity = $extra->pricing_type === 'per_booking' ? 1 : $quantity;
                $lineTotal = round((float) $extra->price * $chargedQuantity, 2);
                $extrasTotal += $lineTotal;
                $selectedExtras[] = [
                    'id' => $extra->id,
                    'key' => $extra->key,
                    'name_en' => $extra->name_en,
                    'name_nl' => $extra->name_nl,
                    'quantity' => $quantity,
                    'billing_quantity' => $chargedQuantity,
                    'pricing_type' => $extra->pricing_type,
                    'unit_price' => (float) $extra->price,
                    'line_total' => $lineTotal,
                ];
            }
        }

        return [
            'vehicle' => $vehicle,
            'vehicle_total' => round($vehicleTotal, 2),
            'extras_total' => round($extrasTotal, 2),
            'estimated_total' => $vehicleTotal === null ? null : round($vehicleTotal + $extrasTotal, 2),
            'selected_extras' => $selectedExtras,
        ];
    }

    public function vehicleData(BookingVehicle $vehicle, string $locale = 'en'): array
    {
        $suffix = $locale === 'nl' ? 'nl' : 'en';

        return [
            'id' => $vehicle->id,
            'key' => $vehicle->key,
            'name' => $vehicle->{"name_{$suffix}"} ?: $vehicle->name_en,
            'badge' => $vehicle->{"badge_{$suffix}"} ?: $vehicle->badge_en,
            'description' => $vehicle->{"description_{$suffix}"} ?: $vehicle->description_en,
            'category' => $vehicle->category,
            'rate_per_km' => (float) $vehicle->rate_per_km,
            'hourly_rate' => $vehicle->hourly_rate === null ? null : (float) $vehicle->hourly_rate,
            'max_passengers' => $vehicle->max_passengers,
            'max_luggage' => $vehicle->max_luggage,
            'image' => $vehicle->image,
            'image_url' => $vehicle->image
                ? (str_starts_with($vehicle->image, 'http') ? $vehicle->image : url($vehicle->image))
                : null,
        ];
    }

    public function extraData(BookingExtra $extra, string $locale = 'en'): array
    {
        $suffix = $locale === 'nl' ? 'nl' : 'en';

        return [
            'id' => $extra->id,
            'key' => $extra->key,
            'name' => $extra->{"name_{$suffix}"} ?: $extra->name_en,
            'description' => $extra->{"description_{$suffix}"} ?: $extra->description_en,
            'price' => (float) $extra->price,
            'pricing_type' => $extra->pricing_type,
            'max_quantity' => $extra->max_quantity,
        ];
    }

    private function calculateVehicleTotal(
        BookingVehicle $vehicle,
        string $mode,
        string $tripType,
        float $distanceKm,
        int $hourlyDuration
    ): ?float {
        if ($mode === 'hourly') {
            if ($vehicle->hourly_rate === null) {
                return null;
            }

            return round((float) $vehicle->hourly_rate * max(1, $hourlyDuration), 2);
        }

        $distanceMultiplier = $tripType === 'return' ? 2 : 1;

        return round(max(0, $distanceKm) * (float) $vehicle->rate_per_km * $distanceMultiplier, 2);
    }
}
