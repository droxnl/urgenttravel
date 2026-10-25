<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingExtra;
use App\Models\BookingVehicle;
use App\Services\BookingPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingSettingsController extends Controller
{
    public function index(BookingPricingService $pricing): JsonResponse
    {
        return response()->json($pricing->adminCatalog());
    }

    public function update(Request $request, BookingPricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'vehicles' => ['required', 'array', 'min:1'],
            'vehicles.*.id' => ['nullable', 'integer', 'exists:booking_vehicles,id'],
            'vehicles.*.key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'vehicles.*.name_en' => ['required', 'string', 'max:120'],
            'vehicles.*.name_nl' => ['required', 'string', 'max:120'],
            'vehicles.*.badge_en' => ['nullable', 'string', 'max:80'],
            'vehicles.*.badge_nl' => ['nullable', 'string', 'max:80'],
            'vehicles.*.description_en' => ['nullable', 'string', 'max:1000'],
            'vehicles.*.description_nl' => ['nullable', 'string', 'max:1000'],
            'vehicles.*.category' => ['required', Rule::in(['comfort', 'business', 'van', 'luxury'])],
            'vehicles.*.rate_per_km' => ['required', 'numeric', 'min:0', 'max:9999'],
            'vehicles.*.hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'vehicles.*.max_passengers' => ['required', 'integer', 'min:1', 'max:16'],
            'vehicles.*.max_luggage' => ['required', 'integer', 'min:1', 'max:24'],
            'vehicles.*.image' => ['nullable', 'string', 'max:255'],
            'vehicles.*.is_active' => ['required', 'boolean'],
            'vehicles.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'extras' => ['required', 'array'],
            'extras.*.id' => ['nullable', 'integer', 'exists:booking_extras,id'],
            'extras.*.key' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'extras.*.name_en' => ['required', 'string', 'max:120'],
            'extras.*.name_nl' => ['required', 'string', 'max:120'],
            'extras.*.description_en' => ['nullable', 'string', 'max:1000'],
            'extras.*.description_nl' => ['nullable', 'string', 'max:1000'],
            'extras.*.price' => ['required', 'numeric', 'min:0', 'max:9999'],
            'extras.*.pricing_type' => ['required', Rule::in(['per_item', 'per_booking'])],
            'extras.*.max_quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'extras.*.is_active' => ['required', 'boolean'],
            'extras.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        foreach ($validated['vehicles'] as $data) {
            $vehicle = isset($data['id']) ? BookingVehicle::query()->findOrFail($data['id']) : new BookingVehicle;
            $vehicle->fill($data);
            $vehicle->save();
        }

        foreach ($validated['extras'] as $data) {
            $extra = isset($data['id']) ? BookingExtra::query()->findOrFail($data['id']) : new BookingExtra;
            $extra->fill($data);
            $extra->save();
        }

        return response()->json([
            'message' => 'Booking vehicles, fares and extras saved.',
            ...$pricing->adminCatalog(),
        ]);
    }
}
