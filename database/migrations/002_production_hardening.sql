-- ============================================================
-- REMTYS — Migration 002: Production Hardening
-- Date: 2026-04-10
-- Purpose: Integrity constraints, FK hardening, index cleanup
-- MUST be run by the postgres superuser
--
-- NOTE: Role creation has been moved to 003_create_app_role.sql
-- This migration focuses ONLY on schema integrity improvements.
--
-- IDEMPOTENT: Safe to run multiple times.
-- ============================================================

BEGIN;

-- ============================================================
-- 1. FOREIGN KEY FIX — CASCADE to RESTRICT on usuarios→contribuyentes (DB-006)
-- Deleting a usuario must NOT silently destroy contribuyente
-- records which are legal/fiscal documents of a citizen.
-- ============================================================

ALTER TABLE contribuyentes DROP CONSTRAINT IF EXISTS contribuyentes_usuario_id_fkey;
ALTER TABLE contribuyentes ADD CONSTRAINT contribuyentes_usuario_id_fkey
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT;


-- ============================================================
-- 2. CHECK CONSTRAINTS — Financial amounts (DB-012)
-- Prevent negative monetary values at the database level.
-- Each constraint is wrapped in a DO block that checks for
-- prior existence, making this section idempotent.
-- ============================================================

-- Pagos: monto must be strictly positive
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_pagos_monto_positivo'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE pagos ADD CONSTRAINT chk_pagos_monto_positivo CHECK (monto > 0);
    END IF;
END $$;

-- Obligacion_periodo: amounts must be non-negative
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_op_monto_base'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE obligacion_periodo ADD CONSTRAINT chk_op_monto_base CHECK (monto >= 0);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_op_recargos'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE obligacion_periodo ADD CONSTRAINT chk_op_recargos CHECK (monto_recargos >= 0);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_op_pagado'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE obligacion_periodo ADD CONSTRAINT chk_op_pagado CHECK (monto_pagado >= 0);
    END IF;
END $$;

-- Pago_aplicacion: applied amount must be strictly positive
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_pa_monto'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE pago_aplicacion ADD CONSTRAINT chk_pa_monto CHECK (monto_aplicado > 0);
    END IF;
END $$;

-- Obligaciones_fiscales: base amount must be non-negative
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_of_monto'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE obligaciones_fiscales ADD CONSTRAINT chk_of_monto CHECK (monto_base >= 0);
    END IF;
END $$;

-- Predios: surface areas and cadastral value must be non-negative
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_predios_superficie_total'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE predios ADD CONSTRAINT chk_predios_superficie_total CHECK (superficie_total >= 0);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_predios_superficie_construida'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE predios ADD CONSTRAINT chk_predios_superficie_construida CHECK (superficie_construida >= 0);
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_predios_valor_catastral'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE predios ADD CONSTRAINT chk_predios_valor_catastral CHECK (valor_catastral >= 0);
    END IF;
END $$;

-- Periodos_fiscales: end date must be after start date
DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints
        WHERE constraint_name = 'chk_periodo_fechas'
          AND constraint_schema = 'public'
    ) THEN
        ALTER TABLE periodos_fiscales ADD CONSTRAINT chk_periodo_fechas CHECK (fecha_fin > fecha_inicio);
    END IF;
END $$;


-- ============================================================
-- 3. PARTIAL UNIQUE INDEX — Active predio-contribuyente (DB-015)
-- Prevent duplicate active ownership for the same pair.
-- ============================================================

CREATE UNIQUE INDEX IF NOT EXISTS idx_predio_contrib_unico_activo
    ON predio_contribuyente(predio_id, contribuyente_id) WHERE activo = TRUE;


-- ============================================================
-- 4. DROP REDUNDANT INDEXES
-- These duplicate the UNIQUE constraints on the same columns,
-- which already create implicit unique indexes in PostgreSQL.
-- ============================================================

-- usuarios.email already has UNIQUE constraint -> implicit index
DROP INDEX IF EXISTS idx_usuarios_email;

-- predios.clave_catastral already has UNIQUE constraint -> implicit index
DROP INDEX IF EXISTS idx_predios_clave_catastral;

-- predios.cuenta_predial already has UNIQUE constraint -> implicit index
DROP INDEX IF EXISTS idx_predios_cuenta_predial;

COMMIT;
