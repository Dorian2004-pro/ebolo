<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->integer('rating');
            $table->string('title', 200)->nullable();
            $table->text('comment')->nullable();
            $table->text('provider_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_flagged')->default(false);
            $table->text('flagged_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 50)->unique();
            $table->foreignUuid('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignUuid('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('raised_against')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('reason_type', 50);
            $table->decimal('amount_disputed', 12, 2)->nullable();
            $table->string('status', 20)->default('open'); // open | investigating | resolved | closed
            $table->string('priority', 10)->default('medium'); // low | medium | high
            $table->text('resolution')->nullable();
            $table->string('resolution_type', 50)->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispute_id')->constrained('disputes')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type', 20);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });

        // Renommée « app_notifications » pour ne pas entrer en conflit avec la table `notifications`
        // du système de notifications natif de Laravel.
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('message');
            $table->string('type', 50);
            $table->string('entity_type', 50)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->string('admin_role', 50)->default('admin'); // super_admin | admin | moderator | support
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('content_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 200)->unique();
            $table->string('title', 200);
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('page_type', 50)->default('custom');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->uuid('published_by')->nullable();
            $table->integer('version')->default(1);
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 200);
            $table->text('content');
            $table->string('announcement_type', 50)->default('general');
            $table->string('target_audience', 50)->default('all');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->uuid('published_by')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 200)->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->text('image_url');
            $table->text('link_url')->nullable();
            $table->string('link_text', 100)->nullable();
            $table->string('banner_type', 50)->default('promotion');
            $table->string('position', 50)->default('home');
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->uuid('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status', 20)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->json('value');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general');
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
        });

        Schema::create('platform_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('stat_date')->unique();
            $table->integer('total_users')->default(0);
            $table->integer('total_providers')->default(0);
            $table->integer('total_customers')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->integer('total_completed_bookings')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_commissions', 15, 2)->default(0);
            $table->decimal('total_payouts', 15, 2)->default(0);
            $table->decimal('total_refunds', 15, 2)->default(0);
            $table->integer('active_providers')->default(0);
            $table->integer('new_users_today')->default(0);
            $table->integer('new_bookings_today')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'platform_stats', 'settings', 'audit_logs', 'banners', 'announcements', 'faqs', 'content_pages',
            'role_permissions', 'roles', 'permissions', 'admins', 'app_notifications',
            'dispute_messages', 'disputes', 'reviews',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
