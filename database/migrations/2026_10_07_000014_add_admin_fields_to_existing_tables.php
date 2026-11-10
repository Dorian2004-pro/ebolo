<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable();
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('artisan_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('artisan_profiles', 'status')) {
                $table->string('status')->default('pending');
            }

            if (! Schema::hasColumn('artisan_profiles', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }

            if (! Schema::hasColumn('artisan_profiles', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
        });

        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'is_featured')) {
                $table->boolean('is_featured')->default(false);
            }
        });

        DB::table('artisan_profiles')
            ->where('is_verified', true)
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (Schema::hasColumn('services', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });

        Schema::table('artisan_profiles', function (Blueprint $table): void {
            $columns = array_filter(
                ['status', 'rejection_reason', 'verified_at'],
                fn (string $column): bool => Schema::hasColumn('artisan_profiles', $column),
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_filter(
                ['suspension_reason', 'last_login_at', 'deleted_at'],
                fn (string $column): bool => Schema::hasColumn('users', $column),
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
