<?php

namespace Tests\Feature;

use App\Models\BookingInquiry;
use App\Models\BookingVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_the_public_catalog_contains_the_configured_vehicle_fares_and_extras(): void
    {
        $response = $this->getJson('/api/booking/config?locale=en')
            ->assertOk()
            ->assertJsonPath('vehicles.0.key', 'normal-car')
            ->assertJsonPath('vehicles.0.rate_per_km', 1.8)
            ->assertJsonPath('vehicles.1.rate_per_km', 2.15)
            ->assertJsonPath('vehicles.2.rate_per_km', 2.8)
            ->assertJsonPath('vehicles.3.rate_per_km', 5)
            ->assertJsonPath('extras.0.price', 15)
            ->assertJsonPath('extras.1.price', 5)
            ->assertJsonPath('extras.2.price', 25);

        $this->assertNull($response->json('vehicles.0.hourly_rate'));
    }

    public function test_a_route_quote_returns_distance_map_and_vehicle_estimates(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::sequence()
                ->push([['display_name' => 'Amsterdam, Netherlands', 'lat' => '52.3676', 'lon' => '4.9041']])
                ->push([['display_name' => 'Schiphol Airport, Netherlands', 'lat' => '52.3105', 'lon' => '4.7683']]),
            'router.project-osrm.org/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 20000,
                    'duration' => 1500,
                    'geometry' => [
                        'coordinates' => [[4.9041, 52.3676], [4.7683, 52.3105]],
                    ],
                ]],
            ]),
        ]);

        $this->postJson('/api/booking/quote', [
            'booking_mode' => 'transfer',
            'pickup' => 'Amsterdam Centraal',
            'dropoff' => 'Schiphol Airport',
            'trip_type' => 'return',
            'passengers' => 2,
            'luggage' => 2,
        ])->assertOk()
            ->assertJsonPath('route.distance_km', 20)
            ->assertJsonPath('route.duration_minutes', 25)
            ->assertJsonPath('route.geometry.0.0', 52.3676)
            ->assertJsonPath('vehicles.0.estimated_total', 72)
            ->assertJsonPath('vehicles.1.estimated_total', 86)
            ->assertJsonPath('vehicles.2.estimated_total', 112)
            ->assertJsonPath('vehicles.3.estimated_total', 200);
    }

    public function test_address_suggestions_are_free_cached_and_reused_by_the_route_quote(): void
    {
        Http::fake([
            'photon.komoot.io/api*' => Http::response([
                'features' => [
                    [
                        'geometry' => ['coordinates' => [4.9003, 52.3789]],
                        'properties' => [
                            'osm_type' => 'N',
                            'osm_id' => 123,
                            'name' => 'Amsterdam Centraal',
                            'city' => 'Amsterdam',
                            'countrycode' => 'NL',
                            'country' => 'Nederland',
                        ],
                    ],
                    [
                        'geometry' => ['coordinates' => [4.7683, 52.3105]],
                        'properties' => [
                            'osm_type' => 'W',
                            'osm_id' => 456,
                            'name' => 'Schiphol Airport',
                            'city' => 'Schiphol',
                            'countrycode' => 'NL',
                            'country' => 'Nederland',
                        ],
                    ],
                ],
            ]),
            'router.project-osrm.org/route/v1/driving/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 20000,
                    'duration' => 1500,
                    'geometry' => [
                        'coordinates' => [[4.9003, 52.3789], [4.7683, 52.3105]],
                    ],
                ]],
            ]),
        ]);

        $this->getJson('/api/booking/addresses?q=Amsterdam%20Centraal')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label', 'Amsterdam Centraal, Amsterdam')
            ->assertJsonPath('data.1.label', 'Schiphol Airport, Schiphol')
            ->assertJsonPath('attribution', 'Search by Photon · © OpenStreetMap contributors');

        $this->postJson('/api/booking/quote', [
            'booking_mode' => 'transfer',
            'pickup' => 'Amsterdam Centraal, Amsterdam',
            'dropoff' => 'Schiphol Airport, Schiphol',
            'trip_type' => 'one_way',
            'passengers' => 2,
            'luggage' => 2,
        ])->assertOk()
            ->assertJsonPath('route.distance_km', 20);

        // One Nominatim search and one OSRM route request: selecting a suggestion
        // must not trigger a second lookup for either address.
        Http::assertSentCount(2);
    }

    public function test_address_search_requires_three_characters(): void
    {
        $this->getJson('/api/booking/addresses?q=Am')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_hourly_quotes_do_not_require_a_dropoff_route(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['name' => 'Amsterdam', 'display_name' => 'Amsterdam, Nederland', 'lat' => '52.3676', 'lon' => '4.9041'],
            ]),
        ]);

        $this->postJson('/api/booking/quote', [
            'booking_mode' => 'hourly',
            'pickup' => 'Amsterdam',
            'dropoff' => null,
            'trip_type' => 'one_way',
            'passengers' => 2,
            'luggage' => 2,
            'hourly_duration_hours' => 3,
        ])->assertOk()
            ->assertJsonPath('route.distance_km', 0)
            ->assertJsonPath('route.dropoff', null)
            ->assertJsonPath('vehicles.0.price_on_request', true);
    }

    public function test_the_server_recalculates_prices_and_stores_the_booking_snapshot(): void
    {
        $vehicle = BookingVehicle::query()->where('key', 'normal-car')->firstOrFail();

        $response = $this->postJson('/api/inquiries', [
            'type' => 'booking',
            'name' => 'Taylor Traveller',
            'email' => 'taylor@example.org',
            'phone' => '+31612345678',
            'pickup' => 'Amsterdam Centraal',
            'dropoff' => 'Schiphol Airport',
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '08:30',
            'passengers' => 2,
            'luggage' => 2,
            'vehicle_id' => $vehicle->id,
            'booking_mode' => 'transfer',
            'trip_type' => 'return',
            'route_distance_km' => 30,
            'route_duration_minutes' => 35,
            'extras' => [
                'child-seat' => 2,
                'gate-pickup' => 1,
            ],
            'payment_method' => 'card',
            'estimated_total' => 1,
            'source_page' => '/booking/',
            'locale' => 'en',
            'consent' => true,
        ])->assertCreated()
            ->assertJsonPath('estimated_total', '163.00');

        $inquiry = BookingInquiry::query()->where('reference', $response->json('reference'))->firstOrFail();

        $this->assertEquals('163.00', $inquiry->estimated_total);
        $this->assertSame('Normal car', $inquiry->vehicle_name);
        $this->assertCount(2, $inquiry->selected_extras);
        $this->assertSame('card', $inquiry->payment_method);
    }

    public function test_an_unknown_route_returns_a_clear_validation_message(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $this->postJson('/api/booking/quote', [
            'booking_mode' => 'transfer',
            'pickup' => 'An address that does not exist',
            'dropoff' => 'Schiphol Airport',
            'trip_type' => 'one_way',
            'passengers' => 2,
            'luggage' => 2,
        ])->assertUnprocessable()
            ->assertJsonPath(
                'message',
                "We could not find 'An address that does not exist'. Add a street, city, hotel or airport name."
            );
    }

    public function test_hourly_requests_are_accepted_without_inventing_a_price(): void
    {
        $vehicle = BookingVehicle::query()->where('key', 'business-e-class')->firstOrFail();

        $response = $this->postJson('/api/inquiries', [
            'type' => 'booking',
            'name' => 'Morgan Example',
            'email' => 'morgan@example.org',
            'phone' => '+31612345678',
            'pickup' => 'Amsterdam',
            'dropoff' => null,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'luggage' => 2,
            'vehicle_id' => $vehicle->id,
            'booking_mode' => 'hourly',
            'trip_type' => 'one_way',
            'route_distance_km' => 0,
            'route_duration_minutes' => 0,
            'hourly_duration_hours' => 4,
            'payment_method' => 'cash',
            'consent' => true,
        ])->assertCreated()
            ->assertJsonPath('estimated_total', null);

        $inquiry = BookingInquiry::query()->where('reference', $response->json('reference'))->firstOrFail();
        $this->assertNull($inquiry->estimated_total);
        $this->assertSame('hourly', $inquiry->booking_mode);
    }

    public function test_an_admin_can_manage_vehicle_and_extra_prices(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $catalog = $this->getJson('/api/admin/booking-settings')
            ->assertOk()
            ->json();

        $catalog['vehicles'][0]['rate_per_km'] = 1.95;
        $catalog['vehicles'][0]['hourly_rate'] = 55;
        $catalog['extras'][0]['price'] = 17.5;

        $this->putJson('/api/admin/booking-settings', [
            'vehicles' => $catalog['vehicles'],
            'extras' => $catalog['extras'],
        ])->assertOk()
            ->assertJsonPath('message', 'Booking vehicles, fares and extras saved.');

        $this->assertDatabaseHas('booking_vehicles', [
            'key' => 'normal-car',
            'rate_per_km' => 1.95,
            'hourly_rate' => 55,
        ]);
        $this->assertDatabaseHas('booking_extras', [
            'key' => 'child-seat',
            'price' => 17.5,
        ]);
    }
}
