<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Remplace la route POST /api/admin/seed du projet Next.js (qui était publique, donc dangereuse) :
 * le compte super administrateur est créé en ligne de commande uniquement.
 */
class AdminSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage_users' => 'Utilisateurs',
        'manage_providers' => 'Prestataires',
        'manage_services' => 'Services',
        'manage_categories' => 'Services',
        'manage_bookings' => 'Réservations',
        'manage_payments' => 'Paiements',
        'manage_disputes' => 'Litiges',
        'manage_content' => 'Contenu',
        'manage_settings' => 'Paramètres',
        'view_audit_logs' => 'Rapports',
        'view_reports' => 'Rapports',
    ];

    public function run(): void
    {
        $email = config('app.admin_email');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'password' => config('app.admin_password'),
                'first_name' => 'Admin',
                'last_name' => 'EBOLO',
                'phone' => '+237600000000',
                'phone_verified' => true,
                'email_verified' => true,
                'is_active' => true,
                'is_suspended' => false,
                'city' => 'Douala',
                'country' => 'Cameroun',
            ]
        );

        $user->roles()->firstOrCreate(['role' => 'admin']);

        Admin::updateOrCreate(
            ['user_id' => $user->id],
            ['admin_role' => 'super_admin', 'permissions' => array_keys(self::PERMISSIONS), 'is_active' => true]
        );

        // Catalogue des permissions (table `permissions`)
        foreach (self::PERMISSIONS as $name => $category) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['id' => DB::table('permissions')->where('name', $name)->value('id') ?? (string) Str::uuid(), 'category' => $category, 'description' => $name, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Réglages par défaut
        foreach ([
            ['key' => 'platform.currency', 'value' => 'XOF', 'category' => 'general', 'description' => 'Devise de la plateforme (FCFA)'],
            ['key' => 'commission.default_rate', 'value' => 10, 'category' => 'finance', 'description' => 'Commission par défaut (%)'],
        ] as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
