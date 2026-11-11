#!/bin/bash
# REMTYS — Backup maestro (BD + archivos)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_FILE="$(cd "$SCRIPT_DIR/.." && pwd)/backups/master.log"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "=========================================="
log "REMTYS BACKUP MAESTRO — INICIO"
log "=========================================="

ERRORES=0

# Backup BD
log "--- Ejecutando backup de base de datos ---"
if bash "$SCRIPT_DIR/backup_db.sh"; then
    log "OK: Backup BD exitoso"
else
    log "FALLO: Backup BD fallo"
    ERRORES=$((ERRORES + 1))
fi

# Backup archivos
log "--- Ejecutando backup de archivos ---"
if bash "$SCRIPT_DIR/backup_files.sh"; then
    log "OK: Backup archivos exitoso"
else
    log "FALLO: Backup archivos fallo"
    ERRORES=$((ERRORES + 1))
fi

if [ "$ERRORES" -eq 0 ]; then
    log "RESULTADO: Backup completo EXITOSO"
else
    log "RESULTADO: Backup con $ERRORES ERRORES — REQUIERE ATENCION"
fi

log "=========================================="
log "REMTYS BACKUP MAESTRO — FIN"
log "=========================================="

exit $ERRORES
