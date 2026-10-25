<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingPricingService;
use App\Services\RouteQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function config(Request $request, BookingPricingService $pricing): JsonResponse
    {
        return response()->json($pricing->publicCatalog(
            $request->query('locale') === 'nl' ? 'nl' : 'en'
        ));
    }

    public function addresses(Request $request, RouteQuoteService $routes): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:190'],
            'locale' => ['nullable', 'in:en,nl'],
        ]);

        try {
            return response()->json([
                'data' => $routes->search($validated['q'], 6, $validated['locale'] ?? 'en'),
                'attribution' => 'Search by Photon · © OpenStreetMap contributors',
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The free address search is temporarily unavailable. Please try again.',
            ], 503);
        }
    }

    public function quote(
        Request $request,
        RouteQuoteService $routes,
        BookingPricingService $pricing
    ): JsonResponse {
        $validated = $request->validate([
            'booking_mode' => ['required', 'in:transfer,hourly'],
            'pickup' => ['required', 'string', 'max:190'],
            'dropoff' => ['nullable', 'string', 'max:190', 'required_if:booking_mode,transfer'],
            'trip_type' => ['required', 'in:one_way,return'],
            'passengers' => ['required', 'integer', 'min:1', 'max:16'],
            'luggage' => ['required', 'integer', 'min:0', 'max:24'],
            'hourly_duration_hours' => ['nullable', 'required_if:booking_mode,hourly', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $route = $routes->quote($validated['pickup'], $validated['dropoff'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The route service is temporarily unavailable. Please try again or contact our team.',
            ], 503);
        }

        $catalog = $pricing->publicCatalog(
            $request->input('locale') === 'nl' ? 'nl' : 'en'
        );
        $options = $pricing->vehicleOptions(
            $validated['booking_mode'],
            $validated['trip_type'],
            (float) $route['distance_km'],
            (int) ($validated['hourly_duration_hours'] ?? 0),
            (int) $validated['passengers'],
            (int) $validated['luggage']
        )->keyBy('key');

        $catalog['vehicles'] = $catalog['vehicles']->map(
            fn (array $vehicle) => [...$vehicle, ...($options->get($vehicle['key']) ?? [])]
        )->values();

        return response()->json([
            ...$catalog,
            'route' => $route,
            'quote' => [
                'booking_mode' => $validated['booking_mode'],
                'trip_type' => $validated['trip_type'],
                'price_distance_km' => (float) $route['distance_km'] * ($validated['trip_type'] === 'return' ? 2 : 1),
                'hourly_duration_hours' => (int) ($validated['hourly_duration_hours'] ?? 0),
            ],
        ]);
    }
}
