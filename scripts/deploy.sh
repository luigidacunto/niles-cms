#!/bin/bash
# Requisiti sul server (Ubuntu/Debian o simile): bash, git, php 8.4 + estensioni Laravel, composer, node/npm.
# Presuppone Node/npm disponibili sul server (VM con SSH): sull'hosting condiviso economico senza Node,
# la build va fatta in locale e trasferita a mano, questo script non si applica in quel caso.
set -e

QUICK=false
DEV=false
DEV_EXPLICIT=false
for arg in "$@"; do
    [[ "$arg" == "--quick" ]] && QUICK=true
    [[ "$arg" == "--dev" ]] && DEV=true && DEV_EXPLICIT=true
done

# Senza --dev esplicito, il branch corrente decide: "dev" => modalità dev, altrimenti
# (main o qualsiasi altro branch) => produzione. --dev resta disponibile per forzare la
# modalità dev anche su un branch diverso (es. un branch di prova temporaneo).
if [ "$DEV_EXPLICIT" = false ] && [ "$(git rev-parse --abbrev-ref HEAD 2>/dev/null)" = "dev" ]; then
    DEV=true
fi

# Su HestiaCP la versione PHP si sceglie per singolo dominio/utente, non a livello di sistema:
# il binario composer condiviso può avere lo shebang fissato sulla versione di sistema (es. 8.3)
# e fallire anche se `php -v` mostra 8.4. Forza l'interprete se
# php8.4 esiste, senza toccare lo shebang di composer (condiviso da altri progetti sul server).
# `type -P` (non `command -v`) perché deve risolvere il binario reale nel PATH, ignorando la
# funzione `composer` sotto — se no si richiamerebbe ricorsivamente invece di trovare il binario.
COMPOSER_BIN="$(type -P composer)"
composer() {
    if command -v php8.4 >/dev/null 2>&1; then
        php8.4 "$COMPOSER_BIN" "$@"
    else
        "$COMPOSER_BIN" "$@"
    fi
}

# Nessun queue worker/supervisor da riavviare: QUEUE_CONNECTION=database è usato in modo
# sincrono/opzionale. Se in futuro si introduce un worker
# persistente, aggiungere qui un passo di restart.

if [ "$QUICK" = true ]; then
    echo "==> [QUICK] Git pull"
    # npm può riscrivere package.json/package-lock.json come effetto collaterale di un
    # run precedente (es. autorizzazioni post-install) — questi file devono cambiare solo
    # via commit, mai per drift locale: scartato prima del pull.
    git checkout -- package.json package-lock.json
    git pull

    echo "==> [QUICK] Cache clear + ottimizzazione"
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo ""
    echo "Deploy rapido completato."
    exit 0
fi

echo "==> [1/6] Git pull"
git checkout -- package.json package-lock.json
git pull

echo "==> [2/6] Composer"
if [ "$DEV" = true ]; then
    # Niente --no-dev sulla VM di sviluppo: servono i pacchetti di dev (test, pail, pint).
    composer install --optimize-autoloader
else
    composer install --no-dev --optimize-autoloader
fi

echo "==> [3/6] NPM build"
npm ci
npm run build

echo "==> [4/6] Migrazioni"
# --force: il deploy deve girare senza interazione, sia in prod (dove Laravel chiederebbe
# conferma per APP_ENV=production) sia in dev.
php artisan migrate --force

echo "==> [5/6] Cache"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [6/6] Storage link"
# Laravel scrive "ERROR ... link already exists" su stdout (non stderr), quindi
# 2>/dev/null non basta a nasconderlo: si controlla prima se esiste già.
[ -e public/storage ] || php artisan storage:link

echo ""
echo "Deploy completato."
