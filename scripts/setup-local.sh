#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
for required in php composer node npm; do command -v "$required" >/dev/null || { echo "Manquant : $required" >&2; exit 1; }; done
php -r 'if (PHP_VERSION_ID < 80400) {fwrite(STDERR, "PHP 8.4+ requis pour les tests verrouillés (Laravel : 8.3+).\n");exit(1);}'
php -r 'if (!extension_loaded("gd")) {fwrite(STDERR, "Extension PHP GD requise pour importer les plans et exécuter les tests. Activez GD pour votre interpréteur PHP CLI, puis relancez ce script.\n");exit(1);}'
[[ "$(node -p 'process.versions.node.split(".")[0]')" == 24 ]] || { echo 'Node 24 requis (.nvmrc).' >&2; exit 1; }
if [[ ! -f .env ]]; then cp .env.example .env; fi
if ! grep -Eq '^APP_ENV=local$' .env; then echo 'Ce script est réservé à APP_ENV=local.' >&2; exit 1; fi
composer install --no-interaction --prefer-dist
php artisan config:clear
if ! grep -Eq '^APP_KEY=.+$' .env; then php artisan key:generate; fi
if [[ ! -f database/database.sqlite ]]; then touch database/database.sqlite; fi
php artisan migrate
php artisan db:seed
php artisan filament:assets
if [[ ! -e public/storage && ! -L public/storage ]]; then php artisan storage:link; fi
npm ci
npm run build
php artisan test
printf '\nInstallation prête.\nAdmin : php artisan events:admin votre@email.pt\nLancement : php artisan serve --host=127.0.0.1 --port=8890\n'
