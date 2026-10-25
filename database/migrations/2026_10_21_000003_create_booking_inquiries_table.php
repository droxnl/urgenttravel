<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('type')->default('booking');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50);
            $table->string('pickup')->nullable();
            $table->string('dropoff')->nullable();
            $table->date('pickup_date')->nullable();
            $table->time('pickup_time')->nullable();
            $table->unsignedTinyInteger('passengers')->nullable();
            $table->unsignedTinyInteger('luggage')->nullable();
            $table->string('flight_number')->nullable();
            $table->string('service_type')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new');
            $table->string('source_page')->nullable();
            $table->string('locale', 10)->default('en');
            $table->boolean('consent')->default(false);
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_inquiries');
    }
};
