-- Migration 001: Secuencias para generacion segura de folios
-- Evita race conditions en generacion concurrente de numeros de expediente y tramite
-- Fecha: 2026-04-01

-- Obtener el maximo actual para inicializar las secuencias
DO $$
DECLARE
    max_exp INTEGER;
    max_trm INTEGER;
BEGIN
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_expediente FROM 10) AS INTEGER)), 0)
    INTO max_exp FROM expedientes WHERE numero_expediente LIKE 'EXP-' || to_char(NOW(), 'YYYY') || '-%';

    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_tramite FROM 10) AS INTEGER)), 0)
    INTO max_trm FROM tramites WHERE numero_tramite LIKE 'TRM-' || to_char(NOW(), 'YYYY') || '-%';

    -- Crear o resetear secuencias
    EXECUTE 'DROP SEQUENCE IF EXISTS seq_expediente_num';
    EXECUTE 'CREATE SEQUENCE seq_expediente_num START ' || (max_exp + 1);

    EXECUTE 'DROP SEQUENCE IF EXISTS seq_tramite_num';
    EXECUTE 'CREATE SEQUENCE seq_tramite_num START ' || (max_trm + 1);
END $$;
