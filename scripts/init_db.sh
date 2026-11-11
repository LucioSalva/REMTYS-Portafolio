#!/bin/bash
# ============================================================
# REMTYS — Inicialización de una base de datos local limpia
#
# Orden: schema.sql -> seeds.sql -> migraciones 001..012 ->
#        seeds_cp_demo.sql -> baseline del runner de migraciones.
#
# Uso:
#   DB_PASS=... DEMO_PASSWORD=... bash scripts/init_db.sh
#
# Variables (con valores por defecto):
#   PGHOST=localhost PGPORT=5432 PGUSER=postgres  (superusuario; libpq)
#   DB_NAME=remtys2_db
#   DB_PASS        contraseña para el rol de aplicación remtys_app (requerida)
#   DEMO_PASSWORD  contraseña de los usuarios demo *@ejemplo.com (requerida)
#   PGBIN          directorio de binarios de PostgreSQL (opcional)
# ============================================================
set -euo pipefail

: "${DB_PASS:?Define DB_PASS (contraseña del rol remtys_app)}"
: "${DEMO_PASSWORD:?Define DEMO_PASSWORD (contraseña de usuarios demo)}"
export PGHOST="${PGHOST:-localhost}" PGPORT="${PGPORT:-5432}" PGUSER="${PGUSER:-postgres}"
DB_NAME="${DB_NAME:-remtys2_db}"
PGBIN="${PGBIN:-}"
PSQL="${PGBIN:+$PGBIN/}psql"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
run() { "$PSQL" -v ON_ERROR_STOP=1 -q -d "$DB_NAME" "$@"; }

echo "[init_db] Creando base $DB_NAME (si no existe)..."
"$PSQL" -v ON_ERROR_STOP=1 -q -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '$DB_NAME'" | grep -q 1 \
    || "$PSQL" -v ON_ERROR_STOP=1 -q -d postgres -c "CREATE DATABASE \"$DB_NAME\" ENCODING 'UTF8'"

echo "[init_db] Esquema base..."
run -f "$ROOT/database/schema.sql" 2>/dev/null

echo "[init_db] Datos demo..."
PGOPTIONS="-c remtys.demo_password=$DEMO_PASSWORD" run -f "$ROOT/database/seeds.sql"

for m in "$ROOT"/database/migrations/[0-9][0-9][0-9]_*.sql; do
    echo "[init_db] Migración $(basename "$m")..."
    run -v db_pass="$DB_PASS" -f "$m" >/dev/null
done

echo "[init_db] Catálogo demo de códigos postales..."
run -f "$ROOT/database/seeds_cp_demo.sql"

if command -v php >/dev/null 2>&1; then
    echo "[init_db] Registrando migraciones como aplicadas (baseline)..."
    DB_HOST="$PGHOST" DB_PORT="$PGPORT" DB_USER="$PGUSER" DB_PASS="${PGPASSWORD:-$DB_PASS}" DB_NAME="$DB_NAME" \
        APP_ENV="${APP_ENV:-local}" php "$ROOT/database/migrate.php" baseline
fi

echo "[init_db] Listo."
