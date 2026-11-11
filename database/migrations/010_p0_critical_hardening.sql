-- =============================================================================
-- REMTYS — Migración 010: Endurecimiento crítico P0
-- =============================================================================
-- Cubre los hallazgos críticos de la auditoría brutal:
--  C-01  Enum `clasificacion_documental` sin `PENDIENTE`
--  C-02  Doble mecanismo de generación de folio → tabla `folio_oficial`
--  C-05  `validaciones_documento` sin concepto de vigencia/rectificación
--  C-06  Versionado documental sin columna raíz ni unique parcial
--  C-08  `historial_expediente` sin catálogo formal de eventos
--  C-09  Ausencia de auditoría de cambios genérica (old/new diff)
--  H-09  Reutilización documental sin contexto forense
--  H-13  Timezone database-level → America/Mexico_City
--  H-21  Solapamiento de propietarios en predio_contribuyente
-- =============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 0. Extensiones requeridas
-- ----------------------------------------------------------------------------
CREATE EXTENSION IF NOT EXISTS btree_gist;  -- EXCLUDE con equality + rangos
CREATE EXTENSION IF NOT EXISTS pgcrypto;    -- gen_random_uuid para correlation_id

-- ----------------------------------------------------------------------------
-- 1. Timezone a nivel de BD (defensa en profundidad; config/database.php ya
--    lo hace por sesión, pero aquí queda persistente para utilidades externas)
-- ----------------------------------------------------------------------------
DO $$
BEGIN
    EXECUTE 'ALTER DATABASE ' || current_database() ||
            ' SET timezone TO ''America/Mexico_City''';
EXCEPTION WHEN insufficient_privilege THEN
    -- En despliegues donde el rol de aplicación no puede ALTER DATABASE,
    -- el timezone se establece por sesión en config/database.php.
    RAISE NOTICE '[010] ALTER DATABASE timezone skipped (insufficient privilege).';
END $$;

-- ----------------------------------------------------------------------------
-- 2. Enum clasificacion_documental: agregar PENDIENTE
--    El servicio RequisitosService ya retorna este valor en memoria.
-- ----------------------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_enum e
        JOIN pg_type t ON t.oid = e.enumtypid
        WHERE t.typname = 'clasificacion_documental' AND e.enumlabel = 'PENDIENTE'
    ) THEN
        ALTER TYPE clasificacion_documental ADD VALUE 'PENDIENTE' BEFORE 'VENCIDO';
    END IF;
END $$;

-- ----------------------------------------------------------------------------
-- 3. Tabla audit_cambios — auditoría genérica old/new con diff JSONB
--    Objetivo: reconstruir cualquier cambio sensible.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_cambios (
    id              BIGSERIAL PRIMARY KEY,
    fecha           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    tabla           VARCHAR(63) NOT NULL,
    registro_id     TEXT NOT NULL,                 -- TEXT para tolerar PKs no enteras
    operacion       VARCHAR(10) NOT NULL
                    CHECK (operacion IN ('INSERT','UPDATE','DELETE')),
    old_row         JSONB,
    new_row         JSONB,
    campos_diff     JSONB,                         -- solo las llaves cambiadas
    usuario_id      INTEGER,                       -- sin FK: preservar auditoría tras borrado de usuario
    usuario_email   VARCHAR(255),
    rol             VARCHAR(20),
    ip              VARCHAR(45),
    user_agent      VARCHAR(255),
    correlation_id  VARCHAR(40),                   -- liga a system_events.contexto->>'correlation_id'
    motivo          TEXT
);

CREATE INDEX IF NOT EXISTS idx_audit_cambios_tabla_registro
    ON audit_cambios(tabla, registro_id);
CREATE INDEX IF NOT EXISTS idx_audit_cambios_fecha
    ON audit_cambios(fecha DESC);
CREATE INDEX IF NOT EXISTS idx_audit_cambios_usuario
    ON audit_cambios(usuario_id, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_audit_cambios_correlation
    ON audit_cambios(correlation_id);

COMMENT ON TABLE audit_cambios IS
    'Auditoría genérica de cambios en entidades sensibles. Append-only. '
    'Poblada desde PHP (AuditService::registrarCambio) con old/new snapshots '
    'y diff calculado. NO usar triggers genéricos para controlar qué se audita.';

-- ----------------------------------------------------------------------------
-- 4. Tabla audit_eliminaciones — tombstones de DELETE físicos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_eliminaciones (
    id              BIGSERIAL PRIMARY KEY,
    fecha           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    tabla           VARCHAR(63) NOT NULL,
    registro_id     TEXT NOT NULL,
    fila_jsonb      JSONB NOT NULL,
    usuario_id      INTEGER,
    motivo          TEXT,
    correlation_id  VARCHAR(40)
);

CREATE INDEX IF NOT EXISTS idx_audit_elim_tabla_fecha
    ON audit_eliminaciones(tabla, fecha DESC);

-- ----------------------------------------------------------------------------
-- 5. Tabla folio_oficial — fuente única de verdad para numeración gubernamental
--    Reemplaza secuencias/funciones divergentes. Lock por (anio, tipo).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS folio_oficial (
    id                BIGSERIAL PRIMARY KEY,
    anio              SMALLINT NOT NULL,
    tipo              VARCHAR(20) NOT NULL,        -- EXP, TRM, ENT (libro entradas), etc.
    consecutivo       INTEGER NOT NULL,
    numero_completo   VARCHAR(40) NOT NULL,        -- EXP-2026-000001
    entidad_tipo      VARCHAR(40),                 -- 'expedientes', 'tramites'
    entidad_id        INTEGER,
    estado            VARCHAR(15) NOT NULL DEFAULT 'ASIGNADO'
                      CHECK (estado IN ('ASIGNADO','ANULADO')),
    motivo_anulacion  TEXT,
    usuario_id        INTEGER,
    correlation_id    VARCHAR(40),
    generado_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    anulado_at        TIMESTAMPTZ,
    UNIQUE (anio, tipo, consecutivo),
    UNIQUE (numero_completo)
);

CREATE INDEX IF NOT EXISTS idx_folio_oficial_entidad
    ON folio_oficial(entidad_tipo, entidad_id)
    WHERE entidad_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_folio_oficial_anio_tipo
    ON folio_oficial(anio, tipo);

COMMENT ON TABLE folio_oficial IS
    'Libro oficial de folios consecutivos. ASIGNADO o ANULADO. Nunca se borra.';

-- Tabla de contador con lock pesimista — una fila por (anio, tipo).
CREATE TABLE IF NOT EXISTS folio_contador (
    anio              SMALLINT NOT NULL,
    tipo              VARCHAR(20) NOT NULL,
    ultimo_valor      INTEGER NOT NULL DEFAULT 0,
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (anio, tipo)
);

COMMENT ON TABLE folio_contador IS
    'Contador serializable por (año, tipo). Usar SELECT ... FOR UPDATE '
    'desde FolioService.reservarFolio para obtener el siguiente consecutivo.';

-- ----------------------------------------------------------------------------
-- 6. validaciones_documento — concepto de vigencia y rectificación
-- ----------------------------------------------------------------------------
ALTER TABLE validaciones_documento
    ADD COLUMN IF NOT EXISTS vigente BOOLEAN NOT NULL DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS invalidada_por_id INTEGER REFERENCES validaciones_documento(id),
    ADD COLUMN IF NOT EXISTS motivo_rectificacion TEXT,
    ADD COLUMN IF NOT EXISTS estado_anterior estado_validacion,
    ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(40);

-- Unique parcial: una sola validación vigente por documento.
CREATE UNIQUE INDEX IF NOT EXISTS uq_validacion_vigente_por_doc
    ON validaciones_documento(documento_id)
    WHERE vigente = TRUE;

-- Índice de orden determinista por (documento, fecha DESC, id DESC)
CREATE INDEX IF NOT EXISTS idx_validaciones_doc_fecha_id
    ON validaciones_documento(documento_id, fecha DESC, id DESC);

-- Bootstrap: marcar validaciones existentes como vigentes solo si son la última por documento.
WITH ranked AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY documento_id ORDER BY fecha DESC, id DESC) AS rn
    FROM validaciones_documento
)
UPDATE validaciones_documento v
SET vigente = (r.rn = 1)
FROM ranked r
WHERE v.id = r.id;

-- FK desde documentos a la validación vigente (opcional — acelera lookup)
ALTER TABLE documentos
    ADD COLUMN IF NOT EXISTS validacion_vigente_id INTEGER
        REFERENCES validaciones_documento(id);

-- ----------------------------------------------------------------------------
-- 7. documentos — columna raiz_id denormalizada + unique parcial de versión activa
-- ----------------------------------------------------------------------------
ALTER TABLE documentos
    ADD COLUMN IF NOT EXISTS raiz_id INTEGER REFERENCES documentos(id);

-- Backfill raiz_id: para docs sin padre, raiz=self; para docs con padre, raiz del árbol.
WITH RECURSIVE arbol AS (
    SELECT id, padre_documento_id, id AS raiz
    FROM documentos
    WHERE padre_documento_id IS NULL
    UNION ALL
    SELECT d.id, d.padre_documento_id, a.raiz
    FROM documentos d
    JOIN arbol a ON d.padre_documento_id = a.id
)
UPDATE documentos d SET raiz_id = a.raiz
FROM arbol a
WHERE d.id = a.id AND (d.raiz_id IS NULL OR d.raiz_id <> a.raiz);

CREATE INDEX IF NOT EXISTS idx_documentos_raiz_id ON documentos(raiz_id);

-- Unique parcial: a lo más UNA versión activa por raíz.
-- Antes de aplicar, normalizamos duplicados (el trigger legacy permitía race).
WITH dupes AS (
    SELECT raiz_id, COUNT(*) AS c,
           MAX(id) FILTER (WHERE es_version_activa = TRUE) AS keep_id
    FROM documentos
    WHERE es_version_activa = TRUE AND raiz_id IS NOT NULL
    GROUP BY raiz_id
    HAVING COUNT(*) > 1
)
UPDATE documentos d
SET es_version_activa = FALSE
FROM dupes
WHERE d.raiz_id = dupes.raiz_id
  AND d.es_version_activa = TRUE
  AND d.id <> dupes.keep_id;

CREATE UNIQUE INDEX IF NOT EXISTS uq_doc_version_activa_por_raiz
    ON documentos(raiz_id)
    WHERE es_version_activa = TRUE;

-- Unique de (raiz_id, version) — previene duplicados de numeración de versión
CREATE UNIQUE INDEX IF NOT EXISTS uq_doc_raiz_version
    ON documentos(raiz_id, version)
    WHERE raiz_id IS NOT NULL;

-- ----------------------------------------------------------------------------
-- 8. Trigger para mantener raiz_id y es_version_activa con lock pesimista.
--    Reemplaza gestionar_version_documento legacy.
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION trg_documentos_before_insert()
RETURNS TRIGGER AS $$
DECLARE
    v_raiz INTEGER;
BEGIN
    -- Derivar raiz_id
    IF NEW.padre_documento_id IS NULL THEN
        NEW.raiz_id := NULL;   -- se fija en AFTER con el id propio
    ELSE
        SELECT raiz_id INTO v_raiz FROM documentos WHERE id = NEW.padre_documento_id FOR UPDATE;
        IF v_raiz IS NULL THEN
            v_raiz := NEW.padre_documento_id;
        END IF;
        NEW.raiz_id := v_raiz;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION trg_documentos_after_insert_root()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.raiz_id IS NULL THEN
        UPDATE documentos SET raiz_id = NEW.id WHERE id = NEW.id;
    ELSE
        -- Desactivar versiones anteriores del mismo árbol
        UPDATE documentos
        SET es_version_activa = FALSE
        WHERE raiz_id = NEW.raiz_id
          AND id <> NEW.id
          AND es_version_activa = TRUE;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_documentos_bi ON documentos;
CREATE TRIGGER trg_documentos_bi
    BEFORE INSERT ON documentos
    FOR EACH ROW
    EXECUTE FUNCTION trg_documentos_before_insert();

DROP TRIGGER IF EXISTS trg_versionar_documento ON documentos;  -- legacy
DROP TRIGGER IF EXISTS trg_documentos_ai_root ON documentos;
CREATE TRIGGER trg_documentos_ai_root
    AFTER INSERT ON documentos
    FOR EACH ROW
    EXECUTE FUNCTION trg_documentos_after_insert_root();

-- ----------------------------------------------------------------------------
-- 9. documento_expediente — contexto forense de reutilización
-- ----------------------------------------------------------------------------
ALTER TABLE documento_expediente
    ADD COLUMN IF NOT EXISTS reutilizado_por_usuario_id INTEGER REFERENCES usuarios(id),
    ADD COLUMN IF NOT EXISTS reutilizado_desde_expediente_id INTEGER REFERENCES expedientes(id),
    ADD COLUMN IF NOT EXISTS reutilizado_at TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS justificacion TEXT,
    ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(40);

CREATE INDEX IF NOT EXISTS idx_doc_exp_reutilizado
    ON documento_expediente(reutilizado) WHERE reutilizado = TRUE;

-- ----------------------------------------------------------------------------
-- 10. historial_expediente — catálogo formal de eventos (no-destructivo)
--     Se agrega columna opcional `categoria` para clasificación.
-- ----------------------------------------------------------------------------
ALTER TABLE historial_expediente
    ADD COLUMN IF NOT EXISTS categoria VARCHAR(30),
    ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(40),
    ADD COLUMN IF NOT EXISTS ip VARCHAR(45);

CREATE INDEX IF NOT EXISTS idx_historial_exp_evento
    ON historial_expediente(expediente_id, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_historial_exp_categoria
    ON historial_expediente(categoria) WHERE categoria IS NOT NULL;

-- Catálogo de eventos válidos (referencia, no constraint dura — añadir nuevos
-- eventos no debe requerir migración).
CREATE TABLE IF NOT EXISTS historial_eventos_catalogo (
    evento          VARCHAR(100) PRIMARY KEY,
    categoria       VARCHAR(30) NOT NULL,
    descripcion     TEXT,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO historial_eventos_catalogo (evento, categoria, descripcion) VALUES
    ('CREACION_EXP',            'EXPEDIENTE', 'Expediente creado'),
    ('CAMBIO_ESTADO_EXP',       'EXPEDIENTE', 'Estado del expediente modificado'),
    ('OBS_EDITADA_EXP',         'EXPEDIENTE', 'Observaciones del expediente editadas'),
    ('CAMBIO_PREDIO_EXP',       'EXPEDIENTE', 'Predio vinculado al expediente cambiado'),
    ('EXP_ARCHIVADO',           'EXPEDIENTE', 'Expediente archivado/cerrado'),
    ('NUEVO_TRAMITE',           'TRAMITE',    'Trámite añadido al expediente'),
    ('CAMBIO_ESTADO_TRAMITE',   'TRAMITE',    'Estado del trámite modificado'),
    ('TRAMITE_ENVIADO',         'TRAMITE',    'Contribuyente envió el trámite a revisión'),
    ('TRAMITE_DICTAMINADO',     'TRAMITE',    'Trámite resuelto (dictamen emitido)'),
    ('DEP_AGREGADA',            'TRAMITE',    'Dependencia DEPENDE_DE agregada'),
    ('DEP_ELIMINADA',           'TRAMITE',    'Dependencia DEPENDE_DE eliminada'),
    ('DOC_SUBIDO',              'DOCUMENTO',  'Documento subido y vinculado al expediente'),
    ('DOC_REUTILIZADO',         'DOCUMENTO',  'Documento reutilizado desde otro expediente'),
    ('DOC_NUEVA_VERSION',       'DOCUMENTO',  'Nueva versión del documento generada'),
    ('DOC_APROBADO',            'DOCUMENTO',  'Documento aprobado por validador'),
    ('DOC_RECHAZADO',           'DOCUMENTO',  'Documento rechazado por validador'),
    ('DOC_EN_REVISION',         'DOCUMENTO',  'Documento marcado en revisión'),
    ('DOC_RECTIFICADO',         'DOCUMENTO',  'Validación rectificada (vigente anterior anulada)'),
    ('DOC_VENCIDO',             'DOCUMENTO',  'Documento detectado como vencido'),
    ('INSPECCION_PROGRAMADA',   'INSPECCION', 'Inspección programada'),
    ('INSPECCION_REALIZADA',    'INSPECCION', 'Inspección realizada'),
    ('PAGO_APLICADO_EXP',       'FISCAL',     'Pago aplicado a obligación vinculada al expediente'),
    ('PAGO_REVERSADO',          'FISCAL',     'Pago reversado (asiento negativo)')
ON CONFLICT (evento) DO NOTHING;

-- ----------------------------------------------------------------------------
-- 11. predio_contribuyente — EXCLUDE GiST contra solapamientos
-- ----------------------------------------------------------------------------
-- Normalizar datos históricos potencialmente conflictivos antes de aplicar EXCLUDE.
-- Política conservadora: cerrar los activos duplicados dejando sólo el más reciente por predio.
WITH rank_act AS (
    SELECT id,
           predio_id,
           ROW_NUMBER() OVER (
               PARTITION BY predio_id
               ORDER BY COALESCE(fecha_inicio, CURRENT_DATE) DESC, id DESC
           ) AS rn
    FROM predio_contribuyente
    WHERE activo = TRUE
)
UPDATE predio_contribuyente p
SET activo = FALSE,
    fecha_fin = COALESCE(p.fecha_fin, CURRENT_DATE)
FROM rank_act r
WHERE p.id = r.id AND r.rn > 1;

-- EXCLUDE: solo una relación activa por predio en un rango temporal dado
ALTER TABLE predio_contribuyente
    DROP CONSTRAINT IF EXISTS excl_predio_contribuyente_solapamiento;

ALTER TABLE predio_contribuyente
    ADD CONSTRAINT excl_predio_contribuyente_solapamiento
    EXCLUDE USING gist (
        predio_id WITH =,
        daterange(fecha_inicio, COALESCE(fecha_fin, DATE 'infinity'), '[]') WITH &&
    )
    WHERE (activo = TRUE);

-- ----------------------------------------------------------------------------
-- 12. documento_expediente y validaciones_documento: relajar CASCADE
--     Evidencia forense — pasar a RESTRICT para expedientes y documentos.
--     IMPORTANTE: conservamos CASCADE solo donde el borrado del padre implica
--     borrado genuino (documento_expediente.tramite_id como soft-link → ON DELETE
--     no definido; lo dejamos como está — borrar un trámite ya es raro).
-- ----------------------------------------------------------------------------
DO $$
BEGIN
    -- documento_expediente.documento_id CASCADE → RESTRICT (no borrar documentos con historial)
    IF EXISTS (
        SELECT 1 FROM information_schema.referential_constraints rc
        JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = rc.constraint_name
        WHERE kcu.table_name = 'documento_expediente'
          AND kcu.column_name = 'documento_id'
          AND rc.delete_rule = 'CASCADE'
    ) THEN
        ALTER TABLE documento_expediente
            DROP CONSTRAINT IF EXISTS documento_expediente_documento_id_fkey;
        ALTER TABLE documento_expediente
            ADD CONSTRAINT documento_expediente_documento_id_fkey
            FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE RESTRICT;
    END IF;

    -- documento_expediente.expediente_id CASCADE → RESTRICT
    IF EXISTS (
        SELECT 1 FROM information_schema.referential_constraints rc
        JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = rc.constraint_name
        WHERE kcu.table_name = 'documento_expediente'
          AND kcu.column_name = 'expediente_id'
          AND rc.delete_rule = 'CASCADE'
    ) THEN
        ALTER TABLE documento_expediente
            DROP CONSTRAINT IF EXISTS documento_expediente_expediente_id_fkey;
        ALTER TABLE documento_expediente
            ADD CONSTRAINT documento_expediente_expediente_id_fkey
            FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE RESTRICT;
    END IF;

    -- validaciones_documento.documento_id CASCADE → RESTRICT
    IF EXISTS (
        SELECT 1 FROM information_schema.referential_constraints rc
        JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = rc.constraint_name
        WHERE kcu.table_name = 'validaciones_documento'
          AND kcu.column_name = 'documento_id'
          AND rc.delete_rule = 'CASCADE'
    ) THEN
        ALTER TABLE validaciones_documento
            DROP CONSTRAINT IF EXISTS validaciones_documento_documento_id_fkey;
        ALTER TABLE validaciones_documento
            ADD CONSTRAINT validaciones_documento_documento_id_fkey
            FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE RESTRICT;
    END IF;

    -- historial_expediente.expediente_id CASCADE → RESTRICT
    IF EXISTS (
        SELECT 1 FROM information_schema.referential_constraints rc
        JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = rc.constraint_name
        WHERE kcu.table_name = 'historial_expediente'
          AND kcu.column_name = 'expediente_id'
          AND rc.delete_rule = 'CASCADE'
    ) THEN
        ALTER TABLE historial_expediente
            DROP CONSTRAINT IF EXISTS historial_expediente_expediente_id_fkey;
        ALTER TABLE historial_expediente
            ADD CONSTRAINT historial_expediente_expediente_id_fkey
            FOREIGN KEY (expediente_id) REFERENCES expedientes(id) ON DELETE RESTRICT;
    END IF;
END $$;

-- ----------------------------------------------------------------------------
-- 13. Libro oficial de entradas (recepción con sello)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS libro_entradas (
    id                  BIGSERIAL PRIMARY KEY,
    fecha               TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    anio                SMALLINT NOT NULL,
    tipo_entrada        VARCHAR(40) NOT NULL,       -- TRAMITE, PAGO, DOCUMENTO, INSPECCION
    numero_consecutivo  INTEGER NOT NULL,
    numero_completo     VARCHAR(50) NOT NULL,       -- ENT-TRAMITE-2026-000001
    entidad_tipo        VARCHAR(40),
    entidad_id          INTEGER,
    usuario_recepcion_id INTEGER REFERENCES usuarios(id),
    contribuyente_id    INTEGER REFERENCES contribuyentes(id),
    correlation_id      VARCHAR(40),
    sello_hash          VARCHAR(64),                 -- SHA-256 de (numero_completo + fecha + entidad)
    UNIQUE (anio, tipo_entrada, numero_consecutivo)
);

CREATE INDEX IF NOT EXISTS idx_libro_entradas_fecha
    ON libro_entradas(fecha DESC);
CREATE INDEX IF NOT EXISTS idx_libro_entradas_entidad
    ON libro_entradas(entidad_tipo, entidad_id)
    WHERE entidad_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_libro_entradas_contribuyente
    ON libro_entradas(contribuyente_id);

-- ----------------------------------------------------------------------------
-- 14. audit_vista_documento — log estructurado de acceso a documentos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_vista_documento (
    id              BIGSERIAL PRIMARY KEY,
    fecha           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    documento_id    INTEGER NOT NULL,  -- sin FK: preservar auditoría si documento se anula
    usuario_id      INTEGER,
    rol             VARCHAR(20),
    contribuyente_id INTEGER,
    ip              VARCHAR(45),
    user_agent      VARCHAR(255),
    disposition     VARCHAR(20) NOT NULL
                    CHECK (disposition IN ('inline','attachment','denied','thumbnail')),
    motivo_denegacion VARCHAR(100),
    tamano_bytes    INTEGER,
    correlation_id  VARCHAR(40)
);

CREATE INDEX IF NOT EXISTS idx_audit_vista_doc_documento
    ON audit_vista_documento(documento_id, fecha DESC);
CREATE INDEX IF NOT EXISTS idx_audit_vista_doc_usuario
    ON audit_vista_documento(usuario_id, fecha DESC);

-- ----------------------------------------------------------------------------
-- 15. Tabla rate_limits persistente (evita bypass por rotación de sesión)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    clave           VARCHAR(120) NOT NULL,   -- ej: "login:ip:203.0.113.10"
    ventana_inicio  TIMESTAMPTZ NOT NULL,
    contador        INTEGER NOT NULL DEFAULT 0,
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (clave, ventana_inicio)
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_updated
    ON rate_limits(updated_at);

-- ----------------------------------------------------------------------------
-- 16. Tabla password_reset_tokens (flujo de recuperación)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id              BIGSERIAL PRIMARY KEY,
    usuario_id      INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash      VARCHAR(64) NOT NULL,   -- SHA-256 del token
    expires_at      TIMESTAMPTZ NOT NULL,
    used_at         TIMESTAMPTZ,
    invalidated_at  TIMESTAMPTZ,
    ip_solicitud    VARCHAR(45),
    ip_uso          VARCHAR(45),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_prt_token_hash
    ON password_reset_tokens(token_hash);
CREATE INDEX IF NOT EXISTS idx_prt_usuario
    ON password_reset_tokens(usuario_id, created_at DESC);

-- ----------------------------------------------------------------------------
-- 17. Tabla de feature flags (rollout seguro)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feature_flags (
    clave           VARCHAR(80) PRIMARY KEY,
    descripcion     TEXT,
    habilitado      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO feature_flags (clave, descripcion, habilitado) VALUES
    ('mfa_admin',            'MFA obligatorio para admin/supergod',     FALSE),
    ('doc_signature',        'Firma electrónica de documentos resolutivos', FALSE),
    ('public_folio_lookup',  'Portal público de validación por folio',  FALSE),
    ('payment_real_gateway', 'Aplicación real de pagos (gateway integrado)', FALSE)
ON CONFLICT (clave) DO NOTHING;

-- ----------------------------------------------------------------------------
-- 18. Desaconsejar funciones legacy con RAISE NOTICE
--     No las borramos para no romper ningún código externo; pero las
--     redefinimos para que avisen.
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION generar_numero_expediente()
RETURNS VARCHAR AS $$
BEGIN
    RAISE NOTICE
        '[DEPRECATED] generar_numero_expediente() — use FolioService::reservarFolio(''EXP'').';
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION generar_numero_tramite()
RETURNS VARCHAR AS $$
BEGIN
    RAISE NOTICE
        '[DEPRECATED] generar_numero_tramite() — use FolioService::reservarFolio(''TRM'').';
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

COMMIT;
