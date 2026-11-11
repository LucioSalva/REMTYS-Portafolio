-- =============================================================================
-- REMTYS - Migracion 011: Rate limit por IP persistente en BD
-- =============================================================================
-- Cubre hallazgo P0: "rate limit por sesion es evadible rotando cookie";
-- se persiste contador por (clave, ip) con ventana deslizante y bloqueo
-- temporal independiente del estado de sesion.
-- =============================================================================

BEGIN;

-- ---------------------------------------------------------------------------
-- 0. Compatibilidad: la migracion 010 crea una version previa de rate_limits
--    (PK clave+ventana_inicio, sin columna ip). Si existe esa forma, se
--    reemplaza: solo contiene contadores efimeros.
-- ---------------------------------------------------------------------------
DO $$
BEGIN
    IF to_regclass('public.rate_limits') IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM information_schema.columns
           WHERE table_schema = 'public' AND table_name = 'rate_limits' AND column_name = 'ip'
       ) THEN
        DROP TABLE public.rate_limits;
    END IF;
END $$;

-- ---------------------------------------------------------------------------
-- 1. Tabla rate_limits
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    clave             VARCHAR(64)  NOT NULL,
    ip                INET         NOT NULL,
    ventana_inicio    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    contador          INTEGER      NOT NULL DEFAULT 0,
    bloqueado_hasta   TIMESTAMPTZ  NULL,
    ultimo_intento    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    PRIMARY KEY (clave, ip)
);

COMMENT ON TABLE rate_limits IS
'Contador de intentos por (clave logica, IP) para rate limit transversal (login, password reset, registro, etc). Sobrevive rotacion de sesion.';

COMMENT ON COLUMN rate_limits.clave IS
'Clave logica del bucket. Valores: login, password_reset, register, email_verify, contribuyente_profile_update, etc.';

COMMENT ON COLUMN rate_limits.bloqueado_hasta IS
'Timestamp hasta el cual la IP esta bloqueada para esta clave. NULL = no bloqueada.';

-- ---------------------------------------------------------------------------
-- 2. Indices auxiliares
-- ---------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_rate_limits_ventana
    ON rate_limits (ventana_inicio);

CREATE INDEX IF NOT EXISTS idx_rate_limits_bloqueado
    ON rate_limits (bloqueado_hasta)
    WHERE bloqueado_hasta IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_rate_limits_ultimo_intento
    ON rate_limits (ultimo_intento);

COMMIT;
