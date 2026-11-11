-- ============================================================
-- REMTYS — Migration 003: Create Application Role
-- Date: 2026-04-10
-- Purpose: Least-privilege database role for the PHP application
-- MUST be run by the postgres superuser
--
-- USAGE:
--   DB_PASS='your_strong_password_here'
--   psql -U postgres -d remtys2_db -v db_pass="$DB_PASS" \
--        -f database/migrations/003_create_app_role.sql
--
-- The password is passed via psql variable (:db_pass).
-- It is NEVER hardcoded in this file.
--
-- IDEMPOTENT: Safe to run multiple times.
--   - Creates the role if it does not exist
--   - Updates the password if it already exists
--   - Re-applies all grants (GRANT is idempotent in PostgreSQL)
-- ============================================================

-- ============================================================
-- 1. CREATE OR UPDATE ROLE
-- ============================================================

-- Validate that :db_pass was supplied. If psql was invoked
-- without -v db_pass="...", the literal string ':db_pass' would
-- be used, which we reject explicitly.
-- Note: psql variables are NOT interpolated inside dollar-quoted
-- DO blocks, so the value is passed through a session-local GUC.
\if :{?db_pass}
\else
DO $$ BEGIN
    RAISE EXCEPTION 'db_pass variable not set. Run with: psql -v db_pass="your_password" -f 003_create_app_role.sql';
END $$;
\endif

SELECT set_config('remtys.app_role_pass', :'db_pass', false);

DO $$
DECLARE
    v_pass text := current_setting('remtys.app_role_pass', true);
BEGIN
    IF v_pass IS NULL OR v_pass = '' THEN
        RAISE EXCEPTION 'db_pass variable is empty.';
    END IF;

    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'remtys_app') THEN
        EXECUTE format('CREATE ROLE remtys_app LOGIN PASSWORD %L', v_pass);
        RAISE NOTICE 'Role remtys_app created.';
    ELSE
        EXECUTE format('ALTER ROLE remtys_app PASSWORD %L', v_pass);
        RAISE NOTICE 'Role remtys_app already exists — password updated.';
    END IF;
END $$;

-- Clear the temporary GUC so the password does not linger in the session.
SELECT set_config('remtys.app_role_pass', '', false);


-- ============================================================
-- 2. GRANT PRIVILEGES — DML only, no DDL
-- ============================================================

-- Connection and schema usage
GRANT CONNECT ON DATABASE remtys2_db TO remtys_app;
GRANT USAGE ON SCHEMA public TO remtys_app;

-- DML on existing tables and sequences
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO remtys_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO remtys_app;

-- Default privileges for tables/sequences created in the future
-- (e.g., by future migrations run as postgres)
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO remtys_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO remtys_app;


-- ============================================================
-- 3. REVOKE DANGEROUS OPERATIONS
-- The app must NEVER be able to create, drop, or alter objects.
-- ============================================================

REVOKE CREATE ON SCHEMA public FROM remtys_app;
