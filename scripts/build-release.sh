#!/bin/bash
# Costruisce il pacchetto di release per l'installazione su hosting condiviso (solo FTP, senza SSH/Composer):
# dist/niles.zip + dist/niles.zip.sha256. È lo zip che scarica scripts/niles-installer.php.
#
# Contenuto: i soli file versionati (niente .env, storage/app, node_modules), dipendenze Composer di produzione,
# asset già compilati, .htaccess di protezione. Non contiene .env né dati: lo stesso pacchetto serve per la
# prima installazione e per gli aggiornamenti.
#
# Requisiti: bash, git, php 8.4 (con estensione zip), composer, node/npm (salvo SKIP_ASSETS=1, che riusa public/build).
# Uso: scripts/build-release.sh   (dalla radice del repository, anche in locale; lo lancia il workflow di release)
set -euo pipefail

cd "$(dirname "$0")/.."
OUT="${NILES_BUILD_DIR:-dist}"   # cartella di lavoro e di destinazione (ricreata a ogni esecuzione)
STAGE="$OUT/niles"
VERSIONE=$(grep -oP "'version'\s*=>\s*'\K[^']+" config/app.php | head -n1)

# Esclusi dal pacchetto: sviluppo, test, documentazione, strumenti per i server con SSH.
ESCLUSI='^(\.github/|tests/|docs/|scripts/|phpunit\.xml$|package(-lock)?\.json$|vite\.config\.js$|\.editorconfig$|\.gitattributes$|CONTRIBUTING\.md$|SECURITY\.md$)'

echo "==> NILES $VERSIONE: preparazione in $STAGE"
rm -rf "$OUT"
mkdir -p "$STAGE"
git ls-files -z | grep -zvE "$ESCLUSI" | tar --null -T - -cf - | tar -xf - -C "$STAGE"

echo "==> Dipendenze di produzione"
composer install --working-dir="$STAGE" --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist

echo "==> Asset"
if [ "${SKIP_ASSETS:-}" != "1" ]; then
    npm ci
    npm run build
fi
cp -R public/build "$STAGE/public/build"

# Cartelle scrivibili che il framework si aspetta (git non conserva quelle vuote).
mkdir -p "$STAGE"/storage/{logs,app/public,app/private} "$STAGE"/storage/framework/{cache/data,sessions,views} "$STAGE/bootstrap/cache"

echo "==> Protezioni .htaccess"
# Radice: il sito è servito da public/. Il file dell'installer passa senza riscrittura finché esiste.
cat > "$STAGE/.htaccess" <<'HTACCESS'
# NILES: la radice del sito è public/ (hosting condiviso senza possibilità di cambiare la radice web).
Options -Indexes

# File nascosti (.env, .git, ...) mai serviti.
<FilesMatch "^\.">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Installer: raggiungibile finché non si cancella da solo a fine installazione.
    RewriteRule ^niles-installer\.php$ - [L]

    # Tutto il resto passa da public/ (i file della radice non sono mai raggiungibili direttamente).
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
HTACCESS

# Seconda barriera: le cartelle con codice o dati sono negate anche se il rewrite venisse meno.
# ⚠️ Mai una cartella con lo stesso nome di una voce di public/ (es. vendor/): Apache controlla i permessi sul
# percorso fisico PRIMA del rewrite, quindi negherebbe anche gli asset pubblici (public/vendor/...). Vedi il test
# NilesInstallerTest. (storage/app/public non è negata: è la destinazione del collegamento public/storage.)
for d in app bootstrap config database lang resources routes storage/framework storage/logs storage/app/private; do
    cat > "$STAGE/$d/.htaccess" <<'HTACCESS'
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
HTACCESS
done

echo "==> Compressione"
cat > "$OUT/zip.php" <<'PHP'
<?php
// Zip con PHP (ZipArchive) per non dipendere dal comando `zip`, assente su alcuni ambienti (es. Git Bash).
$z = new ZipArchive();
$z->open($argv[1], ZipArchive::CREATE | ZipArchive::OVERWRITE);
$base = rtrim(str_replace('\\', '/', realpath($argv[2])), '/') . '/';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $f) {
    $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen($base));
    $f->isDir() ? $z->addEmptyDir($rel) : $z->addFile($f->getPathname(), $rel);
}
$z->close();
PHP
php "$OUT/zip.php" "$OUT/niles.zip" "$STAGE"
rm -rf "$STAGE" "$OUT/zip.php"

(cd "$OUT" && sha256sum niles.zip > niles.zip.sha256)
echo "==> Fatto: $OUT/niles.zip ($(du -h "$OUT/niles.zip" | cut -f1)) — $(cut -d' ' -f1 "$OUT/niles.zip.sha256")"
