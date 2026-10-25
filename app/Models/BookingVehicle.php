<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingVehicle extends Model
{
    protected $fillable = [
        'key',
        'name_en',
        'name_nl',
        'badge_en',
        'badge_nl',
        'description_en',
        'description_nl',
        'category',
        'rate_per_km',
        'hourly_rate',
        'max_passengers',
        'max_luggage',
        'image',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_km' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'max_passengers' => 'integer',
            'max_luggage' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
