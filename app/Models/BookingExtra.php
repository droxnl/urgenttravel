<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingExtra extends Model
{
    protected $fillable = [
        'key',
        'name_en',
        'name_nl',
        'description_en',
        'description_nl',
        'price',
        'pricing_type',
        'max_quantity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
