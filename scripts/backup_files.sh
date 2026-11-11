#!/bin/bash
# REMTYS — Backup de archivos (uploads + config)
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="${PROJECT_DIR}/backups/files"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/remtys_files_${TIMESTAMP}.tar.gz"
LOG_FILE="${BACKUP_DIR}/backup.log"

mkdir -p "$BACKUP_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=== Inicio backup archivos ==="

# Respaldar uploads y configuracion
cd "$PROJECT_DIR"
tar czf "$BACKUP_FILE" \
    storage/uploads/ \
    config/database.php \
    config/mail.php \
    .htaccess \
    public/.htaccess \
    2>>"$LOG_FILE"

FILE_SIZE=$(stat -c%s "$BACKUP_FILE" 2>/dev/null || wc -c < "$BACKUP_FILE")
log "OK: Backup archivos creado: $BACKUP_FILE ($FILE_SIZE bytes)"

# Verificar integridad
if tar tzf "$BACKUP_FILE" > /dev/null 2>&1; then
    log "OK: Integridad tar.gz verificada"
else
    log "ERROR: Archivo tar.gz corrupto"
    exit 1
fi

# Retencion: ultimos 30
TOTAL=$(ls -1 "$BACKUP_DIR"/remtys_files_*.tar.gz 2>/dev/null | wc -l)
if [ "$TOTAL" -gt 30 ]; then
    ELIMINAR=$((TOTAL - 30))
    ls -1t "$BACKUP_DIR"/remtys_files_*.tar.gz | tail -n "$ELIMINAR" | xargs rm -f
    log "Limpieza: $ELIMINAR backups antiguos eliminados"
fi

log "=== Backup archivos completado ==="
