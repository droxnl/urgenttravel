<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->foreignId('booking_vehicle_id')
                ->nullable()
                ->after('service_type')
                ->constrained('booking_vehicles')
                ->nullOnDelete();
            $table->string('vehicle_name')->nullable()->after('booking_vehicle_id');
            $table->string('booking_mode', 20)->nullable()->after('vehicle_name');
            $table->string('trip_type', 20)->nullable()->after('booking_mode');
            $table->decimal('route_distance_km', 8, 2)->nullable()->after('trip_type');
            $table->unsignedInteger('route_duration_minutes')->nullable()->after('route_distance_km');
            $table->unsignedTinyInteger('hourly_duration_hours')->nullable()->after('route_duration_minutes');
            $table->decimal('vehicle_rate_snapshot', 8, 2)->nullable()->after('hourly_duration_hours');
            $table->json('selected_extras')->nullable()->after('vehicle_rate_snapshot');
            $table->decimal('estimated_total', 10, 2)->nullable()->after('selected_extras');
            $table->string('payment_method', 30)->nullable()->after('estimated_total');
            $table->string('invoice_address', 255)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('booking_inquiries', function (Blueprint $table) {
            $table->dropForeign(['booking_vehicle_id']);
            $table->dropColumn([
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
            ]);
        });
    }
};
