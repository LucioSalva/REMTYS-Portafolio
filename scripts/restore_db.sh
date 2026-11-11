#!/bin/bash
# REMTYS — Restaurar base de datos
# Uso: bash scripts/restore_db.sh [archivo.sql.gz]
set -euo pipefail

# Directorio de binarios de PostgreSQL (opcional). Vacío = usar los del PATH.
PGBIN="${PGBIN:-}"
DB_NAME="${DB_NAME:-remtys2_db}"
DB_USER="${DB_USER:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

BACKUP_DIR="$(cd "$(dirname "$0")/.." && pwd)/backups/db"

if [ -n "${1:-}" ]; then
    BACKUP_FILE="$1"
else
    # Usar el backup mas reciente
    BACKUP_FILE=$(ls -1t "$BACKUP_DIR"/remtys_db_*.sql.gz 2>/dev/null | head -1)
    if [ -z "$BACKUP_FILE" ]; then
        echo "ERROR: No se encontraron backups en $BACKUP_DIR"
        exit 1
    fi
fi

echo "=== REMTYS — Restauracion de BD ==="
echo "Archivo: $BACKUP_FILE"
echo "Base de datos destino: $DB_NAME"
echo ""
read -p "ADVERTENCIA: Esto reemplazara TODA la base de datos. Continuar? (si/no): " CONFIRMAR
if [ "$CONFIRMAR" != "si" ]; then
    echo "Restauracion cancelada."
    exit 0
fi

echo "Verificando integridad del backup..."
if ! gzip -t "$BACKUP_FILE" 2>/dev/null; then
    echo "ERROR: Archivo gzip corrupto"
    exit 1
fi

echo "Restaurando base de datos..."
if gunzip -c "$BACKUP_FILE" | "${PGBIN:+$PGBIN/}psql" -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -q 2>&1; then
    echo "OK: Base de datos restaurada exitosamente"
else
    echo "ERROR: Fallo en la restauracion"
    exit 1
fi

# Verificar restauracion
TABLAS=$("${PGBIN:+$PGBIN/}psql" -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public'")
echo "Verificacion: $TABLAS tablas en schema public"

echo "=== Restauracion completada ==="
