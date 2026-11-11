-- REMTYS APP - Migracion email_verificacion
-- Fecha: 2026-04-01

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado_at TIMESTAMP WITH TIME ZONE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificacion_token_hash VARCHAR(64);
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificacion_expires_at TIMESTAMP WITH TIME ZONE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_envio_verificacion_at TIMESTAMP WITH TIME ZONE;

CREATE INDEX IF NOT EXISTS idx_usuarios_email_verificacion_token ON usuarios(email_verificacion_token_hash);

UPDATE usuarios
SET email_verificado = TRUE,
    email_verificado_at = COALESCE(email_verificado_at, NOW())
WHERE email_verificado = FALSE;
