#!/bin/bash
# REMTYS — Backup de base de datos PostgreSQL
# Uso: bash scripts/backup_db.sh

set -euo pipefail

# Directorio de binarios de PostgreSQL (opcional). Vacío = usar los del PATH.
PGBIN="${PGBIN:-}"
DB_NAME="${DB_NAME:-remtys2_db}"
DB_USER="${DB_USER:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

BACKUP_DIR="$(cd "$(dirname "$0")/.." && pwd)/backups/db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/remtys_db_${TIMESTAMP}.sql.gz"
LOG_FILE="${BACKUP_DIR}/backup.log"

mkdir -p "$BACKUP_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== Inicio backup BD: $DB_NAME ==="

# Ejecutar pg_dump con compresion
if "${PGBIN:+$PGBIN/}pg_dump" -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
    --no-owner --no-privileges --clean --if-exists \
    2>>"$LOG_FILE" | gzip > "$BACKUP_FILE"; then

    # Verificar que el archivo existe y tiene tamano razonable
    FILE_SIZE=$(stat -c%s "$BACKUP_FILE" 2>/dev/null || wc -c < "$BACKUP_FILE")

    if [ "$FILE_SIZE" -gt 1000 ]; then
        log "OK: Backup creado: $BACKUP_FILE ($FILE_SIZE bytes)"

        # Verificar integridad descomprimiendo
        if gzip -t "$BACKUP_FILE" 2>/dev/null; then
            log "OK: Integridad gzip verificada"
        else
            log "ERROR: Archivo gzip corrupto"
            exit 1
        fi
    else
        log "ERROR: Backup demasiado pequeno ($FILE_SIZE bytes) — posible fallo"
        exit 1
    fi
else
    log "ERROR: pg_dump fallo"
    exit 1
fi

# Retencion: mantener ultimos 30 backups
TOTAL=$(ls -1 "$BACKUP_DIR"/remtys_db_*.sql.gz 2>/dev/null | wc -l)
if [ "$TOTAL" -gt 30 ]; then
    ELIMINAR=$((TOTAL - 30))
    ls -1t "$BACKUP_DIR"/remtys_db_*.sql.gz | tail -n "$ELIMINAR" | xargs rm -f
    log "Limpieza: $ELIMINAR backups antiguos eliminados (retencion: 30)"
fi

log "=== Backup BD completado ==="
