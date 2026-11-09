<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->text('icon')->nullable();
            $table->text('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->text('description');
            $table->string('short_description', 500)->nullable();
            $table->decimal('base_price', 12, 2);
            $table->string('price_unit', 20)->default('FCFA');
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_fixed_price')->default(true);
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_order')->default(0);
            $table->json('images')->nullable();
            $table->text('requirements')->nullable();
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_variations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->string('adjustment_type', 10)->default('add');
            $table->integer('duration_adjustment')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->integer('max_bookings')->nullable();
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->string('business_name', 200)->nullable();
            $table->string('business_registration_number', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->text('bio')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->text('business_address')->nullable();
            $table->string('business_city', 100)->nullable();
            $table->string('business_country', 100)->nullable();
            $table->decimal('business_latitude', 10, 8)->nullable();
            $table->decimal('business_longitude', 11, 8)->nullable();
            $table->integer('service_radius_km')->default(50);
            $table->boolean('serves_at_customer_location')->default(false);
            $table->boolean('serves_at_business_location')->default(true);
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | suspended
            $table->string('verification_token', 255)->nullable();
            $table->timestamp('verification_token_expires')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('id_card_url')->nullable();
            $table->text('business_license_url')->nullable();
            $table->text('tax_certificate_url')->nullable();
            $table->json('portfolio_images')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_name', 200)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_routing_number', 50)->nullable();
            $table->string('mobile_money_number', 20)->nullable();
            $table->string('mobile_money_provider', 20)->nullable();
            $table->boolean('auto_accept_bookings')->default(false);
            $table->json('notification_preferences')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provider_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('provider_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('document_name', 255);
            $table->text('document_url');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_documents');
        Schema::dropIfExists('provider_categories');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('service_availability');
        Schema::dropIfExists('service_variations');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
    }
};
