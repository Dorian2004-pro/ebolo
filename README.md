# EBOLO

Application Laravel centrale pour le backend EBOLO et ses modules. Le backend racine est la source de vérité pour les utilisateurs, les rôles, les prestataires, les services, les réservations et les paiements.

## Administration

Le panneau Vue 3 est servi par la même application à `/admin`. Ses sources sont dans `resources/js/admin` et ses routes API protégées sont préfixées par `/api/admin`. L'administration réutilise le schéma et l'authentification Sanctum du backend ; elle ne possède pas une seconde base utilisateurs.

L'interface d'administration et ses API partagent le schéma et les utilisateurs du backend ; aucune application ni base de données distincte n'est requise.

Le suivi de séquestre, les commissions, les reversements, les documents de vérification et les notifications de litige ne sont pas encore gérés par le backend principal ; les écrans correspondants n'inventent pas ces données.

## Installation locale

Prérequis : PHP 8.3+, Composer, Node.js 20+, et MySQL/MariaDB (WAMP).

```bash
composer setup
php artisan db:seed
```

Le fichier `.env.example` configure MySQL sur `127.0.0.1:3306`, base `ebolo`, utilisateur local `root`. Crée la base `ebolo` dans phpMyAdmin si elle n’existe pas et ajuste les identifiants dans `.env` selon ta configuration WAMP. Les nouveaux comptes et données seront enregistrés dans cette base.

Le seed crée des comptes et des données de démonstration. En local, le compte administrateur est `admin@ebolo.com` avec le mot de passe `password123`. Ne réutilisez pas ce mot de passe en production.

Pour démarrer, dans deux terminaux depuis la racine :

```bash
php artisan serve
npm run dev
```

L'interface d'administration est disponible à `http://localhost:8000/admin`.
La base MySQL est consultable dans [phpMyAdmin](http://127.0.0.1/phpmyadmin5.2.3/).

## Vérification

```bash
php artisan test
npm run build
```

## API d'administration

- `POST /api/admin/auth/login`
- `GET /api/admin/auth/me` et `POST /api/admin/auth/logout`
- `GET /api/admin/dashboard`
- `GET|POST /api/admin/users` et routes CRUD associées
- `GET /api/admin/providers`, `services`, `bookings`, `payments` et `disputes`

Toutes les routes sauf la connexion exigent un jeton Sanctum et un compte actif ayant le rôle `admin`.
