<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingInquiry;
use App\Services\BookingNotifier;
use App\Services\BookingPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class BookingInquiryController extends Controller
{
    public function store(
        Request $request,
        BookingNotifier $notifier,
        BookingPricingService $pricing
    ): JsonResponse {
        if ($request->input('locale') === 'nl') {
            app()->setLocale('nl');
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:booking,contact'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:50'],
            'pickup' => ['nullable', 'string', 'max:190'],
            'dropoff' => ['nullable', 'string', 'max:190'],
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:16'],
            'luggage' => ['nullable', 'integer', 'min:0', 'max:24'],
            'flight_number' => ['nullable', 'string', 'max:30'],
            'service_type' => ['nullable', 'string', 'max:60'],
            'vehicle_id' => ['nullable', 'integer', 'exists:booking_vehicles,id'],
            'booking_mode' => ['nullable', 'required_with:vehicle_id', Rule::in(['transfer', 'hourly'])],
            'trip_type' => ['nullable', 'required_with:vehicle_id', Rule::in(['one_way', 'return'])],
            'route_distance_km' => ['nullable', 'required_with:vehicle_id', 'numeric', 'min:0', 'max:3000'],
            'route_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'hourly_duration_hours' => ['nullable', 'required_if:booking_mode,hourly', 'integer', 'min:1', 'max:12'],
            'extras' => ['nullable', 'array'],
            'extras.*' => ['integer', 'min:0', 'max:20'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card'])],
            'invoice_address' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:4000'],
            'source_page' => ['nullable', 'string', 'max:190'],
            'locale' => ['nullable', 'string', 'max:10'],
            'consent' => ['accepted'],
        ]);

        $inquiryData = [
            ...Arr::except($validated, [
                'vehicle_id',
                'booking_mode',
                'trip_type',
                'route_distance_km',
                'route_duration_minutes',
                'hourly_duration_hours',
                'extras',
                'payment_method',
                'invoice_address',
            ]),
            'type' => $validated['type'] ?? 'booking',
            'status' => 'new',
            'consent' => true,
        ];

        if (! empty($validated['vehicle_id'])) {
            $quote = $pricing->calculate($validated);
            $inquiryData = [
                ...$inquiryData,
                'booking_vehicle_id' => $quote['vehicle']->id,
                'vehicle_name' => ($validated['locale'] ?? 'en') === 'nl'
                    ? $quote['vehicle']->name_nl
                    : $quote['vehicle']->name_en,
                'booking_mode' => $validated['booking_mode'],
                'trip_type' => $validated['trip_type'],
                'route_distance_km' => $validated['route_distance_km'],
                'route_duration_minutes' => $validated['route_duration_minutes'] ?? null,
                'hourly_duration_hours' => $validated['hourly_duration_hours'] ?? null,
                'vehicle_rate_snapshot' => $validated['booking_mode'] === 'hourly'
                    ? $quote['vehicle']->hourly_rate
                    : $quote['vehicle']->rate_per_km,
                'selected_extras' => $quote['selected_extras'],
                'estimated_total' => $quote['estimated_total'],
                'payment_method' => $validated['payment_method'] ?? null,
                'invoice_address' => $validated['invoice_address'] ?? null,
            ];
        }

        $inquiry = BookingInquiry::query()->create($inquiryData);

        $notifier->notify($inquiry);

        return response()->json([
            'message' => 'Your request has been received. We will contact you shortly.',
            'reference' => $inquiry->reference,
            'status' => $inquiry->status,
            'estimated_total' => $inquiry->estimated_total,
        ], 201);
    }
}
