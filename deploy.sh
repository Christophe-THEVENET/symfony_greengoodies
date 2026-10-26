#!/bin/bash
# Déploiement de GreenGoodies sur le VPS.
#   cd /var/www/greengoodies && sudo ./deploy.sh
#
# 1. sauvegarde la base   2. récupère le code   3. met à jour les images
#    (correctifs de sécurité PHP / MySQL / nginx)   4. relance les conteneurs
# 5. dépendances, migrations, assets, cache   6. vérifie que le site répond
#
# Retour arrière : git checkout <version précédente affichée à la fin> puis
# sudo ./deploy.sh ; pour la base, réimporter la sauvegarde affichée à la fin.
set -euo pipefail

APP_DIR="$(cd "$(dirname "$(readlink -f "$0")")" && pwd)"
BACKUP_DIR=/var/backups/greengoodies
DOMAIN=greengoodies.eu
# www-data du conteneur PHP (uid 1000) / groupe www-data (gid 33)
APP_OWNER=1000:33
COMPOSE=(docker compose -f "$APP_DIR/compose.prod.yaml")

step() { printf '\n=== %s ===\n' "$*"; }
git_() { git -c safe.directory="$APP_DIR" "$@"; }
php_exec() { "${COMPOSE[@]}" exec -T -u www-data php "$@"; }
console() { php_exec php bin/console "$@" --env=prod --no-interaction; }

[ "$(id -u)" -eq 0 ] || { echo "À lancer avec sudo : sudo ./deploy.sh" >&2; exit 1; }
cd "$APP_DIR"

step "Sauvegarde de la base"
install -d -m 700 "$BACKUP_DIR"
DUMP="(aucune : base arrêtée)"
if "${COMPOSE[@]}" ps --status running --services | grep -qx db; then
    DUMP="$BACKUP_DIR/db-$(date +%Y%m%d-%H%M%S).sql.gz"
    # Variables développées DANS le conteneur db (mot de passe jamais sur l'hôte)
    # shellcheck disable=SC2016
    "${COMPOSE[@]}" exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump -uroot \
        --single-transaction --routines --triggers --databases "$MYSQL_DATABASE"' | gzip > "$DUMP"
    zcat "$DUMP" | tail -n 1 | grep -q 'Dump completed' || { echo "Sauvegarde incomplète : arrêt" >&2; exit 1; }
    echo "→ $DUMP"
fi

step "Récupération du code"
PREVIOUS=$(git_ rev-parse --short HEAD)
git_ config core.fileMode false
git_ pull --ff-only origin master
echo "→ $PREVIOUS → $(git_ rev-parse --short HEAD)"

step "Images à jour (correctifs de sécurité)"
"${COMPOSE[@]}" pull db nginx
"${COMPOSE[@]}" build --pull php

step "Redémarrage des conteneurs"
"${COMPOSE[@]}" up -d --wait --remove-orphans
# Redémarré (un simple reload ne suffit pas) : nginx résout l'adresse de PHP au démarrage, et git
# remplace default.conf par un nouveau fichier que le conteneur ne voit qu'après remontage
"${COMPOSE[@]}" restart nginx

step "Droits des fichiers"
chown -R "$APP_OWNER" "$APP_DIR"
chmod 640 .env.local

step "Application"
php_exec composer install --no-dev --optimize-autoloader --no-interaction
console doctrine:migrations:migrate --allow-no-migration
console sass:build
console asset-map:compile
console cache:clear

step "Vérification"
FAILED=0
for path in / /connexion /panier; do
    code=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN$path")
    echo "$path → $code"
    [ "$code" = 200 ] || FAILED=1
done

step "Nettoyage des images obsolètes"
docker image prune -f

echo
echo "Version précédente : $PREVIOUS"
echo "Sauvegarde base    : $DUMP"
if [ "$FAILED" -ne 0 ]; then
    echo "⚠️  Le site ne répond pas correctement : voir le retour arrière en tête de ce script." >&2
    exit 1
fi
echo "=== Déploiement terminé ✅ ==="
