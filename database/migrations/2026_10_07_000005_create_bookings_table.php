<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('artisan_id')->constrained('artisan_profiles')->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('location_type')->default('studio'); // studio, home, salon
            $table->text('location_details')->nullable();
            $table->date('booking_date');
            $table->time('booking_time');
            $table->string('status')->default('pending'); // pending, confirmed, rejected, completed, cancelled
            $table->decimal('total_price', 12, 2);
            $table->string('payment_status')->default('unpaid'); // unpaid, pending, paid, failed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};