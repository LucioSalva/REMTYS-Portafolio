-- ============================================================
-- REMTYS APP — Gestión Predial y Fiscal Municipal
-- Esquema PostgreSQL completo
-- Versión: 1.0.0
-- ============================================================

-- Limpiar si existe
DROP VIEW IF EXISTS v_contribuyentes_detalle CASCADE;
DROP TABLE IF EXISTS inspeccion_documentos CASCADE;
DROP TABLE IF EXISTS inspecciones CASCADE;
DROP TABLE IF EXISTS pago_aplicacion CASCADE;
DROP TABLE IF EXISTS pagos CASCADE;
DROP TABLE IF EXISTS obligacion_periodo CASCADE;
DROP TABLE IF EXISTS obligaciones_fiscales CASCADE;
DROP TABLE IF EXISTS periodos_fiscales CASCADE;
DROP TABLE IF EXISTS historial_expediente CASCADE;
DROP TABLE IF EXISTS tramite_dependencias CASCADE;
DROP TABLE IF EXISTS validaciones_documento CASCADE;
DROP TABLE IF EXISTS documento_expediente CASCADE;
DROP TABLE IF EXISTS documentos CASCADE;
DROP TABLE IF EXISTS tramites CASCADE;
DROP TABLE IF EXISTS expedientes CASCADE;
DROP TABLE IF EXISTS requisitos_documentales CASCADE;
DROP TABLE IF EXISTS tipos_documento CASCADE;
DROP TABLE IF EXISTS tipos_tramite CASCADE;
DROP TABLE IF EXISTS predio_contribuyente CASCADE;
DROP TABLE IF EXISTS predios CASCADE;
DROP TABLE IF EXISTS contribuyente_moral CASCADE;
DROP TABLE IF EXISTS contribuyente_fisica CASCADE;
DROP TABLE IF EXISTS contribuyentes CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;

-- ============================================================
-- TIPOS ENUMERADOS
-- ============================================================

DO $$ BEGIN
    CREATE TYPE rol_usuario AS ENUM ('admin', 'contribuyente', 'revisor', 'supergod');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE tipo_persona AS ENUM ('fisica', 'moral');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE estado_validacion AS ENUM ('pendiente', 'aprobado', 'rechazado', 'en_revision');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE estado_expediente AS ENUM ('abierto', 'en_proceso', 'resuelto', 'cerrado', 'cancelado');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE estado_tramite AS ENUM ('iniciado', 'en_revision', 'documentacion_pendiente', 'aprobado', 'rechazado', 'cancelado');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE clasificacion_documental AS ENUM ('CUMPLE', 'REUTILIZABLE', 'VENCIDO', 'INVALIDO', 'FALTANTE', 'ESPECIFICO', 'EXCLUSIVO');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE estado_inspeccion AS ENUM ('programada', 'realizada', 'cancelada', 'pendiente');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE uso_documento AS ENUM ('requisito', 'evidencia', 'soporte', 'inspeccion');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE usuarios (
    id                              SERIAL PRIMARY KEY,
    email                           VARCHAR(255) NOT NULL UNIQUE,
    password_hash                   VARCHAR(255) NOT NULL,
    nombre_completo                 VARCHAR(255) NOT NULL,
    rol                             rol_usuario NOT NULL DEFAULT 'contribuyente',
    activo                          BOOLEAN NOT NULL DEFAULT TRUE,
    email_verificado                BOOLEAN NOT NULL DEFAULT FALSE,
    email_verificado_at             TIMESTAMP WITH TIME ZONE,
    email_verificacion_token_hash   VARCHAR(64),
    email_verificacion_expires_at   TIMESTAMP WITH TIME ZONE,
    ultimo_envio_verificacion_at    TIMESTAMP WITH TIME ZONE,
    ultimo_acceso                   TIMESTAMP WITH TIME ZONE,
    intentos_fallidos               SMALLINT NOT NULL DEFAULT 0,
    bloqueado_hasta                 TIMESTAMP WITH TIME ZONE,
    created_at                      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at                      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_email_verificacion_token ON usuarios(email_verificacion_token_hash);

-- ============================================================
-- TABLA: contribuyentes
-- ============================================================
CREATE TABLE contribuyentes (
    id                      SERIAL PRIMARY KEY,
    usuario_id              INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    tipo_persona            tipo_persona NOT NULL DEFAULT 'fisica',
    nombre_completo         VARCHAR(400) NOT NULL,
    rfc                     VARCHAR(13) NOT NULL UNIQUE,
    -- Domicilio fiscal
    calle                   VARCHAR(255),
    numero_exterior         VARCHAR(20),
    numero_interior         VARCHAR(20),
    colonia                 VARCHAR(255),
    municipio               VARCHAR(255),
    estado                  VARCHAR(100),
    codigo_postal           VARCHAR(5),
    -- Contacto
    telefono                VARCHAR(20),
    email_contacto          VARCHAR(255),
    -- Estado
    activo                  BOOLEAN NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contribuyentes_rfc ON contribuyentes(rfc);
CREATE INDEX idx_contribuyentes_usuario ON contribuyentes(usuario_id);

-- ============================================================
-- TABLA: contribuyente_fisica
-- ============================================================
CREATE TABLE contribuyente_fisica (
    contribuyente_id        INTEGER PRIMARY KEY REFERENCES contribuyentes(id) ON DELETE CASCADE,
    curp                    VARCHAR(18),
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_contribuyente_fisica_curp ON contribuyente_fisica(curp) WHERE curp IS NOT NULL;

-- ============================================================
-- TABLA: contribuyente_moral
-- ============================================================
CREATE TABLE contribuyente_moral (
    contribuyente_id        INTEGER PRIMARY KEY REFERENCES contribuyentes(id) ON DELETE CASCADE,
    representante_legal     VARCHAR(400),
    rfc_representante       VARCHAR(13),
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contribuyente_moral_rfc_representante ON contribuyente_moral(rfc_representante);

-- ============================================================
-- TABLA: predios
-- ============================================================
CREATE TABLE predios (
    id                      SERIAL PRIMARY KEY,
    clave_catastral         VARCHAR(50) NOT NULL UNIQUE,
    cuenta_predial          VARCHAR(50) NOT NULL UNIQUE,
    nombre_descripcion      VARCHAR(500),
    uso_suelo               VARCHAR(100),
    superficie_total        NUMERIC(12,2),
    superficie_construida   NUMERIC(12,2),
    valor_catastral         NUMERIC(15,2),
    -- Ubicación
    calle                   VARCHAR(255),
    numero_exterior         VARCHAR(20),
    numero_interior         VARCHAR(20),
    colonia                 VARCHAR(255),
    municipio               VARCHAR(255),
    estado                  VARCHAR(100),
    codigo_postal           VARCHAR(5),
    -- Coordenadas opcionales
    latitud                 NUMERIC(10,7),
    longitud                NUMERIC(10,7),
    -- Estado
    activo                  BOOLEAN NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_predios_clave_catastral ON predios(clave_catastral);
CREATE INDEX idx_predios_cuenta_predial ON predios(cuenta_predial);

-- ============================================================
-- TABLA: predio_contribuyente (historial de relaciones)
-- ============================================================
CREATE TABLE predio_contribuyente (
    id                  SERIAL PRIMARY KEY,
    predio_id           INTEGER NOT NULL REFERENCES predios(id) ON DELETE CASCADE,
    contribuyente_id    INTEGER NOT NULL REFERENCES contribuyentes(id) ON DELETE CASCADE,
    fecha_inicio        DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_fin           DATE,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    tipo_relacion       VARCHAR(100) DEFAULT 'propietario',
    observaciones       TEXT,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_predio_contribuyente_predio ON predio_contribuyente(predio_id);
CREATE INDEX idx_predio_contribuyente_contribuyente ON predio_contribuyente(contribuyente_id);
CREATE INDEX idx_predio_contribuyente_activo ON predio_contribuyente(activo);

-- ============================================================
-- TABLA: tipos_tramite
-- ============================================================
CREATE TABLE tipos_tramite (
    id                  SERIAL PRIMARY KEY,
    clave               VARCHAR(50) NOT NULL UNIQUE,
    nombre              VARCHAR(255) NOT NULL,
    descripcion         TEXT,
    dependencia         VARCHAR(255),
    dias_resolucion     SMALLINT DEFAULT 15,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: tipos_documento
-- ============================================================
CREATE TABLE tipos_documento (
    id                      SERIAL PRIMARY KEY,
    clave                   VARCHAR(50) NOT NULL UNIQUE,
    nombre                  VARCHAR(255) NOT NULL,
    descripcion             TEXT,
    reutilizable            BOOLEAN NOT NULL DEFAULT TRUE,
    requiere_vigencia       BOOLEAN NOT NULL DEFAULT FALSE,
    meses_vigencia          SMALLINT,
    extensiones_permitidas  VARCHAR(200) DEFAULT 'pdf,jpg,jpeg,png',
    tamano_maximo_mb        SMALLINT NOT NULL DEFAULT 10,
    activo                  BOOLEAN NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: requisitos_documentales
-- (Define qué documentos necesita cada tipo de trámite)
-- ============================================================
CREATE TABLE requisitos_documentales (
    id                      SERIAL PRIMARY KEY,
    tipo_tramite_id         INTEGER NOT NULL REFERENCES tipos_tramite(id) ON DELETE CASCADE,
    tipo_documento_id       INTEGER NOT NULL REFERENCES tipos_documento(id) ON DELETE CASCADE,
    obligatorio             BOOLEAN NOT NULL DEFAULT TRUE,
    especifico_tramite      BOOLEAN NOT NULL DEFAULT FALSE, -- No puede reutilizarse de otro trámite
    exclusivo_expediente    BOOLEAN NOT NULL DEFAULT FALSE, -- No puede reutilizarse de otro expediente
    descripcion_instruccion TEXT,
    orden_visualizacion     SMALLINT DEFAULT 0,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(tipo_tramite_id, tipo_documento_id)
);

CREATE INDEX idx_requisitos_tipo_tramite ON requisitos_documentales(tipo_tramite_id);
CREATE INDEX idx_requisitos_tipo_documento ON requisitos_documentales(tipo_documento_id);

-- ============================================================
-- TABLA: expedientes
-- ============================================================
CREATE TABLE expedientes (
    id                  SERIAL PRIMARY KEY,
    numero_expediente   VARCHAR(50) NOT NULL UNIQUE,
    contribuyente_id    INTEGER NOT NULL REFERENCES contribuyentes(id),
    predio_id           INTEGER REFERENCES predios(id),
    estado              estado_expediente NOT NULL DEFAULT 'abierto',
    fecha_apertura      DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_cierre        DATE,
    descripcion         TEXT,
    observaciones       TEXT,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_expedientes_contribuyente ON expedientes(contribuyente_id);
CREATE INDEX idx_expedientes_numero ON expedientes(numero_expediente);
CREATE INDEX idx_expedientes_estado ON expedientes(estado);

-- ============================================================
-- TABLA: tramites
-- ============================================================
CREATE TABLE tramites (
    id                  SERIAL PRIMARY KEY,
    numero_tramite      VARCHAR(50) NOT NULL UNIQUE,
    expediente_id       INTEGER NOT NULL REFERENCES expedientes(id) ON DELETE CASCADE,
    tipo_tramite_id     INTEGER NOT NULL REFERENCES tipos_tramite(id),
    estado              estado_tramite NOT NULL DEFAULT 'iniciado',
    fecha_inicio        DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_resolucion    DATE,
    observaciones       TEXT,
    usuario_asignado_id INTEGER REFERENCES usuarios(id),
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_tramites_expediente ON tramites(expediente_id);
CREATE INDEX idx_tramites_tipo ON tramites(tipo_tramite_id);
CREATE INDEX idx_tramites_estado ON tramites(estado);
CREATE INDEX idx_tramites_numero ON tramites(numero_tramite);

-- ============================================================
-- TABLA: tramite_dependencias
-- (DEPENDE_DE — con control de ciclos a nivel aplicación)
-- ============================================================
CREATE TABLE tramite_dependencias (
    id                      SERIAL PRIMARY KEY,
    tramite_id              INTEGER NOT NULL REFERENCES tramites(id) ON DELETE CASCADE,
    depende_de_tramite_id   INTEGER NOT NULL REFERENCES tramites(id) ON DELETE CASCADE,
    descripcion             TEXT,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT no_autodependencia CHECK (tramite_id <> depende_de_tramite_id),
    UNIQUE(tramite_id, depende_de_tramite_id)
);

CREATE INDEX idx_tramite_dep_tramite ON tramite_dependencias(tramite_id);
CREATE INDEX idx_tramite_dep_depende_de ON tramite_dependencias(depende_de_tramite_id);

-- ============================================================
-- TABLA: documentos
-- (Documento con versionado — padre_documento_id forma el árbol)
-- ============================================================
CREATE TABLE documentos (
    id                      SERIAL PRIMARY KEY,
    contribuyente_id        INTEGER NOT NULL REFERENCES contribuyentes(id) ON DELETE CASCADE,
    tipo_documento_id       INTEGER NOT NULL REFERENCES tipos_documento(id),
    -- Árbol de versiones
    padre_documento_id      INTEGER REFERENCES documentos(id),
    version                 SMALLINT NOT NULL DEFAULT 1,
    es_version_activa       BOOLEAN NOT NULL DEFAULT TRUE,
    -- Archivo
    nombre_original         VARCHAR(500),
    nombre_archivo          VARCHAR(500) NOT NULL,
    ruta_archivo            VARCHAR(1000) NOT NULL,
    mime_type               VARCHAR(100),
    tamano_bytes            INTEGER,
    hash_sha256             VARCHAR(64),
    -- Metadatos documentales
    numero_folio            VARCHAR(100),
    fecha_emision           DATE,
    fecha_vigencia          DATE,
    -- Reutilización
    reutilizable            BOOLEAN NOT NULL DEFAULT TRUE,
    exclusivo_expediente_id INTEGER REFERENCES expedientes(id),
    -- Estado
    estado_validacion       estado_validacion NOT NULL DEFAULT 'pendiente',
    observaciones_validacion TEXT,
    -- Timestamps
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_documentos_contribuyente ON documentos(contribuyente_id);
CREATE INDEX idx_documentos_tipo ON documentos(tipo_documento_id);
CREATE INDEX idx_documentos_padre ON documentos(padre_documento_id);
CREATE INDEX idx_documentos_activo ON documentos(es_version_activa);
CREATE INDEX idx_documentos_estado ON documentos(estado_validacion);
CREATE INDEX idx_documentos_exclusivo ON documentos(exclusivo_expediente_id);

-- ============================================================
-- TABLA: documento_expediente
-- (Vinculación de documentos a expedientes/trámites)
-- ============================================================
CREATE TABLE documento_expediente (
    id              SERIAL PRIMARY KEY,
    documento_id    INTEGER NOT NULL REFERENCES documentos(id) ON DELETE CASCADE,
    expediente_id   INTEGER NOT NULL REFERENCES expedientes(id) ON DELETE CASCADE,
    tramite_id      INTEGER REFERENCES tramites(id),
    uso             uso_documento NOT NULL DEFAULT 'requisito',
    reutilizado     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(documento_id, expediente_id, tramite_id, uso)
);

CREATE INDEX idx_doc_exp_documento ON documento_expediente(documento_id);
CREATE INDEX idx_doc_exp_expediente ON documento_expediente(expediente_id);
CREATE INDEX idx_doc_exp_tramite ON documento_expediente(tramite_id);

-- ============================================================
-- TABLA: validaciones_documento
-- (Historial de validaciones por documento)
-- ============================================================
CREATE TABLE validaciones_documento (
    id                  SERIAL PRIMARY KEY,
    documento_id        INTEGER NOT NULL REFERENCES documentos(id) ON DELETE CASCADE,
    usuario_validador_id INTEGER NOT NULL REFERENCES usuarios(id),
    resultado           estado_validacion NOT NULL,
    observaciones       TEXT,
    fecha               TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_validaciones_documento ON validaciones_documento(documento_id);
CREATE INDEX idx_validaciones_usuario ON validaciones_documento(usuario_validador_id);

-- ============================================================
-- TABLA: periodos_fiscales
-- ============================================================
CREATE TABLE periodos_fiscales (
    id              SERIAL PRIMARY KEY,
    anio            SMALLINT NOT NULL,
    descripcion     VARCHAR(255) NOT NULL,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(anio, descripcion)
);

-- ============================================================
-- TABLA: obligaciones_fiscales
-- ============================================================
CREATE TABLE obligaciones_fiscales (
    id                  SERIAL PRIMARY KEY,
    contribuyente_id    INTEGER NOT NULL REFERENCES contribuyentes(id),
    predio_id           INTEGER REFERENCES predios(id),
    tipo                VARCHAR(100) NOT NULL,
    descripcion         TEXT,
    monto_base          NUMERIC(12,2) NOT NULL DEFAULT 0,
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_obligaciones_contribuyente ON obligaciones_fiscales(contribuyente_id);
CREATE INDEX idx_obligaciones_predio ON obligaciones_fiscales(predio_id);

-- ============================================================
-- TABLA: obligacion_periodo
-- ============================================================
CREATE TABLE obligacion_periodo (
    id              SERIAL PRIMARY KEY,
    obligacion_id   INTEGER NOT NULL REFERENCES obligaciones_fiscales(id) ON DELETE CASCADE,
    periodo_id      INTEGER NOT NULL REFERENCES periodos_fiscales(id),
    monto           NUMERIC(12,2) NOT NULL,
    monto_recargos  NUMERIC(12,2) NOT NULL DEFAULT 0,
    pagado          BOOLEAN NOT NULL DEFAULT FALSE,
    monto_pagado    NUMERIC(12,2) NOT NULL DEFAULT 0,
    fecha_limite    DATE,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(obligacion_id, periodo_id)
);

CREATE INDEX idx_obligacion_periodo_oblig ON obligacion_periodo(obligacion_id);
CREATE INDEX idx_obligacion_periodo_periodo ON obligacion_periodo(periodo_id);

-- ============================================================
-- TABLA: pagos
-- ============================================================
CREATE TABLE pagos (
    id                  SERIAL PRIMARY KEY,
    contribuyente_id    INTEGER NOT NULL REFERENCES contribuyentes(id),
    monto               NUMERIC(12,2) NOT NULL,
    referencia          VARCHAR(100) UNIQUE,
    fecha               DATE NOT NULL DEFAULT CURRENT_DATE,
    metodo_pago         VARCHAR(50) NOT NULL DEFAULT 'efectivo',
    observaciones       TEXT,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_pagos_contribuyente ON pagos(contribuyente_id);
CREATE INDEX idx_pagos_fecha ON pagos(fecha);

-- ============================================================
-- TABLA: pago_aplicacion
-- ============================================================
CREATE TABLE pago_aplicacion (
    id                      SERIAL PRIMARY KEY,
    pago_id                 INTEGER NOT NULL REFERENCES pagos(id) ON DELETE CASCADE,
    obligacion_periodo_id   INTEGER NOT NULL REFERENCES obligacion_periodo(id),
    monto_aplicado          NUMERIC(12,2) NOT NULL,
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(pago_id, obligacion_periodo_id)
);

CREATE INDEX idx_pago_aplic_pago ON pago_aplicacion(pago_id);
CREATE INDEX idx_pago_aplic_obligacion ON pago_aplicacion(obligacion_periodo_id);

-- ============================================================
-- TABLA: inspecciones
-- ============================================================
CREATE TABLE inspecciones (
    id                  SERIAL PRIMARY KEY,
    expediente_id       INTEGER NOT NULL REFERENCES expedientes(id),
    tramite_id          INTEGER REFERENCES tramites(id),
    tipo                VARCHAR(100) NOT NULL,
    programada_para     TIMESTAMP WITH TIME ZONE,
    realizada_en        TIMESTAMP WITH TIME ZONE,
    inspector_id        INTEGER REFERENCES usuarios(id),
    estado              estado_inspeccion NOT NULL DEFAULT 'pendiente',
    resultado           TEXT,
    observaciones       TEXT,
    created_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_inspecciones_expediente ON inspecciones(expediente_id);
CREATE INDEX idx_inspecciones_tramite ON inspecciones(tramite_id);

-- ============================================================
-- TABLA: inspeccion_documentos
-- ============================================================
CREATE TABLE inspeccion_documentos (
    id              SERIAL PRIMARY KEY,
    inspeccion_id   INTEGER NOT NULL REFERENCES inspecciones(id) ON DELETE CASCADE,
    documento_id    INTEGER NOT NULL REFERENCES documentos(id) ON DELETE CASCADE,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE(inspeccion_id, documento_id)
);

-- ============================================================
-- TABLA: historial_expediente
-- ============================================================
CREATE TABLE historial_expediente (
    id              SERIAL PRIMARY KEY,
    expediente_id   INTEGER NOT NULL REFERENCES expedientes(id) ON DELETE CASCADE,
    evento          VARCHAR(100) NOT NULL,
    descripcion     TEXT,
    usuario_id      INTEGER REFERENCES usuarios(id),
    metadatos       JSONB,
    fecha           TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_historial_expediente ON historial_expediente(expediente_id);
CREATE INDEX idx_historial_fecha ON historial_expediente(fecha);

-- ============================================================
-- SECUENCIAS DE NEGOCIO
-- Generación concurrente de números consecutivos para
-- expedientes (EXP-YYYY-NNNNNN) y trámites (TRM-YYYY-NNNNNN)
-- Usadas por ExpedienteService.php vía nextval()
-- ============================================================
CREATE SEQUENCE IF NOT EXISTS seq_expediente_num START WITH 1;
CREATE SEQUENCE IF NOT EXISTS seq_tramite_num START WITH 1;

-- ============================================================
-- FUNCIÓN: updated_at automático
-- ============================================================
CREATE OR REPLACE FUNCTION actualizar_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers updated_at
CREATE TRIGGER trg_usuarios_updated_at BEFORE UPDATE ON usuarios FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_contribuyentes_updated_at BEFORE UPDATE ON contribuyentes FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_contribuyente_fisica_updated_at BEFORE UPDATE ON contribuyente_fisica FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_contribuyente_moral_updated_at BEFORE UPDATE ON contribuyente_moral FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_predios_updated_at BEFORE UPDATE ON predios FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_tipos_tramite_updated_at BEFORE UPDATE ON tipos_tramite FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_tipos_documento_updated_at BEFORE UPDATE ON tipos_documento FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_expedientes_updated_at BEFORE UPDATE ON expedientes FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_tramites_updated_at BEFORE UPDATE ON tramites FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_documentos_updated_at BEFORE UPDATE ON documentos FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_obligaciones_updated_at BEFORE UPDATE ON obligaciones_fiscales FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_obligacion_periodo_updated_at BEFORE UPDATE ON obligacion_periodo FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();
CREATE TRIGGER trg_inspecciones_updated_at BEFORE UPDATE ON inspecciones FOR EACH ROW EXECUTE FUNCTION actualizar_updated_at();

-- ============================================================
-- FUNCIÓN: versionar documento
-- Cuando se inserta un nuevo documento, desactiva versiones anteriores del mismo árbol
-- ============================================================
CREATE OR REPLACE FUNCTION gestionar_version_documento()
RETURNS TRIGGER AS $$
DECLARE
    raiz_id INTEGER;
BEGIN
    IF NEW.padre_documento_id IS NOT NULL THEN
        -- Encontrar la raíz del árbol
        WITH RECURSIVE arbol AS (
            SELECT id, padre_documento_id FROM documentos WHERE id = NEW.padre_documento_id
            UNION ALL
            SELECT d.id, d.padre_documento_id FROM documentos d INNER JOIN arbol a ON d.id = a.padre_documento_id
        )
        SELECT id INTO raiz_id FROM arbol WHERE padre_documento_id IS NULL LIMIT 1;

        IF raiz_id IS NULL THEN
            raiz_id := NEW.padre_documento_id;
        END IF;

        -- Desactivar todas las versiones del árbol excepto el nuevo
        UPDATE documentos
        SET es_version_activa = FALSE
        WHERE id <> NEW.id
          AND (id = raiz_id OR padre_documento_id IN (
              WITH RECURSIVE descendants AS (
                  SELECT id FROM documentos WHERE id = raiz_id
                  UNION ALL
                  SELECT d.id FROM documentos d INNER JOIN descendants descendants_tree ON d.padre_documento_id = descendants_tree.id
              )
              SELECT id FROM descendants
          ));
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_versionar_documento
    AFTER INSERT ON documentos
    FOR EACH ROW
    EXECUTE FUNCTION gestionar_version_documento();

-- ============================================================
-- FUNCIÓN: generar número de expediente automático
-- ============================================================
CREATE OR REPLACE FUNCTION generar_numero_expediente()
RETURNS VARCHAR AS $$
DECLARE
    anio_actual VARCHAR(4);
    consecutivo INTEGER;
BEGIN
    anio_actual := TO_CHAR(NOW(), 'YYYY');
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_expediente FROM 10) AS INTEGER)), 0) + 1
    INTO consecutivo
    FROM expedientes
    WHERE numero_expediente LIKE 'EXP-' || anio_actual || '-%';
    RETURN 'EXP-' || anio_actual || '-' || LPAD(consecutivo::TEXT, 6, '0');
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- FUNCIÓN: generar número de trámite automático
-- ============================================================
CREATE OR REPLACE FUNCTION generar_numero_tramite()
RETURNS VARCHAR AS $$
DECLARE
    anio_actual VARCHAR(4);
    consecutivo INTEGER;
BEGIN
    anio_actual := TO_CHAR(NOW(), 'YYYY');
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_tramite FROM 8) AS INTEGER)), 0) + 1
    INTO consecutivo
    FROM tramites
    WHERE numero_tramite LIKE 'TRM-' || anio_actual || '-%';
    RETURN 'TRM-' || anio_actual || '-' || LPAD(consecutivo::TEXT, 6, '0');
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- VISTA: v_contribuyentes_detalle
-- Compatibilidad de lectura para persona fisica y moral
-- ============================================================
CREATE OR REPLACE VIEW v_contribuyentes_detalle AS
SELECT
    c.*,
    cf.curp,
    CASE WHEN c.tipo_persona = 'moral' THEN c.nombre_completo ELSE NULL END AS razon_social,
    cm.representante_legal,
    cm.rfc_representante,
    c.nombre_completo AS nombre_mostrado
FROM contribuyentes c
LEFT JOIN contribuyente_fisica cf ON cf.contribuyente_id = c.id
LEFT JOIN contribuyente_moral cm ON cm.contribuyente_id = c.id;
-- ============================================================
-- VISTA: saldo_contribuyente
-- ============================================================
CREATE OR REPLACE VIEW v_saldo_contribuyente AS
SELECT
    c.id                                AS contribuyente_id,
    c.nombre_completo,
    c.rfc,
    COUNT(DISTINCT op.id)               AS total_obligaciones,
    COALESCE(SUM(op.monto + op.monto_recargos), 0)          AS total_cargo,
    COALESCE(SUM(op.monto_pagado), 0)   AS total_pagado,
    COALESCE(SUM((op.monto + op.monto_recargos) - op.monto_pagado), 0) AS saldo_pendiente,
    COUNT(DISTINCT CASE WHEN op.pagado THEN op.id END)      AS obligaciones_pagadas,
    COUNT(DISTINCT CASE WHEN NOT op.pagado THEN op.id END)  AS obligaciones_pendientes
FROM contribuyentes c
LEFT JOIN obligaciones_fiscales of ON of.contribuyente_id = c.id AND of.activo = TRUE
LEFT JOIN obligacion_periodo op ON op.obligacion_id = of.id
GROUP BY c.id, c.nombre_completo, c.rfc;

-- ============================================================
-- VISTA: v_documentos_contribuyente
-- (Con estado de vigencia calculado)
-- ============================================================
CREATE OR REPLACE VIEW v_documentos_contribuyente AS
SELECT
    d.id,
    d.contribuyente_id,
    d.tipo_documento_id,
    td.nombre                   AS tipo_documento_nombre,
    td.reutilizable             AS tipo_reutilizable,
    td.requiere_vigencia,
    td.meses_vigencia,
    d.numero_folio,
    d.fecha_emision,
    d.fecha_vigencia,
    d.nombre_original,
    d.nombre_archivo,
    d.ruta_archivo,
    d.estado_validacion,
    d.reutilizable              AS doc_reutilizable,
    d.exclusivo_expediente_id,
    d.es_version_activa,
    d.version,
    d.padre_documento_id,
    d.created_at,
    CASE
        WHEN td.requiere_vigencia AND d.fecha_vigencia < CURRENT_DATE THEN 'vencido'
        WHEN td.requiere_vigencia AND d.fecha_vigencia >= CURRENT_DATE THEN 'vigente'
        ELSE 'sin_vigencia'
    END AS estado_vigencia
FROM documentos d
INNER JOIN tipos_documento td ON td.id = d.tipo_documento_id
WHERE d.es_version_activa = TRUE;

-- ============================================================
-- VISTA: v_requisitos_tramite_contribuyente
-- (Para calcular clasificación documental)
-- ============================================================
CREATE OR REPLACE VIEW v_requisitos_tramite AS
SELECT
    rd.id                       AS requisito_id,
    rd.tipo_tramite_id,
    tt.nombre                   AS tipo_tramite_nombre,
    rd.tipo_documento_id,
    td.nombre                   AS tipo_documento_nombre,
    td.reutilizable             AS tipo_reutilizable,
    td.requiere_vigencia,
    td.meses_vigencia,
    rd.obligatorio,
    rd.especifico_tramite,
    rd.exclusivo_expediente,
    rd.descripcion_instruccion,
    rd.orden_visualizacion
FROM requisitos_documentales rd
INNER JOIN tipos_tramite tt ON tt.id = rd.tipo_tramite_id
INNER JOIN tipos_documento td ON td.id = rd.tipo_documento_id
WHERE tt.activo = TRUE AND td.activo = TRUE;



