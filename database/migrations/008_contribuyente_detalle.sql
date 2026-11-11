BEGIN;

CREATE TABLE IF NOT EXISTS contribuyente_fisica (
    contribuyente_id        INTEGER PRIMARY KEY REFERENCES contribuyentes(id) ON DELETE CASCADE,
    curp                    VARCHAR(18),
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS contribuyente_moral (
    contribuyente_id        INTEGER PRIMARY KEY REFERENCES contribuyentes(id) ON DELETE CASCADE,
    representante_legal     VARCHAR(400),
    rfc_representante       VARCHAR(13),
    created_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_contribuyente_fisica_curp
    ON contribuyente_fisica(curp)
    WHERE curp IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_contribuyente_moral_rfc_representante
    ON contribuyente_moral(rfc_representante);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'contribuyentes'
          AND column_name = 'curp'
    ) THEN
        INSERT INTO contribuyente_fisica (contribuyente_id, curp)
        SELECT id, NULLIF(curp, '')
        FROM contribuyentes
        WHERE tipo_persona = 'fisica'
        ON CONFLICT (contribuyente_id)
        DO UPDATE SET curp = EXCLUDED.curp,
                      updated_at = NOW();
    END IF;

    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'contribuyentes'
          AND column_name = 'representante_legal'
    ) THEN
        INSERT INTO contribuyente_moral (contribuyente_id, representante_legal, rfc_representante)
        SELECT id,
               NULLIF(representante_legal, ''),
               NULLIF(rfc_representante, '')
        FROM contribuyentes
        WHERE tipo_persona = 'moral'
        ON CONFLICT (contribuyente_id)
        DO UPDATE SET representante_legal = EXCLUDED.representante_legal,
                      rfc_representante = EXCLUDED.rfc_representante,
                      updated_at = NOW();
    END IF;
END $$;

ALTER TABLE contribuyentes DROP COLUMN IF EXISTS curp;
ALTER TABLE contribuyentes DROP COLUMN IF EXISTS razon_social;
ALTER TABLE contribuyentes DROP COLUMN IF EXISTS representante_legal;
ALTER TABLE contribuyentes DROP COLUMN IF EXISTS rfc_representante;

DROP TRIGGER IF EXISTS trg_contribuyente_fisica_updated_at ON contribuyente_fisica;
CREATE TRIGGER trg_contribuyente_fisica_updated_at
    BEFORE UPDATE ON contribuyente_fisica
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

DROP TRIGGER IF EXISTS trg_contribuyente_moral_updated_at ON contribuyente_moral;
CREATE TRIGGER trg_contribuyente_moral_updated_at
    BEFORE UPDATE ON contribuyente_moral
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_updated_at();

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

COMMIT;