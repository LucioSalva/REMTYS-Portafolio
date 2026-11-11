#!/bin/bash
# ============================================================
# REMTYS — Production Deploy Script
# Validates environment, builds images, starts services
# ============================================================
set -euo pipefail

echo "=== REMTYS Deploy Script ==="
echo ""

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_DIR"

# -----------------------------------------------------------
# 1. Check .env exists
# -----------------------------------------------------------
if [ ! -f .env ]; then
    echo "ERROR: .env file not found."
    echo "  Copy .env.example and configure it:"
    echo "  cp .env.example .env"
    exit 1
fi

# Source .env to validate variables
set -a
source .env
set +a

# -----------------------------------------------------------
# 2. Validate required environment variables
# -----------------------------------------------------------
REQUIRED_VARS="DB_HOST DB_PORT DB_NAME DB_USER DB_PASS APP_URL APP_ENV"
MISSING=0
for var in $REQUIRED_VARS; do
    if [ -z "${!var:-}" ]; then
        echo "ERROR: Required variable $var is not set in .env"
        MISSING=1
    fi
done
if [ "$MISSING" -eq 1 ]; then
    exit 1
fi

# -----------------------------------------------------------
# 3. Warn if not production
# -----------------------------------------------------------
if [ "$APP_ENV" != "production" ]; then
    echo "WARNING: APP_ENV is '$APP_ENV', not 'production'"
    echo ""
fi

# -----------------------------------------------------------
# 4. Check TLS certificates
# -----------------------------------------------------------
if [ ! -f docker/certs/cert.pem ] || [ ! -f docker/certs/key.pem ]; then
    echo "WARNING: TLS certificates not found in docker/certs/"
    echo "  Run: bash scripts/generate-certs.sh (for self-signed)"
    echo "  Or place your real certificates there."
    echo ""
    read -p "Continue without TLS? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# -----------------------------------------------------------
# 5. Build and start
# -----------------------------------------------------------
echo "Building and starting REMTYS..."
docker compose -f docker-compose.prod.yml up --build -d

# -----------------------------------------------------------
# 6. Wait for database health
# -----------------------------------------------------------
echo "Waiting for database to be ready..."
RETRIES=30
until docker compose -f docker-compose.prod.yml exec -T db pg_isready -U "$DB_USER" -d "$DB_NAME" > /dev/null 2>&1; do
    RETRIES=$((RETRIES - 1))
    if [ "$RETRIES" -le 0 ]; then
        echo "ERROR: Database did not become ready in time."
        echo "Check logs: docker compose -f docker-compose.prod.yml logs db"
        exit 1
    fi
    sleep 2
done
echo "Database is ready."

# -----------------------------------------------------------
# 7. Run schema migration (idempotent — init scripts only
#    run on first volume creation, this covers restarts)
# -----------------------------------------------------------
echo "Running schema migration..."
docker compose -f docker-compose.prod.yml exec -T db \
    psql -U "$DB_USER" -d "$DB_NAME" \
    -f /docker-entrypoint-initdb.d/01-schema.sql 2>/dev/null \
    || echo "  Schema already exists (OK)"

# -----------------------------------------------------------
# 8. Apply production hardening migration (idempotent)
# -----------------------------------------------------------
if [ -f database/migrations/002_production_hardening.sql ]; then
    echo "Applying production hardening migration..."
    docker compose -f docker-compose.prod.yml cp \
        database/migrations/002_production_hardening.sql db:/tmp/002.sql
    docker compose -f docker-compose.prod.yml exec -T db \
        psql -U postgres -d "$DB_NAME" -f /tmp/002.sql \
        || echo "  WARNING: Some hardening constraints may already exist"
else
    echo "  No hardening migration found (skipping)"
fi

# -----------------------------------------------------------
# 8b. Create/update application role with least privileges
# -----------------------------------------------------------
if [ -f database/migrations/003_create_app_role.sql ]; then
    echo "Creating/updating application database role..."
    docker compose -f docker-compose.prod.yml cp \
        database/migrations/003_create_app_role.sql db:/tmp/003.sql
    docker compose -f docker-compose.prod.yml exec -T db \
        psql -U postgres -d "$DB_NAME" -v db_pass="$DB_PASS" -f /tmp/003.sql \
        || echo "  WARNING: Role creation may have failed — check logs"
else
    echo "  No role migration found (skipping)"
fi

# -----------------------------------------------------------
# 8c. Apply RLS preparation (idempotent, policies only)
# -----------------------------------------------------------
if [ -f database/migrations/004_rls_preparation.sql ]; then
    echo "Applying RLS preparation policies..."
    docker compose -f docker-compose.prod.yml cp \
        database/migrations/004_rls_preparation.sql db:/tmp/004.sql
    docker compose -f docker-compose.prod.yml exec -T db \
        psql -U postgres -d "$DB_NAME" -f /tmp/004.sql \
        || echo "  WARNING: Some RLS policies may already exist"
else
    echo "  No RLS migration found (skipping)"
fi

# -----------------------------------------------------------
# 9. Final status
# -----------------------------------------------------------
echo ""
echo "=== REMTYS deployed successfully ==="
echo ""
docker compose -f docker-compose.prod.yml ps
echo ""
echo "Access: $APP_URL"
echo ""
echo "Useful commands:"
echo "  Logs:    docker compose -f docker-compose.prod.yml logs -f"
echo "  Stop:    docker compose -f docker-compose.prod.yml down"
echo "  Restart: docker compose -f docker-compose.prod.yml restart"
