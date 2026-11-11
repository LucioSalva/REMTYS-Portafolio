-- ============================================================
-- Migration 006: Create business sequences for expediente and tramite numbering
-- ============================================================
-- Purpose:
--   ExpedienteService.php uses nextval('seq_expediente_num') to generate
--   expediente numbers like EXP-2026-000013.
--   Similarly, it uses nextval('seq_tramite_num') to generate
--   tramite numbers like TRM-2026-000013.
--
--   These sequences replace the previous approach of scanning MAX()
--   on the tables, providing a concurrency-safe way to generate
--   unique consecutive numbers.
--
-- Idempotent: Safe to run multiple times (IF NOT EXISTS).
-- Start value: 13 (existing data has 12 records in each table).
-- ============================================================

-- Sequence for expediente numbering (EXP-YYYY-NNNNNN)
CREATE SEQUENCE IF NOT EXISTS seq_expediente_num START WITH 13;

COMMENT ON SEQUENCE seq_expediente_num IS
    'Generates consecutive numbers for expediente numbering (EXP-YYYY-NNNNNN). Used by ExpedienteService.php via nextval().';

-- Sequence for tramite numbering (TRM-YYYY-NNNNNN)
CREATE SEQUENCE IF NOT EXISTS seq_tramite_num START WITH 13;

COMMENT ON SEQUENCE seq_tramite_num IS
    'Generates consecutive numbers for tramite numbering (TRM-YYYY-NNNNNN). Used by ExpedienteService.php via nextval().';

-- Grant USAGE to the application role if it exists
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'remtys_app') THEN
        EXECUTE 'GRANT USAGE, SELECT ON SEQUENCE seq_expediente_num TO remtys_app';
        EXECUTE 'GRANT USAGE, SELECT ON SEQUENCE seq_tramite_num TO remtys_app';
        RAISE NOTICE 'Granted USAGE on sequences to remtys_app role.';
    ELSE
        RAISE NOTICE 'Role remtys_app does not exist, skipping GRANT.';
    END IF;
END
$$;
