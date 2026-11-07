# EBOLO — source originale du panneau d'administration

Cette application Laravel 12 autonome est conservée comme référence. Le panneau actif est intégré à l'application centrale du dépôt : ses vues Vue se trouvent dans `../resources/js/admin`, et son API utilise les modèles et les routes du backend racine.

Ne pas installer ni démarrer cette copie séparément. Les migrations, identifiants, `.env` et instructions ci-dessous décrivent l'ancienne application isolée et ne s'appliquent pas à l'application centrale. Voir le [README racine](../README.md) pour l'installation actuelle.

---

Documentation de la source originale :

Migration du dashboard admin généré en Next.js (React + Drizzle + JWT) vers **Laravel 12** (API + auth Sanctum) et **Vue 3** (Vue Router, Vite).
Le design s'inspire de la maquette « Ébolo Prestige » (aubergine, or, terracotta, Plus Jakarta Sans + Inter). Le logo « E » d'origine est conservé tel quel.

## Identifiants (créés par le seeder)

| E-mail | Mot de passe |
|---|---|
| `admin@ebolo.com` | `admin123` |

Modifiables dans `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`) **avant** `php artisan db:seed`. À changer en production.

## Installation

Prérequis : PHP ≥ 8.2, Composer, Node ≥ 20.

```bash
composer setup          # install, .env, clé, SQLite, migrations + seeders, npm install, build
php artisan serve       # → http://localhost:8000  (redirige vers /admin)
```

Développement avec rechargement : `composer run dev` (serveur Laravel + Vite). Sinon `php artisan serve` suffit après `npm run build`.

### Base de données
SQLite par défaut (zéro config). Pour PostgreSQL (comme l'original) ou MySQL, décommentez le bloc correspondant dans `.env`, puis `php artisan migrate --seed`.

### Production
Mettre `SEED_DEMO_DATA=false` (ne crée que le compte admin), `APP_DEBUG=false`, changer le mot de passe admin, puis `npm run build`.

## Correspondance avec le projet Next.js

| Next.js | Laravel / Vue |
|---|---|
| `src/db/schema.ts` (Drizzle) | `database/migrations/*` + `app/Models/*` |
| `POST /api/admin/auth/login` (JWT) | `AuthController` + jetons Sanctum (24 h), anti brute-force 5 essais/min |
| `src/middleware.ts` | middleware `EnsureAdmin` + garde de routes dans `resources/js/router.js` |
| `POST /api/admin/seed` (publique) | `php artisan db:seed` (plus d'endpoint public) |
| pages `app/admin/*` | `resources/js/pages/*.vue` |
| `components/admin/*` | `resources/js/components/*.vue` |

## Modules
Tableau de bord, Utilisateurs (CRUD, suspension), Prestataires (validation/refus), Services, Réservations, Paiements (séquestre), Litiges.
Contenu, Rapports, Paramètres et Support affichent « bientôt » : ils n'existaient pas dans l'original.
