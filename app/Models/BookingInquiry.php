<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookingInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'type',
        'name',
        'email',
        'phone',
        'pickup',
        'dropoff',
        'pickup_date',
        'pickup_time',
        'passengers',
        'luggage',
        'flight_number',
        'service_type',
        'booking_vehicle_id',
        'vehicle_name',
        'booking_mode',
        'trip_type',
        'route_distance_km',
        'route_duration_minutes',
        'hourly_duration_hours',
        'vehicle_rate_snapshot',
        'selected_extras',
        'estimated_total',
        'payment_method',
        'invoice_address',
        'message',
        'status',
        'source_page',
        'locale',
        'consent',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'consent' => 'boolean',
        'passengers' => 'integer',
        'luggage' => 'integer',
        'route_distance_km' => 'decimal:2',
        'route_duration_minutes' => 'integer',
        'hourly_duration_hours' => 'integer',
        'vehicle_rate_snapshot' => 'decimal:2',
        'selected_extras' => 'array',
        'estimated_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $inquiry) {
            $inquiry->reference ??= 'UTS-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        });
    }

    public function emailDeliveries(): HasMany
    {
        return $this->hasMany(EmailDelivery::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(BookingVehicle::class, 'booking_vehicle_id');
    }
}
