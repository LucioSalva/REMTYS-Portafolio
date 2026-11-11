-- ============================================================
-- REMTYS — Migration 005: Catálogo de Códigos Postales y Colonias
-- Date: 2026-04-10
-- Purpose: Normalize postal codes and colonies for the municipality
--          so the UI can offer dropdowns instead of free-text input.
--
-- IDEMPOTENT: Safe to run multiple times.
--   - CREATE TABLE IF NOT EXISTS
--   - CREATE INDEX IF NOT EXISTS
--   - GRANT is idempotent in PostgreSQL
--
-- MUST be run by the postgres superuser.
-- ============================================================

BEGIN;

-- ============================================================
-- 1. CATALOG TABLE
-- ============================================================
-- One row per (codigo_postal, colonia) pair.
-- municipio and estado carry defaults for the municipality but are stored
-- explicitly so the table can serve other municipalities in the future.
-- The 'activo' flag allows soft-disabling entries without deleting data.
-- ============================================================

CREATE TABLE IF NOT EXISTS catalogo_cp_colonia (
    id              SERIAL PRIMARY KEY,
    codigo_postal   VARCHAR(5)   NOT NULL
                    CONSTRAINT chk_cp_format CHECK (codigo_postal ~ '^\d{5}$'),
    colonia         VARCHAR(255) NOT NULL,
    municipio       VARCHAR(255) NOT NULL DEFAULT 'Municipio Demo',
    estado          VARCHAR(100) NOT NULL DEFAULT 'Estado de México',
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,

    CONSTRAINT uq_cp_colonia UNIQUE (codigo_postal, colonia)
);

COMMENT ON TABLE  catalogo_cp_colonia IS 'Catálogo de códigos postales y colonias del municipio (fuente: SEPOMEX)';
COMMENT ON COLUMN catalogo_cp_colonia.codigo_postal IS 'Código postal de 5 dígitos (SEPOMEX)';
COMMENT ON COLUMN catalogo_cp_colonia.colonia       IS 'Nombre de la colonia según SEPOMEX';
COMMENT ON COLUMN catalogo_cp_colonia.activo        IS 'FALSE = entrada deshabilitada sin borrar el registro';

-- ============================================================
-- 2. INDEXES
-- ============================================================
-- idx_catalogo_cp: the UI autocomplete will search by CP first.
-- idx_catalogo_colonia: secondary search by colony name.
-- Both are justified by the dropdown/typeahead access pattern.
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_catalogo_cp
    ON catalogo_cp_colonia (codigo_postal);

CREATE INDEX IF NOT EXISTS idx_catalogo_colonia
    ON catalogo_cp_colonia (colonia);

-- ============================================================
-- 3. PERMISSIONS
-- ============================================================
-- Grant SELECT to the application role if it exists.
-- The role is created in migration 003; if it has not been run yet
-- we skip gracefully instead of failing.
-- ============================================================

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'remtys_app') THEN
        EXECUTE 'GRANT SELECT ON catalogo_cp_colonia TO remtys_app';
    ELSE
        RAISE NOTICE 'Role remtys_app does not exist yet — skipping GRANT. Run migration 003 first, then re-run this migration or grant manually.';
    END IF;
END
$$;

COMMIT;
