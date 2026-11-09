# Ébolo — Espace prestataire (Laravel + Vue 3)

Vues réalisées d'après la maquette : **15 Dashboard prestataire** et **16 Vérification d'identité**.

## Démarrage local (PowerShell)

Le backend principal, Laravel et Vite ont des rôles et des ports différents. `npm run dev` ne sert que les ressources Vue : l'application est servie par Laravel. Ce projet requiert PHP **8.4.1 ou plus récent** (PHP 8.4.15 est disponible dans l'installation WAMP de développement).

1. Démarrer le backend dans `C:\Users\USER\Ebolo_backend` :
   ```powershell
   php artisan serve --host 127.0.0.1 --port 8003
   ```
2. Dans ce dossier prestataire, lancer Vite dans un premier terminal :
   ```powershell
   npm install
   npm run dev -- --host 127.0.0.1
   ```
3. Dans un deuxième terminal du même dossier, lancer Laravel :
   ```powershell
   & 'C:\wamp64\bin\php\php8.4.15\php.exe' artisan serve --host 127.0.0.1 --port 8004
   ```
4. Ouvrir `http://127.0.0.1:8004/connexion`.

Ne pas affecter le port `8004` à Vite : il est réservé au site Laravel. Vite utilise son port standard `5173`.

Les paramètres du backend sont dans `.env` : `VITE_API_URL` et `VITE_ARTISAN_ROLE_ID`. Le mode réel est activé (`VITE_USE_MOCK=false`). Après une modification de ces variables Vite, redémarrer `npm run dev`.

Le code `7492` est une valeur de démonstration pour la validation du séquestre, pas un mécanisme de paiement réel.

## Routes URL
- `/connexion` et `/inscription`
- `/pro` tableau de bord · `/pro/verification` (`?onboarding=1` affiche « Étape 2 sur 3 »)
- `/pro/prestations` gestion du catalogue · `/pro/planning` rendez-vous · `/pro/messages` conversations des clientes

## Routes API utilisées (`resources/js/services/api.js`)
| Usage | Route |
|---|---|
| Inscription / connexion | `POST /register`, `POST /login`, `GET /me` |
| Profil | `GET /artisan/profile`, `PUT /artisan/profile` |
| Prestations du compte | `GET|POST /artisan/services`, `PUT|DELETE /artisan/services/{id}` |
| Réservations reçues | `GET /bookings` |
| Accepter / refuser | `PATCH /bookings/{id}/status` (`confirmed` / `rejected`) |
| Horaires | `GET|PUT /artisan/availability` |
| Messagerie | `GET /chat/{userId}/messages`, `POST /chat/send` |

| Vérification d'identité | `GET|POST /artisan/identity-verification` |
| Libération de démonstration | `POST /bookings/{id}/release` |

Une réservation destinée au tableau de bord est présentée avec `client`, `service`, `starts_at`, `ends_at` et `payment`. Les revenus du mois sont calculés localement à partir des réservations marquées `released`.

## Écarts assumés avec la maquette
- Pas de cadre de téléphone ni de barre d'état : mise en page responsive mobile-first.
- Ajout d'une section « Demandes à confirmer » (accepter/refuser, prévu par la spec).
- Verso/recto réellement téléversés (aperçu, contrôle format/poids/résolution) ; « Lisible & Validé » devient « Ajouté » car la validation est faite par la modération.
- Passeport : une seule page demandée.
