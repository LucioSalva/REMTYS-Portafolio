-- =========================================================================
-- Patch: 3 ajustes a la matriz de requisitos
--   1) Pago predial T18 -> requisito OR (grupo IDENT_O_RECIBO_PREDIAL)
--   2) Agregar variante JURIDICA a T1, T6, T9, T11 heredando de FISICA + Poder + Acta
--   3) Agregar columna tramite.anticipo_pct para modelar el 20% del Convenio
-- Idempotente: puede reejecutarse sin duplicar datos.
-- =========================================================================
BEGIN;

-- -------------------------------------------------------------------------
-- Ajuste 3 (primero, es schema): anticipo_pct en tramite
-- -------------------------------------------------------------------------
ALTER TABLE tramite
  ADD COLUMN IF NOT EXISTS anticipo_pct numeric(5,2)
  CHECK (anticipo_pct IS NULL OR (anticipo_pct > 0 AND anticipo_pct <= 100));
COMMENT ON COLUMN tramite.anticipo_pct IS
  'Porcentaje de anticipo requerido al iniciar el tramite (ej. Convenio = 20%). No es un documento, es una condicion de pago.';

UPDATE tramite SET anticipo_pct = 20.00 WHERE tramite_id = 8;  -- Convenio

-- -------------------------------------------------------------------------
-- Ajuste 1: Pago predial (T18) -> "ultimo recibo predial O identificacion"
-- -------------------------------------------------------------------------
-- Nuevo grupo
INSERT INTO grupo_documento (grupo_id, codigo, nombre, descripcion)
VALUES (2, 'IDENT_O_RECIBO_PREDIAL',
        'Identificacion oficial o ultimo recibo predial',
        'Cualquiera de los dos acredita al contribuyente registrado en el padron; basta con uno.')
ON CONFLICT (grupo_id) DO NOTHING;

INSERT INTO grupo_documento_tipo (grupo_id, tipo_documento_id) VALUES
  (2, 1),   -- IDENTIFICACION_OFICIAL
  (2, 18)   -- RECIBO_PREDIAL_VIGENTE
ON CONFLICT DO NOTHING;

-- Reemplazar los requisitos de T18 por un unico requisito que apunte al grupo
DELETE FROM tramite_requisito WHERE tramite_id = 18;

INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (18,'FISICA',              1, NULL, 2, false, 1, true, 'Ultimo recibo predial o identificacion oficial'),
 (18,'JURIDICA',             1, NULL, 2, false, 1, true, 'Ultimo recibo predial o identificacion oficial'),
 (18,'INSTITUCION_PUBLICA',  1, NULL, 2, false, 1, true, 'Ultimo recibo predial o identificacion oficial');

-- -------------------------------------------------------------------------
-- Ajuste 2: JURIDICA para T1, T6, T9, T11 (no declarada en el PDF)
--    Regla: hereda de FISICA + Poder Notarial (3) + Acta Constitutiva (4).
-- -------------------------------------------------------------------------
-- Limpia por si se reejecuta
DELETE FROM tramite_requisito
 WHERE aplica_a = 'JURIDICA' AND tramite_id IN (1, 6, 9, 11);

-- Inserta copiando la matriz de FISICA y re-ordenando, luego agrega 2 filas extra.
-- Usamos CTE para preservar orden y agregar los 2 nuevos al final.
WITH base AS (
  SELECT tramite_id, 'JURIDICA'::tipo_persona AS aplica_a,
         row_number() OVER (PARTITION BY tramite_id ORDER BY orden) AS orden,
         tipo_documento_id, grupo_id,
         original_requerido, copias_requeridas, obligatorio, condicion, notas
  FROM tramite_requisito
  WHERE aplica_a = 'FISICA' AND tramite_id IN (1, 6, 9, 11)
)
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion, notas)
SELECT tramite_id, aplica_a, orden, tipo_documento_id, grupo_id,
       original_requerido, copias_requeridas, obligatorio, condicion, notas
FROM base;

-- Anadir Poder Notarial y Acta Constitutiva al final de cada uno
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion, notas)
SELECT t, 'JURIDICA', max_orden + 1, 3,    NULL, true, 1, true, NULL, 'Agregado por herencia JURIDICA'
FROM (SELECT tramite_id AS t, max(orden) AS max_orden
      FROM tramite_requisito
      WHERE aplica_a='JURIDICA' AND tramite_id IN (1,6,9,11)
      GROUP BY tramite_id) s;

INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion, notas)
SELECT t, 'JURIDICA', max_orden + 1, 4,    NULL, true, 1, true, NULL, 'Agregado por herencia JURIDICA'
FROM (SELECT tramite_id AS t, max(orden) AS max_orden
      FROM tramite_requisito
      WHERE aplica_a='JURIDICA' AND tramite_id IN (1,6,9,11)
      GROUP BY tramite_id) s;

COMMIT;

-- -------------------------------------------------------------------------
-- Verificacion
-- -------------------------------------------------------------------------
SELECT 'Total requisitos' AS metrica, count(*)::text AS valor FROM tramite_requisito
UNION ALL
SELECT 'T18 por persona', string_agg(aplica_a::text || '=' || c::text, ', ')
  FROM (SELECT aplica_a, count(*) c FROM tramite_requisito WHERE tramite_id=18 GROUP BY aplica_a) s
UNION ALL
SELECT 'JURIDICA en T1/T6/T9/T11', string_agg(tramite_id::text || ':' || c::text, ', ')
  FROM (SELECT tramite_id, count(*) c FROM tramite_requisito
        WHERE aplica_a='JURIDICA' AND tramite_id IN (1,6,9,11)
        GROUP BY tramite_id ORDER BY tramite_id) s
UNION ALL
SELECT 'Convenio anticipo_pct', anticipo_pct::text FROM tramite WHERE tramite_id = 8;
