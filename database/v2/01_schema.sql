-- =========================================================================
-- remtys_db  |  PostgreSQL 17
-- Sistema: Repositorio documental único por contribuyente para trámites
--          municipales de Tesorería / Catastro.
-- Idea rectora: el contribuyente sube un documento una sola vez y se reutiliza
--               en todos los trámites cuyos requisitos lo admitan, mientras
--               esté vigente.
-- =========================================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;

-- =========================================================================
-- 1. CATÁLOGOS Y ENUMS
-- =========================================================================

CREATE TYPE tipo_persona       AS ENUM ('FISICA','JURIDICA','INSTITUCION_PUBLICA');
CREATE TYPE doc_scope          AS ENUM ('CONTRIBUYENTE','INMUEBLE','SOLICITUD');
CREATE TYPE doc_estado         AS ENUM ('VIGENTE','VENCIDO','RECHAZADO','REEMPLAZADO');
CREATE TYPE solicitud_estado   AS ENUM ('BORRADOR','DOCUMENTOS_PENDIENTES','EN_REVISION','OBSERVADA','APROBADA','RECHAZADA','FINALIZADA','CANCELADA');
CREATE TYPE sol_doc_estado     AS ENUM ('PENDIENTE','ENTREGADO','VALIDADO','OBSERVADO','RECHAZADO');
CREATE TYPE area_responsable   AS ENUM ('CATASTRO','IMPUESTO_PREDIAL','TRASLADO_DOMINIO','EJECUCION_FISCAL','TESORERIA_GENERAL');

-- =========================================================================
-- 2. CONTRIBUYENTES  (quién sube los documentos)
-- =========================================================================

CREATE TABLE contribuyente (
    contribuyente_id   uuid        PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo_persona       tipo_persona NOT NULL,
    -- Persona física
    curp               varchar(18)  UNIQUE,
    -- Común (física o moral)
    rfc                varchar(13)  UNIQUE,
    nombre_razon_social varchar(255) NOT NULL,
    apellido_paterno   varchar(120),
    apellido_materno   varchar(120),
    -- Persona jurídica
    acta_constitutiva_fecha date,
    -- Contacto
    email              citext       UNIQUE,
    telefono           varchar(20),
    -- Domicilio fiscal / notificaciones
    calle              varchar(255),
    numero_ext         varchar(20),
    numero_int         varchar(20),
    colonia            varchar(120),
    municipio          varchar(120),
    estado             varchar(120) DEFAULT 'México',
    codigo_postal      varchar(10),
    -- Auditoría
    creado_en          timestamptz  NOT NULL DEFAULT now(),
    actualizado_en     timestamptz  NOT NULL DEFAULT now(),
    CONSTRAINT ck_contrib_fisica_tiene_curp
        CHECK (tipo_persona <> 'FISICA' OR curp IS NOT NULL),
    CONSTRAINT ck_contrib_jur_tiene_razon
        CHECK (tipo_persona = 'FISICA' OR nombre_razon_social IS NOT NULL)
);

CREATE INDEX ix_contribuyente_tipo     ON contribuyente(tipo_persona);
CREATE INDEX ix_contribuyente_nombre   ON contribuyente(nombre_razon_social);

-- Representantes / apoderados de una persona jurídica o física
CREATE TABLE representante (
    representante_id   uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    contribuyente_id   uuid NOT NULL REFERENCES contribuyente(contribuyente_id) ON DELETE CASCADE,
    nombre_completo    varchar(255) NOT NULL,
    curp               varchar(18),
    tipo_representacion varchar(40) NOT NULL,  -- APODERADO, REPRESENTANTE_LEGAL, GESTOR, CARTA_PODER
    vigente_desde      date NOT NULL DEFAULT CURRENT_DATE,
    vigente_hasta      date,
    creado_en          timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX ix_representante_contrib ON representante(contribuyente_id);

-- =========================================================================
-- 3. INMUEBLES  (muchos documentos son del inmueble, no del contribuyente)
-- =========================================================================

CREATE TABLE inmueble (
    inmueble_id        uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    clave_catastral    varchar(40)  UNIQUE,   -- NULL si aún no se asigna
    calle              varchar(255),
    numero_ext         varchar(20),
    numero_int         varchar(20),
    colonia            varchar(120),
    localidad          varchar(120),
    municipio          varchar(120) NOT NULL DEFAULT 'Municipio Demo',
    estado             varchar(120) NOT NULL DEFAULT 'México',
    codigo_postal      varchar(10),
    superficie_terreno_m2 numeric(12,2),
    superficie_construccion_m2 numeric(12,2),
    valor_catastral    numeric(14,2),
    creado_en          timestamptz NOT NULL DEFAULT now(),
    actualizado_en     timestamptz NOT NULL DEFAULT now()
);

-- Relación contribuyente <-> inmueble (titularidad / posesión)
CREATE TABLE inmueble_contribuyente (
    inmueble_id        uuid NOT NULL REFERENCES inmueble(inmueble_id) ON DELETE CASCADE,
    contribuyente_id   uuid NOT NULL REFERENCES contribuyente(contribuyente_id) ON DELETE CASCADE,
    tipo_vinculo       varchar(30) NOT NULL DEFAULT 'PROPIETARIO', -- PROPIETARIO, POSEEDOR, COPROPIETARIO
    porcentaje         numeric(5,2) DEFAULT 100.00 CHECK (porcentaje > 0 AND porcentaje <= 100),
    vigente_desde      date NOT NULL DEFAULT CURRENT_DATE,
    vigente_hasta      date,
    PRIMARY KEY (inmueble_id, contribuyente_id, tipo_vinculo)
);

-- =========================================================================
-- 4. CATÁLOGO DE TIPOS DE DOCUMENTO  (eje del sistema)
-- =========================================================================

CREATE TABLE tipo_documento (
    tipo_documento_id  smallint PRIMARY KEY,           -- ID estable/publicable
    codigo             varchar(60) UNIQUE NOT NULL,    -- identificador simbólico
    nombre             varchar(200) NOT NULL,          -- nombre canónico
    scope              doc_scope NOT NULL,             -- a quién/qué pertenece
    vigencia_meses     int,                             -- NULL = permanente
    vigencia_tipo      varchar(30) NOT NULL DEFAULT 'PERMANENTE',
                       -- PERMANENTE, EJERCICIO_FISCAL, MESES, UNICO_USO, VENCIMIENTO_DOC
    descripcion        text,
    notas              text,
    activo             boolean NOT NULL DEFAULT true,
    CONSTRAINT ck_vigencia CHECK (
        (vigencia_tipo = 'MESES' AND vigencia_meses IS NOT NULL)
     OR (vigencia_tipo <> 'MESES')
    )
);

-- Aliases / sinónimos que aparecen en las fichas (para búsqueda y normalización)
CREATE TABLE tipo_documento_alias (
    tipo_documento_id  smallint NOT NULL REFERENCES tipo_documento(tipo_documento_id) ON DELETE CASCADE,
    alias              varchar(200) NOT NULL,
    PRIMARY KEY (tipo_documento_id, alias)
);

-- Documentos que son "equivalentes" (cualquiera de los del grupo satisface el requisito)
-- Ej.: Escritura / Testimonio Notarial / Sentencia / Contrato compraventa / Cesión de derechos
-- todos son "documento que acredita la propiedad".
CREATE TABLE grupo_documento (
    grupo_id           smallint PRIMARY KEY,
    codigo             varchar(60) UNIQUE NOT NULL,
    nombre             varchar(200) NOT NULL,
    descripcion        text
);

CREATE TABLE grupo_documento_tipo (
    grupo_id           smallint NOT NULL REFERENCES grupo_documento(grupo_id) ON DELETE CASCADE,
    tipo_documento_id  smallint NOT NULL REFERENCES tipo_documento(tipo_documento_id) ON DELETE CASCADE,
    PRIMARY KEY (grupo_id, tipo_documento_id)
);

-- =========================================================================
-- 5. DOCUMENTOS SUBIDOS POR EL CONTRIBUYENTE  (tabla nuclear: reutilización)
-- =========================================================================

CREATE TABLE documento (
    documento_id       uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    contribuyente_id   uuid NOT NULL REFERENCES contribuyente(contribuyente_id) ON DELETE CASCADE,
    inmueble_id        uuid REFERENCES inmueble(inmueble_id) ON DELETE SET NULL,
    tipo_documento_id  smallint NOT NULL REFERENCES tipo_documento(tipo_documento_id),
    -- Archivo
    nombre_original    varchar(255) NOT NULL,
    mime_type          varchar(120) NOT NULL,
    tamano_bytes       bigint NOT NULL CHECK (tamano_bytes > 0),
    sha256             char(64)  NOT NULL,   -- hash para deduplicación
    almacenamiento_uri text      NOT NULL,   -- s3://bucket/..  ó  file:///...
    -- Vigencia
    vigente_desde      date NOT NULL DEFAULT CURRENT_DATE,
    vigente_hasta      date,                 -- NULL = permanente
    estado             doc_estado NOT NULL DEFAULT 'VIGENTE',
    -- Validación humana
    validado_por       uuid,                 -- FK a empleado (no modelado en v1)
    validado_en        timestamptz,
    motivo_rechazo     text,
    -- Reemplazo (si el doc fue sustituido por otro)
    reemplazado_por    uuid REFERENCES documento(documento_id),
    -- Auditoría
    creado_en          timestamptz NOT NULL DEFAULT now(),
    actualizado_en     timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT ck_sha256_len CHECK (char_length(sha256) = 64),
    CONSTRAINT uq_doc_contrib_sha UNIQUE (contribuyente_id, sha256)
);

CREATE INDEX ix_documento_contrib_tipo ON documento(contribuyente_id, tipo_documento_id) WHERE estado = 'VIGENTE';
CREATE INDEX ix_documento_inmueble_tipo ON documento(inmueble_id, tipo_documento_id)   WHERE estado = 'VIGENTE';
CREATE INDEX ix_documento_vigencia ON documento(vigente_hasta) WHERE vigente_hasta IS NOT NULL;
CREATE INDEX ix_documento_sha256 ON documento(sha256);

-- Regla: si el tipo_documento es scope=INMUEBLE, el documento debe tener inmueble_id.
CREATE OR REPLACE FUNCTION trg_documento_scope_check() RETURNS trigger AS $$
DECLARE v_scope doc_scope;
BEGIN
    SELECT scope INTO v_scope FROM tipo_documento WHERE tipo_documento_id = NEW.tipo_documento_id;
    IF v_scope = 'INMUEBLE' AND NEW.inmueble_id IS NULL THEN
        RAISE EXCEPTION 'El tipo_documento % tiene scope INMUEBLE y requiere inmueble_id', NEW.tipo_documento_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER documento_scope_chk
    BEFORE INSERT OR UPDATE ON documento
    FOR EACH ROW EXECUTE FUNCTION trg_documento_scope_check();

-- =========================================================================
-- 6. CATÁLOGO DE TRÁMITES
-- =========================================================================

CREATE TABLE tramite (
    tramite_id         smallint PRIMARY KEY,
    codigo             varchar(80) UNIQUE NOT NULL,
    nombre             varchar(200) NOT NULL,
    tipo               varchar(20) NOT NULL CHECK (tipo IN ('TRAMITE','SERVICIO')),
    area               area_responsable NOT NULL,
    descripcion        text,
    documento_a_obtener varchar(255),
    vigencia_resultado varchar(120),
    fundamento_legal   text,
    casos_aplicables   text,
    sujeto_inspeccion  boolean NOT NULL DEFAULT false,
    se_realiza_en_linea boolean NOT NULL DEFAULT false,
    direccion_web      varchar(255),
    tiempo_max_respuesta varchar(80),
    costo_descripcion  varchar(255),
    costo_monto        numeric(10,2),        -- monto fijo, NULL si variable/gratuito
    costo_es_variable  boolean NOT NULL DEFAULT false,
    costo_es_gratuito  boolean NOT NULL DEFAULT false,
    pago_efectivo      boolean NOT NULL DEFAULT true,
    pago_tdc           boolean NOT NULL DEFAULT false,
    pago_tdb           boolean NOT NULL DEFAULT false,
    pago_portal        boolean NOT NULL DEFAULT false,
    fecha_actualizacion date,
    activo             boolean NOT NULL DEFAULT true
);

-- Trámites relacionados (un trámite puede ser prerrequisito de otro)
CREATE TABLE tramite_relacion (
    tramite_id         smallint NOT NULL REFERENCES tramite(tramite_id) ON DELETE CASCADE,
    tramite_relacionado_id smallint NOT NULL REFERENCES tramite(tramite_id) ON DELETE CASCADE,
    relacion           varchar(30) NOT NULL DEFAULT 'RELACIONADO', -- RELACIONADO, PRERREQUISITO
    PRIMARY KEY (tramite_id, tramite_relacionado_id),
    CHECK (tramite_id <> tramite_relacionado_id)
);

-- =========================================================================
-- 7. MATRIZ TRÁMITE × REQUISITO  (corazón de la reutilización)
-- =========================================================================
-- Cada fila = un requisito documental de un trámite, para un tipo de persona.
-- Puede exigir un tipo_documento concreto, o un grupo (cualquiera del grupo vale).

CREATE TABLE tramite_requisito (
    tramite_requisito_id serial PRIMARY KEY,
    tramite_id         smallint NOT NULL REFERENCES tramite(tramite_id) ON DELETE CASCADE,
    aplica_a           tipo_persona NOT NULL,
    orden              smallint NOT NULL,
    -- Exactamente uno de estos dos: tipo_documento_id XOR grupo_id
    tipo_documento_id  smallint REFERENCES tipo_documento(tipo_documento_id),
    grupo_id           smallint REFERENCES grupo_documento(grupo_id),
    -- Reglas de presentación
    original_requerido boolean NOT NULL DEFAULT false,
    copias_requeridas  smallint NOT NULL DEFAULT 0,
    obligatorio        boolean NOT NULL DEFAULT true,
    condicion          text,                -- "si no es el propietario", etc.
    notas              text,
    CONSTRAINT ck_req_tipo_xor_grupo CHECK (
        (tipo_documento_id IS NOT NULL AND grupo_id IS NULL)
     OR (tipo_documento_id IS NULL AND grupo_id IS NOT NULL)
    ),
    UNIQUE (tramite_id, aplica_a, orden)
);

CREATE INDEX ix_req_tramite_persona ON tramite_requisito(tramite_id, aplica_a);

-- =========================================================================
-- 8. SOLICITUDES  (instancias de trámites iniciadas por un contribuyente)
-- =========================================================================

CREATE TABLE solicitud (
    solicitud_id       uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    folio              varchar(30) UNIQUE NOT NULL,
    tramite_id         smallint NOT NULL REFERENCES tramite(tramite_id),
    contribuyente_id   uuid NOT NULL REFERENCES contribuyente(contribuyente_id),
    inmueble_id        uuid REFERENCES inmueble(inmueble_id),
    aplica_a           tipo_persona NOT NULL,       -- fija qué set de requisitos se usa
    estado             solicitud_estado NOT NULL DEFAULT 'BORRADOR',
    creado_en          timestamptz NOT NULL DEFAULT now(),
    enviada_en         timestamptz,
    finalizada_en      timestamptz,
    resultado_uri      text,                       -- documento final generado
    observaciones      text
);
CREATE INDEX ix_solicitud_contribuyente ON solicitud(contribuyente_id);
CREATE INDEX ix_solicitud_tramite ON solicitud(tramite_id);
CREATE INDEX ix_solicitud_estado ON solicitud(estado);

-- Referencia entre solicitud y documento existente (NO copia: apunta).
CREATE TABLE solicitud_documento (
    solicitud_id       uuid NOT NULL REFERENCES solicitud(solicitud_id) ON DELETE CASCADE,
    tramite_requisito_id int NOT NULL REFERENCES tramite_requisito(tramite_requisito_id),
    documento_id       uuid REFERENCES documento(documento_id),
    estado             sol_doc_estado NOT NULL DEFAULT 'PENDIENTE',
    observacion        text,
    asignado_en        timestamptz,
    validado_por       uuid,
    validado_en        timestamptz,
    PRIMARY KEY (solicitud_id, tramite_requisito_id)
);
CREATE INDEX ix_soldoc_doc ON solicitud_documento(documento_id);

-- =========================================================================
-- 9. VISTAS DE APOYO
-- =========================================================================

-- Para un (solicitud): qué documentos ya satisfacen, qué falta subir.
CREATE OR REPLACE VIEW v_solicitud_checklist AS
SELECT
    s.solicitud_id,
    s.folio,
    s.contribuyente_id,
    s.inmueble_id,
    s.aplica_a,
    r.tramite_requisito_id,
    r.orden,
    r.obligatorio,
    r.original_requerido,
    r.copias_requeridas,
    r.condicion,
    COALESCE(td.nombre, g.nombre) AS requisito,
    CASE WHEN r.tipo_documento_id IS NOT NULL THEN 'TIPO' ELSE 'GRUPO' END AS requisito_clase,
    sd.documento_id,
    sd.estado AS estado_requisito
FROM solicitud s
JOIN tramite_requisito r ON r.tramite_id = s.tramite_id AND r.aplica_a = s.aplica_a
LEFT JOIN tipo_documento td ON td.tipo_documento_id = r.tipo_documento_id
LEFT JOIN grupo_documento g ON g.grupo_id = r.grupo_id
LEFT JOIN solicitud_documento sd
       ON sd.solicitud_id = s.solicitud_id
      AND sd.tramite_requisito_id = r.tramite_requisito_id;

-- Documentos vigentes de un contribuyente (repositorio útil para autocompletar)
CREATE OR REPLACE VIEW v_documentos_reutilizables AS
SELECT d.documento_id, d.contribuyente_id, d.inmueble_id, d.tipo_documento_id,
       td.nombre AS tipo_nombre, td.scope,
       d.vigente_desde, d.vigente_hasta, d.nombre_original, d.sha256
FROM documento d
JOIN tipo_documento td ON td.tipo_documento_id = d.tipo_documento_id
WHERE d.estado = 'VIGENTE'
  AND (d.vigente_hasta IS NULL OR d.vigente_hasta >= CURRENT_DATE);
