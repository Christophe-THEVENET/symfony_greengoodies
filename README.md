# GreenGoodies

> **Pas de Node.js / npm.** Le front est géré entièrement par **Symfony Asset Mapper** (importmap) + **Symfony UX/Stimulus**, sans bundler ni `node_modules`. Le SCSS est compilé par `symfonycasts/sass-bundle` (binaire Dart Sass autonome).

> **Refonte design.** Le projet initial a été développé **sans IA**. Une **refonte complète de l'interface** vient d'être réalisée avec **Claude Design** (maquettes) et **Claude Code** (intégration).

## Formation Bootcamp avancé Symfony OpenClassRooms

### Projet final - Mettre en place un site de e-commerce avec Symfony

Vous êtes développeur PHP/Symfony en freelance. GreenGoodies, une boutique lyonnaise spécialisée dans la vente de produits biologiques, éthiques et écologiques, souhaite élargir sa cible commerciale.
Vous êtes en contact avec Aurélie, la gérante de la boutique. Elle a déjà les maquettes de son futur site en sa possession et vous demande de développer le site en question.

## Objectif du projet

Réaliser un site web complet avec PHP et Symfony, comprenant une base de données, et un espace utilisateur ainsi qu’une API pour une boutique en ligne.

## Objectifs pédagogiques

-   Mettre en place une base de données avec Symfony
-   Développer le back-end d'une application Symfony
-   Mettre en place les échanges de données pour les afficher via des vues Twig
-   Créer une API pour un site Symfony

## Cahier des charges

* Maquettes fournies : [voir les maquettes](https://www.figma.com/design/dwbwGIJqxan1qJFwKt8juV/Green-Goodies?node-id=0-1&p=f).

* Spécifications fonctionnelles : [voir les spécifications](https://s3.eu-west-1.amazonaws.com/course.oc-static.com/projects/876_DA_PHP_Sf_V2/P13/Spe%CC%81cifications+fonctionnelles+-+GreenGoodies.pdf)

* Spécifications techniques de l'API : [voir les spécifications de l'API](https://s3.eu-west-1.amazonaws.com/course.oc-static.com/projects/876_DA_PHP_Sf_V2/P13/Spe%CC%81cifications+techniques+de+l'API+-+GreenGoodies.pdf)

## Features

    * Conception de la base de données;
    * Fixtures produits et utilisateurs;
    * Intégration des maquettes avec Twig, Scss, et JavaScript;
    * Authentification et inscription des utilisateurs;
    * Validations des donnèes frontend et back-end;
    * Système de panier d'achat et de commande;
    * Compte utilisateur avec historique des commandes;
    * API sécurisée RESTful avec JWT pour les produits;
    * bonus:
    * La gestion du panier est asynchrone;
    * Système de notifications centralisé;
    * Symfony UX Toggle Password pour afficher/masquer les mots de passe dans les formulaires;
    * Validation front en temps réel avec Stimulus;

## Prérequis

-   Docker;
-   Docker Compose (plugin `docker compose`);
-   Git.

Tout le reste (PHP 8.3, Composer, MySQL, Dart Sass) tourne dans les conteneurs : rien à installer en local.

## Installation (environnement local Docker)

1. Cloner le dépôt :

```bash
git clone https://github.com/Christophe-THEVENET/symfony_greengoodies.git
cd symfony_greengoodies/
```

2. Créer le fichier d'environnement local (non versionné) et renseigner les variables :

```bash
cp .env .env.local
```

Variables à compléter dans `.env.local` :

```dotenv
APP_ENV=dev
APP_SECRET=               # chaîne aléatoire

MYSQL_DATABASE=           # ex. greengoodies
MYSQL_USER=
MYSQL_PASSWORD=
MYSQL_ROOT_PASSWORD=
# DATABASE_URL est déjà construit à partir des variables MYSQL_* ci-dessus (hôte = db)

###> lexik/jwt-authentication-bundle ###
JWT_PASSPHRASE=           # passphrase des clés JWT (voir étape 6)
###< lexik/jwt-authentication-bundle ###

STRIPE_PUBLIC_KEY=        # clés depuis le dashboard Stripe (mode test)
STRIPE_SECRET_KEY=
```

3. Construire et démarrer les conteneurs. En local, `compose.yaml` et `compose.override.yaml` sont chargés automatiquement (nginx exposé sur le port **8088**, MySQL 8, Mailpit) :

```bash
docker compose build
docker compose up -d
```

4. Installer les dépendances et préparer la base de données dans le conteneur `php` :

```bash
docker compose exec php composer install
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:fixtures:load
```

5. Compiler les assets (SCSS + Asset Mapper) :

```bash
docker compose exec php php bin/console sass:build
docker compose exec php php bin/console asset-map:compile
```

> Astuce dev : `docker compose exec php php bin/console sass:build --watch` recompile le SCSS à la volée.

6. Générer la paire de clés JWT (API sécurisée Lexik). La passphrase doit correspondre à `JWT_PASSPHRASE` de `.env.local` ; les clés sont créées dans `config/jwt/` (non versionnées) :

```bash
docker compose exec php php bin/console lexik:jwt:generate-keypair
```

## Utilisation

-   Application : <http://localhost:8088>
-   Mailpit (mails de test) : <http://localhost:8025>

Connectez-vous avec les comptes créés dans les fixtures (voir les identifiants dans le fichier `src/DataFixtures/AppFixtures.php`) ou inscrivez-vous en tant que nouvel utilisateur.

## Déploiement (production)

Site en production : <https://greengoodies.space/>

La production tourne sous Docker via `docker-compose.yml` (services `db`, `php`, `nginx` avec certificats Let's Encrypt) et un fichier `.env.prod` (non versionné) contenant les secrets.

Le déploiement est automatisé par le script `deploy.sh`, exécuté sur le serveur depuis `/var/www/greengoodies`. Il :

1. récupère la dernière version (`git pull origin master`) ;
2. corrige les permissions (`www-data`) ;
3. installe les dépendances Composer (`--no-dev --optimize-autoloader`) ;
4. joue les migrations Doctrine ;
5. compile les assets (`sass:build` + `asset-map:compile`) ;
6. vide le cache, le tout en `--env=prod`.

```bash
./deploy.sh
```

> Les commandes ciblent le conteneur `greengoodies-php-1`. Adaptez ce nom si le projet Compose est lancé sous un autre nom.
