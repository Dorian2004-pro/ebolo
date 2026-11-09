<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artisan_profiles', function (Blueprint $table) {
            $table->string('identity_type')->nullable();
            $table->string('identity_recto_path')->nullable();
            $table->string('identity_verso_path')->nullable();
            $table->timestamp('identity_submitted_at')->nullable();
            $table->text('identity_rejection_reason')->nullable();
            $table->json('availability')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('artisan_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'identity_type',
                'identity_recto_path',
                'identity_verso_path',
                'identity_submitted_at',
                'identity_rejection_reason',
                'availability',
            ]);
        });
    }
};
