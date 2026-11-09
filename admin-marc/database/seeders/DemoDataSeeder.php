<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Payout;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Jeu de données de démonstration — univers Ébolo (artisanes de Douala et Yaoundé,
 * paiement Mobile Money avec séquestre). Déterministe : mt_srand fixe.
 */
class DemoDataSeeder extends Seeder
{
    private int $phoneSeq = 50000000;

    public function run(): void
    {
        if (Booking::count() > 0) {
            $this->command?->warn('Des réservations existent déjà : jeu de démonstration ignoré.');

            return;
        }

        mt_srand(237);
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        // ------------------------------------------------------------ Catégories
        $cats = [];
        foreach ([
            ['Coiffure & Tresses', 'scissors'], ['Couture & Mode', 'shirt'], ['Maquillage', 'sparkles'],
            ['Onglerie & Soins', 'hand'], ['Bien-être', 'flower'],
        ] as $i => [$name, $icon]) {
            $cats[$name] = Category::create(['name' => $name, 'slug' => Str::slug($name), 'icon' => $icon, 'display_order' => $i, 'is_active' => true]);
        }

        // ------------------------------------------------------------ Prestataires
        $providerDefs = [
            ['Marie', 'Kouam', 'Atelier Bonapriso', 'Douala', 'approved', 'Coiffure & Tresses', 9, 4.9, 'mtn'],
            ['Aïcha', 'Njoya', 'Aïcha Braids', 'Yaoundé', 'approved', 'Coiffure & Tresses', 6, 4.7, 'orange'],
            ['Estelle', 'Mbarga', 'Maison Estelle Couture', 'Yaoundé', 'approved', 'Couture & Mode', 12, 4.8, 'orange'],
            ['Carine', 'Nkoulou', 'Studio Carine Makeup', 'Douala', 'approved', 'Maquillage', 5, 4.6, 'mtn'],
            ['Sandrine', 'Tchoumi', 'Les Mains de Fée', 'Douala', 'approved', 'Onglerie & Soins', 7, 4.5, 'mtn'],
            ['Prisca', 'Fotso', 'Prisca Beauty Lab', 'Douala', 'pending', 'Maquillage', 3, 0, 'mtn'],
            ['Josiane', 'Essomba', 'Josy Couture', 'Yaoundé', 'pending', 'Couture & Mode', 4, 0, 'orange'],
            ['Gladys', 'Ewane', 'Gladys Spa', 'Douala', 'rejected', 'Bien-être', 2, 0, 'orange'],
        ];

        $providerUsers = []; // clé => User (prestataires approuvés)
        foreach ($providerDefs as $i => [$first, $last, $business, $city, $status, $cat, $years, $rating, $momo]) {
            $user = $this->makeUser($first, $last, $city, $now->copy()->subDays(70 - $i * 3), 'provider');
            $provider = Provider::create([
                'user_id' => $user->id,
                'business_name' => $business,
                'bio' => "Artisane {$cat} à {$city}. {$years} ans d'expérience.",
                'years_of_experience' => $years,
                'rating' => $rating,
                'business_city' => $city,
                'business_country' => 'Cameroun',
                'status' => $status,
                'verified_at' => $status === 'approved' ? $now->copy()->subDays(60 - $i * 3) : null,
                'rejected_at' => $status === 'rejected' ? $now->copy()->subDays(4) : null,
                'rejection_reason' => $status === 'rejected' ? 'Pièce d\'identité illisible — merci de la renvoyer.' : null,
                'id_card_url' => $status === 'rejected' ? null : 'private/ids/'.Str::slug($business).'.jpg',
                'business_license_url' => $status === 'approved' ? 'private/licences/'.Str::slug($business).'.pdf' : null,
                'mobile_money_provider' => $momo === 'mtn' ? 'mtn_momo' : 'orange_money',
                'mobile_money_number' => $user->phone,
                'serves_at_customer_location' => $i % 2 === 0,
                'created_at' => $user->created_at,
                'updated_at' => $user->created_at,
            ]);
            $provider->categories()->attach($cats[$cat]->id);
            if ($status === 'approved') {
                $providerUsers[] = ['user' => $user, 'provider' => $provider, 'category' => $cats[$cat]];
            }
        }

        // ------------------------------------------------------------ Services
        $serviceDefs = [
            0 => [['Knotless Braids moyennes', 8500, 240, true], ['Soin profond & brushing', 7500, 90, false]],
            1 => [['Cornrows avec motifs', 5000, 150, false], ['Vanilles twist', 6000, 120, false]],
            2 => [['Robe sur-mesure en wax', 35000, 120, true], ['Retouche & ourlet', 3500, 45, false]],
            3 => [['Maquillage de soirée', 12000, 75, false], ['Maquillage mariée', 40000, 150, true]],
            4 => [['Pose gel & nail art', 10000, 120, false], ['Manucure & pédicure', 8000, 90, false]],
        ];
        $services = [];
        foreach ($serviceDefs as $idx => $defs) {
            foreach ($defs as [$name, $price, $minutes, $featured]) {
                $services[] = Service::create([
                    'category_id' => $providerUsers[$idx]['category']->id,
                    'provider_id' => $providerUsers[$idx]['user']->id,
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
                    'description' => $name.' réalisé par une artisane vérifiée Ébolo.',
                    'short_description' => $name,
                    'base_price' => $price,
                    'duration_minutes' => $minutes,
                    'is_featured' => $featured,
                    'is_active' => true,
                ]);
            }
        }
        $services[3]->update(['is_active' => false]); // un service désactivé pour l'exemple

        // ------------------------------------------------------------ Clientes
        $customerNames = [
            ['Vanessa', 'Ndongo'], ['Grace', 'Mbella'], ['Léa', 'Fotso'], ['Armelle', 'Tagne'], ['Nadège', 'Biyong'],
            ['Flora', 'Manga'], ['Sylvie', 'Atangana'], ['Christelle', 'Onana'], ['Mireille', 'Ekotto'], ['Laure', 'Njock'],
            ['Rose', 'Mballa'], ['Hélène', 'Kamga'], ['Ursule', 'Tchakounté'], ['Dorine', 'Nguefack'], ['Joëlle', 'Abega'],
            ['Cynthia', 'Bell'], ['Pauline', 'Ewodo'], ['Michèle', 'Noubissi'], ['Yvette', 'Simo'], ['Mariette', 'Fouda'],
            ['Clarisse', 'Owona'], ['Bernadette', 'Eyenga'], ['Danielle', 'Mouelle'], ['Nicole', 'Ngo Bissa'],
        ];
        $customers = [];
        foreach ($customerNames as $i => [$first, $last]) {
            $created = $i < 5 ? $now->copy()->subHours(mt_rand(2, 20 + $i * 3)) // quelques inscriptions « aujourd'hui »
                : $now->copy()->subDays(mt_rand(1, 55))->subMinutes(mt_rand(0, 600));
            $customers[] = $this->makeUser($first, $last, $i % 3 === 0 ? 'Yaoundé' : 'Douala', $created, 'customer');
        }
        $customers[22]->update(['is_suspended' => true, 'suspension_reason' => 'Annulations répétées le jour même.']);
        $customers[23]->update(['is_active' => false]);

        // ------------------------------------------------------------ Réservations
        $bookable = array_values(array_filter($services, fn ($s) => $s->is_active));
        $slots = ['09:00', '10:30', '12:00', '14:00', '15:30', '17:00'];
        $total = 96;
        $completedBookings = [];
        $paymentSeq = 1;

        for ($i = 1; $i <= $total; $i++) {
            $service = $bookable[mt_rand(0, count($bookable) - 1)];
            $customer = $customers[mt_rand(0, 21)];

            // 6 rendez-vous aujourd'hui, le reste réparti de J-27 à J+6
            $offset = $i <= 6 ? 0 : mt_rand(-27, 6);
            $date = $today->copy()->addDays($offset);
            $start = Carbon::parse($date->toDateString().' '.$slots[mt_rand(0, count($slots) - 1)]);
            $end = $start->copy()->addMinutes($service->duration_minutes);

            if ($offset < 0) {
                $status = mt_rand(1, 100) <= 80 ? 'completed' : 'cancelled';
            } elseif ($offset === 0) {
                $status = ['completed', 'completed', 'in_progress', 'confirmed', 'confirmed', 'pending'][($i - 1) % 6];
            } else {
                $status = mt_rand(1, 100) <= 70 ? 'confirmed' : 'pending';
            }

            $createdAt = $offset <= 0
                ? $start->copy()->subDays(mt_rand(0, 4))->subHours(mt_rand(1, 20))
                : $now->copy()->subDays(mt_rand(0, 6))->subHours(mt_rand(0, 12));
            if ($createdAt->greaterThan($now)) {
                $createdAt = $now->copy()->subMinutes(mt_rand(5, 300));
            }

            $price = (float) $service->base_price;
            $commission = round($price * 0.10);

            $completedAt = null;
            if ($status === 'completed') {
                $completedAt = $offset === 0
                    ? $today->copy()->addMinutes(mt_rand(1, max(2, (int) $today->diffInMinutes($now) - 5)))
                    : $end->copy();
            }

            $booking = Booking::create([
                'reference' => sprintf('EBL-%05d', 10000 + $i),
                'customer_id' => $customer->id,
                'provider_id' => $service->provider_id,
                'service_id' => $service->id,
                'booking_date' => $date->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'duration_minutes' => $service->duration_minutes,
                'location_type' => mt_rand(0, 2) === 0 ? 'domicile' : 'salon',
                'city' => $customer->city,
                'country' => 'Cameroun',
                'base_price' => $price,
                'variation_price' => 0,
                'total_price' => $price,
                'currency' => 'XOF',
                'commission_rate' => 10,
                'commission_amount' => $commission,
                'provider_earnings' => $price - $commission,
                'status' => $status,
                'confirmed_at' => in_array($status, ['confirmed', 'in_progress', 'completed'], true) ? $createdAt->copy()->addHours(1) : null,
                'started_at' => in_array($status, ['in_progress', 'completed'], true) ? $start : null,
                'completed_at' => $completedAt,
                'cancelled_at' => $status === 'cancelled' ? $createdAt->copy()->addHours(3) : null,
                'cancellation_reason' => $status === 'cancelled' ? 'Empêchement de la cliente' : null,
                'created_at' => $createdAt,
                'updated_at' => $completedAt ?? $createdAt,
            ]);

            // ---- Transactions Mobile Money : fonds consignés (séquestre) jusqu'à la fin de la prestation
            $gateway = mt_rand(0, 1) ? 'mtn_momo' : 'orange_money';
            $makeTx = function (string $type, string $direction, string $txStatus, $userId, $at, ?string $failure = null, ?float $amount = null) use ($booking, $price, $gateway, &$paymentSeq) {
                return Transaction::create([
                    'reference' => sprintf('TX-%06d', $paymentSeq++),
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'amount' => $amount ?? $price,
                    'currency' => 'XOF',
                    'payment_gateway' => $gateway,
                    'gateway_transaction_id' => strtoupper(Str::random(12)),
                    'transaction_type' => $type,
                    'direction' => $direction,
                    'status' => $txStatus,
                    'failure_reason' => $failure,
                    'processed_at' => $at,
                    'completed_at' => in_array($txStatus, ['completed', 'held'], true) ? $at : null,
                    'failed_at' => $txStatus === 'failed' ? $at : null,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            };

            if (in_array($status, ['confirmed', 'in_progress'], true)) {
                $makeTx('payment', 'in', 'held', $customer->id, $createdAt->copy()->addHours(1));
            } elseif ($status === 'completed') {
                $makeTx('payment', 'in', 'completed', $customer->id, $createdAt->copy()->addHours(1));
                if (mt_rand(1, 100) <= 75) {
                    $makeTx('payout', 'out', 'completed', $service->provider_id, $completedAt->copy()->addHours(2), null, $price - $commission);
                }
                $completedBookings[] = [$booking, $service];
            } elseif ($status === 'pending') {
                if (mt_rand(0, 1)) {
                    $makeTx('payment', 'in', 'pending', $customer->id, $createdAt->copy()->addMinutes(10));
                } elseif (mt_rand(0, 3) === 0) {
                    $makeTx('payment', 'in', 'failed', $customer->id, $createdAt->copy()->addMinutes(10), 'Solde Mobile Money insuffisant');
                }
            } elseif ($status === 'cancelled' && mt_rand(0, 1)) {
                $makeTx('refund', 'out', 'completed', $customer->id, $createdAt->copy()->addHours(5));
            }
        }

        // ------------------------------------------------------------ Reversements en attente (par prestataire)
        foreach ($providerUsers as $pu) {
            $net = Booking::where('provider_id', $pu['user']->id)->where('status', 'completed')
                ->orderByDesc('completed_at')->limit(3)->sum('provider_earnings');
            if ($net > 0) {
                Payout::create([
                    'reference' => 'PO-'.strtoupper(Str::random(8)),
                    'provider_id' => $pu['user']->id,
                    'amount' => $net,
                    'fees' => 0,
                    'net_amount' => $net,
                    'payout_method' => $pu['provider']->mobile_money_provider,
                    'payout_details' => ['number' => $pu['provider']->mobile_money_number],
                    'status' => 'pending',
                    'period_start' => $today->copy()->subDays(7)->toDateString(),
                    'period_end' => $today->toDateString(),
                ]);
            }
        }

        // ------------------------------------------------------------ Avis
        $comments = [
            [5, 'Un travail d\'orfèvre', 'Ponctuelle, très professionnelle, je recommande les yeux fermés.'],
            [5, 'Sublime', 'Exactement ce que je voulais, merci pour le soin apporté.'],
            [4, 'Très bien', 'Résultat superbe, un peu de retard à l\'arrivée.'],
            [5, 'Parfait', 'Salon propre et climatisé, accueil chaleureux.'],
            [4, 'Satisfaite', 'Belle prestation, je reviendrai.'],
        ];
        foreach ($completedBookings as $n => [$booking, $service]) {
            if ($n % 5 < 3) {
                [$rating, $title, $comment] = $comments[$n % count($comments)];
                Review::create([
                    'booking_id' => $booking->id, 'customer_id' => $booking->customer_id, 'provider_id' => $booking->provider_id,
                    'service_id' => $service->id, 'rating' => $rating, 'title' => $title, 'comment' => $comment,
                    'created_at' => $booking->completed_at, 'updated_at' => $booking->completed_at,
                ]);
            }
        }
        foreach ($providerUsers as $pu) {
            $count = Review::where('provider_id', $pu['user']->id)->count();
            if ($count > 0) {
                $pu['provider']->update([
                    'total_reviews' => $count,
                    'rating' => round((float) Review::where('provider_id', $pu['user']->id)->avg('rating'), 2),
                ]);
            }
        }

        // ------------------------------------------------------------ Litiges
        $disputeDefs = [
            ['service_quality', 'Les tresses se sont défaites au bout de deux jours.', 'open', 'high'],
            ['no_show', 'La prestataire ne s\'est pas présentée au rendez-vous à domicile.', 'investigating', 'medium'],
            ['payment_issue', 'Débit Mobile Money effectué deux fois pour la même réservation.', 'resolved', 'low'],
        ];
        foreach ($disputeDefs as $n => [$type, $reason, $dStatus, $priority]) {
            [$booking] = $completedBookings[$n * 4] ?? $completedBookings[0];
            Dispute::create([
                'reference' => sprintf('LIT-%04d', 101 + $n),
                'booking_id' => $booking->id,
                'raised_by' => $booking->customer_id,
                'raised_against' => $booking->provider_id,
                'reason' => $reason,
                'reason_type' => $type,
                'amount_disputed' => $booking->total_price,
                'status' => $dStatus,
                'priority' => $priority,
                'resolution' => $dStatus === 'resolved' ? 'Remboursement du débit en double validé par l\'équipe.' : null,
                'resolved_at' => $dStatus === 'resolved' ? $now->copy()->subDay() : null,
                'created_at' => $now->copy()->subDays(3 - $n),
                'updated_at' => $now->copy()->subDays(3 - $n),
            ]);
        }
    }

    private function makeUser(string $first, string $last, string $city, Carbon $createdAt, string $role): User
    {
        $user = User::create([
            'email' => Str::slug($first.' '.$last, '.').'@exemple.cm',
            'password' => null, // les comptes clients/prestataires se connectent par code SMS, pas par mot de passe
            'first_name' => $first,
            'last_name' => $last,
            'phone' => '+237'.(6 * 100000000 + $this->phoneSeq++),
            'phone_verified' => true,
            'email_verified' => true,
            'city' => $city,
            'country' => 'Cameroun',
            'is_active' => true,
            'last_login_at' => $createdAt->copy()->addHours(mt_rand(1, 400)) > now() ? now()->subHours(mt_rand(1, 40)) : $createdAt->copy()->addHours(mt_rand(1, 400)),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $user->roles()->create(['role' => $role]);

        return $user;
    }
}
