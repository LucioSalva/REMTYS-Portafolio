-- ============================================================
-- REMTYS — Migración 007: Tabla system_events
-- Consola de monitoreo para supergod
-- ============================================================

-- Extensión para búsqueda de texto (debe ir primero)
CREATE EXTENSION IF NOT EXISTS pg_trgm;

CREATE TABLE IF NOT EXISTS system_events (
    id              BIGSERIAL PRIMARY KEY,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    nivel           VARCHAR(10)  NOT NULL,      -- INFO, SUCCESS, WARNING, ERROR, CRITICAL
    categoria       VARCHAR(30)  NOT NULL,      -- AUTH, SEGURIDAD, ERROR, ADMIN, DOCUMENTO, PAGO, MAIL, SISTEMA
    evento          VARCHAR(100) NOT NULL,       -- LOGIN_EXITOSO, LOGIN_FALLIDO, USER_REGISTERED, etc.
    descripcion     TEXT,                        -- Mensaje legible
    ip              VARCHAR(45),                 -- IPv4 o IPv6
    usuario_id      INTEGER,                     -- Sin FK: conservar eventos si el usuario se elimina
    usuario_email   VARCHAR(255),                -- Email (enmascarado por el logger)
    rol             VARCHAR(20),                 -- Rol al momento del evento
    modulo          VARCHAR(50),                 -- Módulo activo (auth, admin, contribuyente, supergod)
    accion          VARCHAR(50),                 -- Acción del router
    metodo_http     VARCHAR(10),                 -- GET, POST, etc.
    ruta            TEXT,                        -- URI completa
    user_agent      VARCHAR(255),                -- Navegador/cliente
    contexto        JSONB DEFAULT '{}'::jsonb    -- Detalles técnicos (sanitizados, sin secretos)
);

-- Índices para consultas frecuentes de la consola
CREATE INDEX idx_sysevents_created     ON system_events (created_at DESC);
CREATE INDEX idx_sysevents_nivel       ON system_events (nivel);
CREATE INDEX idx_sysevents_categoria   ON system_events (categoria);
CREATE INDEX idx_sysevents_evento      ON system_events (evento);
CREATE INDEX idx_sysevents_usuario     ON system_events (usuario_id);
CREATE INDEX idx_sysevents_ip          ON system_events (ip);

-- Índice compuesto para filtros combinados (los más usados en la consola)
CREATE INDEX idx_sysevents_cat_nivel_ts ON system_events (categoria, nivel, created_at DESC);

-- Índice GIN para búsqueda en contexto JSONB
CREATE INDEX idx_sysevents_contexto    ON system_events USING GIN (contexto);

-- Índice para búsqueda de texto en descripción
CREATE INDEX idx_sysevents_desc_trgm   ON system_events USING GIN (descripcion gin_trgm_ops);

-- Nota: el índice trgm usa la extensión pg_trgm creada al inicio.
