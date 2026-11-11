-- ============================================================
-- REMTYS — Seed: Catálogo de códigos postales y colonias (DEMO)
--
-- Versión de portafolio: muestra reducida y ficticia.
-- En producción esta tabla se carga con el catálogo público de
-- SEPOMEX (Correos de México) filtrado al municipio de operación.
--
-- IDEMPOTENT: Uses ON CONFLICT DO NOTHING.
-- Requires: migration 005_catalogo_cp_colonias.sql
-- ============================================================

BEGIN;

INSERT INTO catalogo_cp_colonia (codigo_postal, colonia) VALUES
('50000', 'Centro'),
('50000', 'Barrio de San Juan'),
('50010', 'Jardines del Valle'),
('50010', 'Lomas Verdes'),
('50020', 'Unidad Habitacional Las Palmas'),
('50050', 'Zona Comercial'),
('50050', 'Ampliación Comercial'),
('50100', 'Parque Industrial'),
('50100', 'Fraccionamiento Industrial Norte'),
('50200', 'Fraccionamiento Las Rosas'),
('50200', 'Residencial Los Pinos'),
('50210', 'Ejido La Esperanza')

ON CONFLICT (codigo_postal, colonia) DO NOTHING;

COMMIT;
