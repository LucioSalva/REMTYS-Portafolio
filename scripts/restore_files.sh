#!/bin/bash
# REMTYS — Restaurar archivos
# Uso: bash scripts/restore_files.sh [archivo.tar.gz]
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="${PROJECT_DIR}/backups/files"

if [ -n "${1:-}" ]; then
    BACKUP_FILE="$1"
else
    BACKUP_FILE=$(ls -1t "$BACKUP_DIR"/remtys_files_*.tar.gz 2>/dev/null | head -1)
    if [ -z "$BACKUP_FILE" ]; then
        echo "ERROR: No se encontraron backups en $BACKUP_DIR"
        exit 1
    fi
fi

echo "=== REMTYS — Restauracion de archivos ==="
echo "Archivo: $BACKUP_FILE"
echo ""
read -p "ADVERTENCIA: Esto sobreescribira archivos existentes. Continuar? (si/no): " CONFIRMAR
if [ "$CONFIRMAR" != "si" ]; then
    echo "Restauracion cancelada."
    exit 0
fi

echo "Verificando integridad..."
if ! tar tzf "$BACKUP_FILE" > /dev/null 2>&1; then
    echo "ERROR: Archivo tar.gz corrupto"
    exit 1
fi

echo "Restaurando archivos..."
cd "$PROJECT_DIR"
tar xzf "$BACKUP_FILE"

echo "OK: Archivos restaurados exitosamente"
echo "=== Restauracion completada ==="
