-- =============================================================================
-- REMTYS - Migracion 012: correlation_id + retencion en system_events
-- =============================================================================
-- Cubre:
--  - Columna correlation_id dedicada (hasta ahora vivia dentro de contexto JSONB
--    lo que impedia filtrar/joinear eficientemente con audit_cambios/historial).
--  - Politica de retencion por nivel (tabla append-only crece ilimitadamente si
--    no se purga). Se implementa como funcion SQL invocable desde cron/docker.
--  - Backfill de correlation_id desde contexto->>'correlation_id' para eventos
--    existentes, para que la consola supergod pueda correlacionar hacia atras.
-- =============================================================================

BEGIN;

-- ---------------------------------------------------------------------------
-- 1. Agregar columna correlation_id + indice
-- ---------------------------------------------------------------------------
ALTER TABLE system_events
    ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(40);

-- Backfill desde contexto JSONB (eventos anteriores a esta migracion)
UPDATE system_events
SET correlation_id = contexto->>'correlation_id'
WHERE correlation_id IS NULL
  AND contexto ? 'correlation_id'
  AND length(contexto->>'correlation_id') BETWEEN 8 AND 40;

CREATE INDEX IF NOT EXISTS idx_sysevents_correlation
    ON system_events (correlation_id)
    WHERE correlation_id IS NOT NULL;

COMMENT ON COLUMN system_events.correlation_id IS
'ID de correlacion por request. Liga system_events con audit_cambios, historial_expediente y archivos de log. Generado en app/helpers/audit.php correlationId().';

-- ---------------------------------------------------------------------------
-- 2. Funcion de retencion por nivel
--    Politica por defecto:
--      INFO, SUCCESS     -> 90 dias
--      WARNING           -> 180 dias
--      ERROR, CRITICAL   -> 365 dias (no se eliminan en ventana corta)
--    Invocable desde tarea programada (ver docker/cron o pg_cron).
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION purgar_system_events(
    p_dias_info     INTEGER DEFAULT 90,
    p_dias_warning  INTEGER DEFAULT 180,
    p_dias_error    INTEGER DEFAULT 365
)
RETURNS TABLE (nivel_purgado VARCHAR, filas_eliminadas BIGINT) AS $$
DECLARE
    v_count BIGINT;
BEGIN
    -- INFO/SUCCESS
    DELETE FROM system_events
    WHERE nivel IN ('INFO','SUCCESS')
      AND created_at < NOW() - (p_dias_info || ' days')::INTERVAL;
    GET DIAGNOSTICS v_count = ROW_COUNT;
    nivel_purgado := 'INFO/SUCCESS';
    filas_eliminadas := v_count;
    RETURN NEXT;

    -- WARNING
    DELETE FROM system_events
    WHERE nivel = 'WARNING'
      AND created_at < NOW() - (p_dias_warning || ' days')::INTERVAL;
    GET DIAGNOSTICS v_count = ROW_COUNT;
    nivel_purgado := 'WARNING';
    filas_eliminadas := v_count;
    RETURN NEXT;

    -- ERROR/CRITICAL
    DELETE FROM system_events
    WHERE nivel IN ('ERROR','CRITICAL')
      AND created_at < NOW() - (p_dias_error || ' days')::INTERVAL;
    GET DIAGNOSTICS v_count = ROW_COUNT;
    nivel_purgado := 'ERROR/CRITICAL';
    filas_eliminadas := v_count;
    RETURN NEXT;

    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION purgar_system_events IS
'Aplica retencion por nivel sobre system_events. Invocar diariamente desde cron. Retorna filas eliminadas por tier.';

-- ---------------------------------------------------------------------------
-- 3. Funcion de purga para tablas de rate_limits obsoletas
--    (buckets que llevan mas de 7 dias sin actividad)
-- ---------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION purgar_rate_limits_inactivos(
    p_dias_sin_actividad INTEGER DEFAULT 7
)
RETURNS BIGINT AS $$
DECLARE
    v_count BIGINT;
BEGIN
    DELETE FROM rate_limits
    WHERE ultimo_intento < NOW() - (p_dias_sin_actividad || ' days')::INTERVAL
      AND (bloqueado_hasta IS NULL OR bloqueado_hasta < NOW());
    GET DIAGNOSTICS v_count = ROW_COUNT;
    RETURN v_count;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION purgar_rate_limits_inactivos IS
'Elimina buckets de rate_limits sin actividad reciente y sin bloqueo vigente. Invocar diariamente.';

-- ---------------------------------------------------------------------------
-- 4. Vista auxiliar para monitoreo de volumen de eventos
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_system_events_volumen AS
SELECT
    DATE_TRUNC('day', created_at)::DATE AS dia,
    nivel,
    categoria,
    COUNT(*) AS total
FROM system_events
WHERE created_at >= NOW() - INTERVAL '30 days'
GROUP BY 1, 2, 3
ORDER BY 1 DESC, 2, 3;

COMMENT ON VIEW v_system_events_volumen IS
'Volumen diario de eventos por nivel/categoria en los ultimos 30 dias. Uso: dimensionar retencion y detectar ruido/incidentes.';

COMMIT;
