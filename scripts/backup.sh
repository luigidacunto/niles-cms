#!/usr/bin/env bash
# =============================================================================
# backup.sh  —  Esegui SUL SERVER prima di pull rischiosi
#
# Crea nella home dell'utente corrente (la versione del pannello è nel nome dei file, così restano
# riconoscibili anche scaricati singolarmente, es. db_v0.16.2.sql.gz):
#   ~/niles-backups/YYYYMMDD_HHMMSS/db_vX.Y.Z.sql.gz      ← dump completo MariaDB/MySQL
#   ~/niles-backups/YYYYMMDD_HHMMSS/files_vX.Y.Z.tar.gz   ← archivio della directory del deploy (saltato con --only-db)
#
# Uso (path del deploy = directory padre di scripts/, rilevato automaticamente):
#   bash /percorso/del/deploy/scripts/backup.sh
#   bash /percorso/del/deploy/scripts/backup.sh --only-db   # solo il dump database, niente file
#
# Ripristino DB:
#   gunzip -c ~/niles-backups/YYYYMMDD_HHMMSS/db_vX.Y.Z.sql.gz | mysql -h HOST -u USER -p DBNAME
#
# Requisiti (Ubuntu/Debian o simile): bash, mysqldump (mysql-client/mariadb-client), gzip, tar; sudo solo
# se il deploy appartiene a un altro utente.
#
# Ripristino file (con sudo solo se APP_DIR appartiene a un altro utente, es. www-data):
#   sudo tar -xzf ~/niles-backups/YYYYMMDD_HHMMSS/files_vX.Y.Z.tar.gz -C /
# =============================================================================

set -euo pipefail

ONLY_DB=false
[[ "${1:-}" == "--only-db" ]] && ONLY_DB=true

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Versione del pannello (config/app.php → 'version') nel nome dei file di backup, per riconoscere a
# colpo d'occhio a quale release corrisponde un file anche scaricato da solo. "NA" se non leggibile.
APP_VERSION=$(grep -oE "'version'\s*=>\s*'[^']+'" "$APP_DIR/config/app.php" 2>/dev/null | grep -oE "[0-9][^']*" | head -1)
VTAG="_v${APP_VERSION:-NA}"
BACKUP_DIR="$HOME/niles-backups/${TIMESTAMP}"

_env() { grep "^$1=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'"; }

DB_HOST=$(_env DB_HOST);     DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(_env DB_PORT);     DB_PORT=${DB_PORT:-3306}
DB_NAME=$(_env DB_DATABASE)
DB_USER=$(_env DB_USERNAME)
DB_PASS=$(_env DB_PASSWORD)

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
    echo "ERRORE: DB_DATABASE o DB_USERNAME non trovati in $ENV_FILE"
    exit 1
fi

mkdir -p "$BACKUP_DIR"

echo ""
echo "┌─────────────────────────────────────────────────────┐"
echo "│  NILES — Backup  │  $TIMESTAMP  │  v${APP_VERSION:-NA}  │"
echo "└─────────────────────────────────────────────────────┘"
echo ""

STEP_TOTAL=2
$ONLY_DB && STEP_TOTAL=1

# ── 1. Dump database ──────────────────────────────────────────────────────────
echo "→ [1/$STEP_TOTAL] Dump database ($DB_USER@$DB_HOST:$DB_PORT/$DB_NAME)..."

MYSQL_PWD="$DB_PASS" mysqldump \
    -h "$DB_HOST" -P "$DB_PORT" \
    -u "$DB_USER" \
    --single-transaction --routines --triggers \
    "$DB_NAME" \
    | gzip > "$BACKUP_DIR/db${VTAG}.sql.gz"

DB_SIZE=$(du -sh "$BACKUP_DIR/db${VTAG}.sql.gz" | cut -f1)
echo "  ✓ db${VTAG}.sql.gz  ($DB_SIZE)"

# ── 2. Archivio file (saltato con --only-db) ───────────────────────────────────
if ! $ONLY_DB; then
    echo "→ [2/2] Archivio file ($APP_DIR)..."

    TAR_ARGS=(
        --exclude="$APP_DIR/.git"
        --exclude="$APP_DIR/node_modules"
        --exclude="$APP_DIR/vendor"
        -czf "$BACKUP_DIR/files${VTAG}.tar.gz"
        -C / "${APP_DIR#/}"
    )

    # ponytail: prova senza sudo (caso comune: deploy nella home utente); sudo
    # serve solo se il deploy appartiene a un altro utente (es. www-data)
    if ! tar "${TAR_ARGS[@]}" 2>/dev/null; then
        sudo tar "${TAR_ARGS[@]}"
        sudo chown "$USER:$USER" "$BACKUP_DIR/files${VTAG}.tar.gz"
    fi

    FILES_SIZE=$(du -sh "$BACKUP_DIR/files${VTAG}.tar.gz" | cut -f1)
    echo "  ✓ files${VTAG}.tar.gz  ($FILES_SIZE)"
fi

# ── Riepilogo ─────────────────────────────────────────────────────────────────
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo ""
echo "  Backup salvato in: $BACKUP_DIR  ($TOTAL_SIZE totali)"
echo ""
echo "  Ripristino DB:"
echo "    gunzip -c $BACKUP_DIR/db${VTAG}.sql.gz | mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME"
echo ""
if ! $ONLY_DB; then
    echo "  Ripristino file (sudo obbligatorio per ownership www-data):"
    echo "    sudo tar -xzf $BACKUP_DIR/files${VTAG}.tar.gz -C /"
    echo ""
fi

# ── Pulizia automatica: mantieni solo gli ultimi 10 backup ────────────────────
BACKUP_ROOT="$HOME/niles-backups"
BACKUP_COUNT=$(ls -1 "$BACKUP_ROOT" | wc -l)
if [[ $BACKUP_COUNT -gt 10 ]]; then
    echo "  Pulizia backup vecchi (mantengo gli ultimi 10)..."
    ls -1t "$BACKUP_ROOT" | tail -n +11 | while read -r old; do
        rm -rf "$BACKUP_ROOT/$old"
        echo "  → eliminato: $old"
    done
fi

echo "  Fatto."
echo ""
