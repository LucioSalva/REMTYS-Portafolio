-- ============================================================
-- REMTYS — Migration 004: Row-Level Security Preparation (Phase 1)
-- Date: 2026-04-10
-- Purpose: Define RLS policies WITHOUT enabling them
-- MUST be run by the postgres superuser
--
-- WHY PHASE 1 ONLY (policies defined, RLS not enabled):
--
--   1. The app connects as a single role (remtys_app) for all users.
--      RLS requires a session variable to identify the current
--      contribuyente, which the PHP layer does not set today.
--
--   2. Admin queries (admin_controller.php) read ALL contribuyentes'
--      data. Enabling RLS without an admin bypass policy would break
--      the backoffice entirely.
--
--   3. The PHP app uses a singleton PDO connection per request.
--      Adding SET LOCAL remtys.contribuyente_id = X to every
--      controller function is invasive and error-prone without a
--      centralized middleware layer that does not exist yet.
--
--   4. IDOR protections are already enforced in PHP controllers
--      via explicit WHERE contribuyente_id = :cid checks.
--
-- This migration creates the policies so that enabling RLS later
-- requires only:
--   a) PHP sets the GUC per transaction (SET LOCAL)
--   b) ALTER TABLE ... ENABLE ROW LEVEL SECURITY
--   c) An admin bypass policy or separate admin role
--
-- IDEMPOTENT: Safe to run multiple times.
-- ============================================================

BEGIN;

-- ============================================================
-- 1. RLS POLICIES FOR CONTRIBUYENTE-SCOPED TABLES
--
-- Each policy restricts rows to the contribuyente_id matching
-- the custom GUC 'remtys.contribuyente_id'.
--
-- current_setting('remtys.contribuyente_id', true) returns NULL
-- if the variable is not set, which means NO rows match —
-- a safe default that denies access rather than granting it.
-- ============================================================

-- --- expedientes ---
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE policyname = 'contribuyente_expedientes'
          AND tablename = 'expedientes'
    ) THEN
        CREATE POLICY contribuyente_expedientes ON expedientes
            FOR ALL
            TO remtys_app
            USING (
                contribuyente_id = current_setting('remtys.contribuyente_id', true)::int
            );
    END IF;
END $$;

-- --- documentos ---
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE policyname = 'contribuyente_documentos'
          AND tablename = 'documentos'
    ) THEN
        CREATE POLICY contribuyente_documentos ON documentos
            FOR ALL
            TO remtys_app
            USING (
                contribuyente_id = current_setting('remtys.contribuyente_id', true)::int
            );
    END IF;
END $$;

-- --- pagos ---
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE policyname = 'contribuyente_pagos'
          AND tablename = 'pagos'
    ) THEN
        CREATE POLICY contribuyente_pagos ON pagos
            FOR ALL
            TO remtys_app
            USING (
                contribuyente_id = current_setting('remtys.contribuyente_id', true)::int
            );
    END IF;
END $$;

-- --- obligaciones_fiscales ---
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE policyname = 'contribuyente_obligaciones'
          AND tablename = 'obligaciones_fiscales'
    ) THEN
        CREATE POLICY contribuyente_obligaciones ON obligaciones_fiscales
            FOR ALL
            TO remtys_app
            USING (
                contribuyente_id = current_setting('remtys.contribuyente_id', true)::int
            );
    END IF;
END $$;


-- ============================================================
-- 2. RLS POLICIES FOR JUNCTION / CHILD TABLES
--
-- predio_contribuyente — scoped by contribuyente_id directly
-- ============================================================

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies
        WHERE policyname = 'contribuyente_predio_contribuyente'
          AND tablename = 'predio_contribuyente'
    ) THEN
        CREATE POLICY contribuyente_predio_contribuyente ON predio_contribuyente
            FOR ALL
            TO remtys_app
            USING (
                contribuyente_id = current_setting('remtys.contribuyente_id', true)::int
            );
    END IF;
END $$;


-- ============================================================
-- 3. DO NOT ENABLE RLS
--
-- The following commands are commented out intentionally.
-- Execute them ONLY after completing the Phase 2 checklist below.
--
-- PHASE 2 CHECKLIST:
--   [ ] PHP middleware sets: SET LOCAL remtys.contribuyente_id = X
--       at the start of every contribuyente transaction
--   [ ] PHP middleware sets: RESET remtys.contribuyente_id
--       or uses SET LOCAL (auto-resets at transaction end)
--   [ ] Admin bypass: either
--       (a) Create a separate admin role exempt from RLS, OR
--       (b) Add a policy: USING (current_setting('remtys.role', true) = 'admin')
--   [ ] Integration tests pass with RLS enabled
--   [ ] Load test confirms no performance regression
-- ============================================================

-- ALTER TABLE expedientes           ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE documentos            ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE pagos                 ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE obligaciones_fiscales ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE predio_contribuyente  ENABLE ROW LEVEL SECURITY;

COMMIT;
