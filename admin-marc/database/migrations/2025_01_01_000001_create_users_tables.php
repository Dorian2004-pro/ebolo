<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255)->unique();
            $table->string('password')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->unique();
            $table->boolean('phone_verified')->default(false);
            $table->boolean('email_verified')->default(false);
            $table->text('avatar_url')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('momo_provider', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50);
            $table->timestamps();
        });

        // Table standard de Laravel (réinitialisation de mot de passe).
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Jetons d'API Sanctum (users.id est un UUID -> uuidMorphs).
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('email_verification_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
    }
};
