<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_nl');
            $table->string('badge_en')->nullable();
            $table->string('badge_nl')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_nl')->nullable();
            $table->string('category', 30)->default('comfort');
            $table->decimal('rate_per_km', 8, 2);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->unsignedTinyInteger('max_passengers')->default(4);
            $table->unsignedTinyInteger('max_luggage')->default(3);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name_en');
            $table->string('name_nl');
            $table->text('description_en')->nullable();
            $table->text('description_nl')->nullable();
            $table->decimal('price', 8, 2);
            $table->string('pricing_type', 20)->default('per_item');
            $table->unsignedTinyInteger('max_quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extras');
        Schema::dropIfExists('booking_vehicles');
    }
};
