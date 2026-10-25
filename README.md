# GreenGoodies

L'appli est en ligne ici : <https://greengoodies.eu>

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4_LTS-000000?logo=symfony&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine_ORM-3.x-FC6A31?logo=doctrine&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4_LTS-4479A1?logo=mysql&logoColor=white)
![Stimulus](https://img.shields.io/badge/Symfony_UX_/_Stimulus-2.x-000000?logo=stimulus&logoColor=white)
![Sass](https://img.shields.io/badge/Sass-Dart-CC6699?logo=sass&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Lexik-000000?logo=jsonwebtokens&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-API-635BFF?logo=stripe&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-stable-009639?logo=nginx&logoColor=white)

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

Tout le reste (PHP 8.5, Composer, MySQL, Dart Sass) tourne dans les conteneurs : rien à installer en local.

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

3. Construire et démarrer les conteneurs. En local, `compose.yaml` et `compose.override.yaml` sont chargés automatiquement (nginx exposé sur le port **8088**, MySQL 8.4, Mailpit) :

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

-   Application (local) : <http://localhost:8088>
-   Mailpit (mails de test) : <http://localhost:8025>
-   Production : <https://greengoodies.eu>

Connectez-vous avec les comptes créés dans les fixtures (voir les identifiants dans le fichier `src/DataFixtures/AppFixtures.php`) ou inscrivez-vous en tant que nouvel utilisateur.

## Production (Docker)

La prod tourne sur un VPS dans `/var/www/greengoodies`, orchestrée par **`compose.prod.yaml`** (versionné, sans aucun secret) : MySQL 8.4, PHP 8.5-FPM, nginx en HTTP seulement. Le trafic arrive par **Caddy**, point d'entrée partagé du VPS (projet `edge` dans `~/edge` : ports 80/443, certificats Let's Encrypt obtenus et renouvelés automatiquement), qui le transmet à nginx par le réseau Docker `edge`. Les secrets sont dans `.env.local` sur le serveur (non versionné).

> **Quel fichier Compose ?**
> - **Local / dev** → `compose.yaml` + `compose.override.yaml` (non versionnés, chargés automatiquement par `docker compose`).
> - **Production** → `compose.prod.yaml`, toujours ciblé explicitement : `docker compose -f compose.prod.yaml …` (c'est ce que fait `deploy.sh`).

### Premier déploiement (une seule fois)

Prérequis : le réseau Docker `edge` et Caddy démarrés, avec un bloc `greengoodies.eu` dans le `Caddyfile` du projet `edge`.

```bash
git clone https://github.com/Christophe-THEVENET/symfony_greengoodies.git /var/www/greengoodies
cd /var/www/greengoodies
cp .env .env.local   # puis renseigner les valeurs ci-dessous
```

```dotenv
APP_ENV=prod
APP_SECRET=xxxxxxxxxxx
MYSQL_DATABASE=xxxxxxxxxxxx
MYSQL_USER=xxxxxxxxxxxx
MYSQL_PASSWORD=xxxxxxxxxxxx
MYSQL_ROOT_PASSWORD=xxxxxxxxxxxx
DATABASE_URL=mysql://xxxxxxx:xxxxxxx@db:3306/xxxxxxxx?serverVersion=8.4&charset=utf8mb4
JWT_PASSPHRASE=xxxxxxxxxxxx   # ex. openssl rand -hex 32
STRIPE_PUBLIC_KEY=pk_live_xxx
STRIPE_SECRET_KEY=sk_live_xxx
```

```bash
sudo ./deploy.sh
docker compose -f compose.prod.yaml exec -u www-data php php bin/console lexik:jwt:generate-keypair --env=prod
```

### Déploiements suivants

```bash
cd /var/www/greengoodies && sudo ./deploy.sh
```

Le script `deploy.sh` :

1. sauvegarde la base (`/var/backups/greengoodies/db-<date>.sql.gz`) ;
2. récupère la dernière version (`git pull --ff-only origin master`) ;
3. met à jour les images Docker et reconstruit PHP : **chaque déploiement embarque les derniers correctifs de sécurité** de PHP, MySQL et nginx ;
4. relance les conteneurs (attend que MySQL soit prêt) ;
5. installe les dépendances, joue les migrations, compile les assets, vide le cache ;
6. vérifie que les pages clés répondent en 200 et affiche, en cas de problème, la version précédente et la sauvegarde à restaurer.
