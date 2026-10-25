<?php

namespace Database\Seeders;

use App\Models\BookingExtra;
use App\Models\BookingVehicle;
use Illuminate\Database\Seeder;

class BookingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'key' => 'normal-car',
                'name_en' => 'Normal car',
                'name_nl' => 'Normale auto',
                'badge_en' => 'Comfort',
                'badge_nl' => 'Comfort',
                'description_en' => 'A comfortable, practical chauffeur car for everyday journeys and smaller groups.',
                'description_nl' => 'Een comfortabele, praktische chauffeursauto voor dagelijkse ritten en kleinere groepen.',
                'category' => 'comfort',
                'rate_per_km' => 1.80,
                'hourly_rate' => null,
                'max_passengers' => 4,
                'max_luggage' => 3,
                'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.26.jpeg',
                'sort_order' => 10,
            ],
            [
                'key' => 'business-e-class',
                'name_en' => 'Business car · E-Class',
                'name_nl' => 'Zakelijke auto · E-klasse',
                'badge_en' => 'Business',
                'badge_nl' => 'Zakelijk',
                'description_en' => 'An E-Class or similar executive car with a refined cabin for business and private travel.',
                'description_nl' => 'Een E-klasse of vergelijkbare directieauto met een verfijnde cabine voor zakelijke en privéreizen.',
                'category' => 'business',
                'rate_per_km' => 2.15,
                'hourly_rate' => null,
                'max_passengers' => 4,
                'max_luggage' => 3,
                'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.07.jpeg',
                'sort_order' => 20,
            ],
            [
                'key' => 'van-v-class',
                'name_en' => 'Van · V-Class / Vito',
                'name_nl' => 'Bus · V-klasse / Vito',
                'badge_en' => 'Groups',
                'badge_nl' => 'Groepen',
                'description_en' => 'A spacious V-Class or Vito for families, teams and journeys with extra luggage.',
                'description_nl' => 'Een ruime V-klasse of Vito voor gezinnen, teams en reizen met extra bagage.',
                'category' => 'van',
                'rate_per_km' => 2.80,
                'hourly_rate' => null,
                'max_passengers' => 7,
                'max_luggage' => 6,
                'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.37-2.jpeg',
                'sort_order' => 30,
            ],
            [
                'key' => 's-class-luxury',
                'name_en' => 'S-Class / EQS / A8 / 7 Series',
                'name_nl' => 'S-klasse / EQS / A8 / 7-serie',
                'badge_en' => 'First class',
                'badge_nl' => 'Eerste klasse',
                'description_en' => 'Our most exclusive class, selected for VIP arrivals and occasions that call for a flagship car.',
                'description_nl' => 'Onze meest exclusieve klasse, voor VIP-aankomsten en gelegenheden waarbij een topklasse auto past.',
                'category' => 'luxury',
                'rate_per_km' => 5.00,
                'hourly_rate' => null,
                'max_passengers' => 3,
                'max_luggage' => 2,
                'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg',
                'sort_order' => 40,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            BookingVehicle::query()->updateOrCreate(
                ['key' => $vehicle['key']],
                [...$vehicle, 'is_active' => true]
            );
        }

        $extras = [
            [
                'key' => 'child-seat',
                'name_en' => 'Child seat',
                'name_nl' => 'Kinderzitje',
                'description_en' => 'A properly fitted child seat for one passenger.',
                'description_nl' => 'Een correct gemonteerd kinderzitje voor één passagier.',
                'price' => 15.00,
                'pricing_type' => 'per_item',
                'max_quantity' => 4,
                'sort_order' => 10,
            ],
            [
                'key' => 'booster-seat',
                'name_en' => 'Booster seat',
                'name_nl' => 'Stoelverhoger',
                'description_en' => 'A booster seat for one child.',
                'description_nl' => 'Een stoelverhoger voor één kind.',
                'price' => 5.00,
                'pricing_type' => 'per_item',
                'max_quantity' => 4,
                'sort_order' => 20,
            ],
            [
                'key' => 'gate-pickup',
                'name_en' => 'Pickup inside at the gate',
                'name_nl' => 'Binnen ophalen bij de gate',
                'description_en' => 'A personal meet inside the terminal at the arrival gate.',
                'description_nl' => 'Persoonlijk onthaal binnen in de terminal bij de aankomstgate.',
                'price' => 25.00,
                'pricing_type' => 'per_booking',
                'max_quantity' => 1,
                'sort_order' => 30,
            ],
        ];

        foreach ($extras as $extra) {
            BookingExtra::query()->updateOrCreate(
                ['key' => $extra['key']],
                [...$extra, 'is_active' => true]
            );
        }
    }
}
