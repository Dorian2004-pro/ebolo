# Ébolo — espace client

Application Laravel + Vue indépendante pour l’inscription, la connexion et le parcours client d’Ébolo. Les comptes, prestations et rendez-vous restent gérés par l’API du backend existant ; cette application ne crée pas de deuxième base utilisateur.

## Prérequis

- PHP 8.3+ et Composer
- Node.js 20+ et npm
- Le backend Ébolo démarré et sa base de données configurée

## Configuration locale

Depuis `ebolo-client` :

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
```

Configurez ensuite `.env` :

```dotenv
APP_URL=http://127.0.0.1:8002
EBOLO_API_URL=http://127.0.0.1:8003/api
EBOLO_CLIENT_ROLE_ID=1
```

Le backend actuel utilise l’identifiant `1` pour le rôle client. Si la base est différente, retrouvez son identifiant avec `php artisan tinker --execute="echo App\Models\Role::where('slug', 'client')->value('id');"` depuis la racine du backend, puis adaptez cette valeur avant d’ouvrir les inscriptions.

Lancez l’API du backend sur l’adresse configurée, puis démarrez l’interface dans deux terminaux :

```powershell
php artisan serve
npm run dev
```

L’instance Ébolo utilise le port `8003`, pour éviter le serveur d’une autre application déjà présent sur `8000`. Démarrez le backend depuis `C:\Users\USER\Ebolo_backend` avec `php artisan serve --host=127.0.0.1 --port=8003`, puis l’interface client depuis ce projet avec `php artisan serve --host=127.0.0.1 --port=8002`. Ouvrez `http://127.0.0.1:8002`. Les comptes et rendez-vous sont transmis au backend. Le jeton Sanctum est conservé dans la session Laravel et n’est pas envoyé au navigateur.

## Parcours inclus

- Inscription client et connexion avec validation côté serveur et retours d’erreur.
- Consultation et recherche de prestations du catalogue backend.
- Demande de rendez-vous au salon ou à domicile.
- Consultation des rendez-vous du client et déconnexion.

## Vérification

```powershell
vendor/bin/phpunit --filter=ClientModuleTest
npm run build
```
