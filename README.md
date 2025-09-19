# GreenGoodies

l'appli est en ligne ici : https://greengoodies.space


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

-   PHP 8.2.0 ou plus;
-   Symfony 7.3 ou plus;
-   Composer;
-   MySQL/MariaDB;
-   Docker (optionnel, pour une installation en prod ou en local avec environnement dockerisé);


## Installation en local avec environnement LAMP

`git clone https://github.com/Christophe-THEVENET/symfony_greengoodies.git`

`cd symfony_greengoodies/`

`composer install`

`cp .env .env.local` > configurer le DNS de la base de données

`php bin/console cache:clear`

`php bin/console doctrine:database:create`

`php bin/console doctrine:migrations:migrate`

`php bin/console doctrine:fixtures:load`

`php bin/console asset-map:compile`

## Installation en local ou prod avec environnement Docker

`git clone https://github.com/Christophe-THEVENET/symfony_greengoodies.git`

`cd symfony_greengoodies/`

`cp .env .env.prod`

>exemple de configuration pour .env.prod (pour utiliser l'API en prod, adapter les chemins des clés JWT) :
JWT_SECRET_KEY=/path/to/your/prod/private.pem   
JWT_PUBLIC_KEY=/path/to/your/prod/public.pem  
JWT_PASSPHRASE=xxxxxxxxxxxxxxxxxxxx  
###< lexik/jwt-authentication-bundle ###  
APP_ENV=prod  
APP_SECRET=xxxxxxxxxxx  
MYSQL_DATABASE=xxxxxxxxxxxx  
MYSQL_USER=xxxxxxxxxxxx  
MYSQL_PASSWORD=xxxxxxxxxxxx  
MYSQL_ROOT_PASSWORD=xxxxxxxxxxxx  
DATABASE_URL=mysql://xxxxxxx:xxxxxxx@db:3306/xxxxxxxx?serverVersion=5.7.44&charset=utf8mb4

`docker compose up -d --build`

installer les dépendances :

`docker compose exec php bash -lc "composer install --no-dev --optimize-autoloader"`

base de données et fixtures :

`docker compose exec php bash -lc "php bin/console doctrine:database:create --if-not-exists --env=prod"`

`docker compose exec php bash -lc "php bin/console doctrine:migrations:migrate --no-interaction --env=prod"`

`docker compose exec php bash -lc "php bin/console doctrine:fixtures:load --no-interaction --env=prod"`

droits sur les dossiers var et cache :

`docker compose exec php bash -lc "mkdir -p var/cache var/log var/sass && chown -R www-data:www-data var && chmod -R u+rwX,g+rwX,o-rwx var"`

`docker compose exec php bash -lc "php bin/console cache:clear --env=prod --no-debug && php bin/console cache:warmup --env=prod"`

compiler les assets :

`docker compose exec --user www-data php bash -lc "php bin/console asset-map:compile --env=prod --no-debug || true; php bin/console sass:build --env=prod --no-debug || true"`

## Utilisation

url local: localhost:8000
url prod: https://greengoodies.space

Connectez-vous avec les comptes créés dans les fixtures (voir les identifiants dans le fichier `src/DataFixtures/AppFixtures.php`) ou inscrivez-vous en tant que nouvel utilisateur.
