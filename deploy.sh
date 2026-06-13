#!/bin/bash
set -euo pipefail
cd /var/www/greengoodies

echo "=== Normalisation config git (évite le bug core.fileMode) ==="
git config core.fileMode false

echo "=== Correction des permissions git ==="
sudo chown -R "$USER:$USER" .

echo "=== Pull des modifications ==="
git pull origin master

echo "=== Correction des permissions ==="
docker exec -i greengoodies-php-1 chown -R www-data:www-data /var/www/html

echo "=== Dépendances Composer ==="
docker exec -i -u www-data greengoodies-php-1 composer install --no-dev --optimize-autoloader --no-interaction

echo "=== Migrations base de données ==="
docker exec -i -u www-data greengoodies-php-1 php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "=== Compilation des assets ==="
docker exec -i -u www-data greengoodies-php-1 php bin/console sass:build --env=prod
docker exec -i -u www-data greengoodies-php-1 php bin/console asset-map:compile --env=prod

echo "=== Vidage du cache ==="
docker exec -i -u www-data greengoodies-php-1 php bin/console cache:clear --env=prod

echo "=== Déploiement terminé ✅ ==="
