<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('raised_against')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('reason_type')->default('other');
            $table->decimal('amount_disputed', 12, 2)->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
