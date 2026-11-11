-- ================================================================
-- ADVERTENCIA: ARCHIVO EXCLUSIVO PARA DESARROLLO LOCAL
-- NO EJECUTAR EN PRODUCCION
-- Las cuentas aqui definidas son de prueba y deben eliminarse
-- o reemplazarse antes de cualquier despliegue productivo.
-- ================================================================

-- ============================================================
-- REMTYS APP — Seeds / Datos de Prueba
-- ============================================================

-- ============================================================
-- TIPOS DE DOCUMENTO
-- ============================================================
INSERT INTO tipos_documento (clave, nombre, descripcion, reutilizable, requiere_vigencia, meses_vigencia, extensiones_permitidas, tamano_maximo_mb) VALUES
('ID_OFICIAL',      'Identificación Oficial Vigente',       'INE, Pasaporte o Cédula Profesional',                  TRUE,  TRUE,  36, 'pdf,jpg,jpeg,png', 10),
('CURP_DOC',        'CURP',                                  'Clave Única de Registro de Población',                  TRUE,  FALSE, NULL, 'pdf,jpg,jpeg,png', 5),
('RFC_DOC',         'Constancia de Situación Fiscal (RFC)',  'Emitida por el SAT con antigüedad máxima 3 meses',     FALSE, TRUE,  3,  'pdf', 5),
('ACTA_NACIMIENTO', 'Acta de Nacimiento',                   'Acta de nacimiento certificada',                       TRUE,  FALSE, NULL, 'pdf,jpg,jpeg,png', 10),
('COMP_DOMICILIO',  'Comprobante de Domicilio',              'Recibo de luz, agua o teléfono, máx 3 meses',         FALSE, TRUE,  3,  'pdf,jpg,jpeg,png', 10),
('ESCR_NOTARIAL',   'Escritura Notarial',                   'Escritura pública del inmueble',                       TRUE,  FALSE, NULL, 'pdf', 20),
('CERT_LIBERTAD',   'Certificado de Libertad de Gravamen',  'Emitido por el Registro Público de la Propiedad',     FALSE, TRUE,  3,  'pdf', 10),
('PLANO_CATASTRAL', 'Plano Catastral del Predio',           'Plano oficial con clave catastral',                    TRUE,  FALSE, NULL, 'pdf,jpg,jpeg,png', 15),
('PAGO_PREDIAL',    'Comprobante de Pago Predial',          'Recibo de pago del año en curso o anterior',          FALSE, TRUE,  12, 'pdf,jpg,jpeg,png', 10),
('CERT_NO_ADEUDO',  'Certificado de No Adeudo Predial',      'Certificado oficial que acredita no tener adeudos prediales', FALSE, TRUE,  3, 'pdf', 5),
('SOLICITUD_TRAM',  'Solicitud de Trámite',                 'Formato de solicitud firmado por el contribuyente',    FALSE, FALSE, NULL, 'pdf', 5),
('MEMORIA_DESC',    'Memoria Descriptiva',                  'Descripción técnica del predio o construcción',        FALSE, FALSE, NULL, 'pdf', 20),
('PLANO_ARQT',      'Planos Arquitectónicos',               'Planos de construcción o remodelación',                FALSE, FALSE, NULL, 'pdf', 30),
('DICT_ESTRUCTURAL','Dictamen Estructural',                 'Dictamen de seguridad estructural emitido por perito', FALSE, TRUE,  24, 'pdf', 10),
('ACTA_CONSTIT',    'Acta Constitutiva',                   'Para personas morales: acta constitutiva notariada',   TRUE,  FALSE, NULL, 'pdf', 20),
('POD_NOTARIAL',    'Poder Notarial del Representante',    'Si aplica representante legal',                        FALSE, TRUE,  12, 'pdf', 10),
('DICTAMEN_USO',    'Dictamen de Uso de Suelo',            'Emitido por la Dirección de Desarrollo Urbano',        FALSE, TRUE,  6,  'pdf', 5),
('FOTO_PREDIO',     'Fotografías del Predio',              'Fotografías actuales del inmueble',                    FALSE, FALSE, NULL, 'jpg,jpeg,png', 15),
('INSP_RESULTADO',  'Resultado de Inspección',             'Documento exclusivo generado por inspección',          FALSE, FALSE, NULL, 'pdf', 10);

-- ============================================================
-- TIPOS DE TRÁMITE
-- ============================================================
INSERT INTO tipos_tramite (clave, nombre, descripcion, dependencia, dias_resolucion) VALUES
('ALTA_PREDIO',       'Alta de Predio',                          'Registro inicial de un predio en el padrón catastral',              'Catastro Municipal',        15),
('MOD_PREDIO',        'Modificación de Datos del Predio',        'Actualización de datos catastrales del predio',                     'Catastro Municipal',        10),
('FUSION',            'Fusión de Predios',                       'Unificación de dos o más predios colindantes',                      'Catastro Municipal',        20),
('SUBDIVISION',       'Subdivisión de Predio',                   'División de un predio en dos o más fracciones',                     'Catastro Municipal',        20),
('LICENCIA_CONST',    'Licencia de Construcción',                'Autorización para ejecutar obras de construcción nueva',            'Obras Públicas',            15),
('LICENCIA_AMPLIACION','Licencia de Ampliación',                 'Autorización para ampliar construcción existente',                  'Obras Públicas',            10),
('LICENCIA_DEMOL',    'Licencia de Demolición',                  'Autorización para demoler total o parcialmente una construcción',   'Obras Públicas',            10),
('CERT_USO_SUELO',    'Certificado de Uso de Suelo',             'Certificado que especifica los usos permitidos del predio',         'Desarrollo Urbano',         5),
('CAMBIO_USO',        'Cambio de Uso de Suelo',                  'Solicitud para cambiar el uso de suelo autorizado',                 'Desarrollo Urbano',         30),
('TRASLADO_DOMINIO',  'Traslado de Dominio',                     'Actualización del propietario en el padrón catastral',              'Catastro Municipal',        10),
('CERT_NO_ADEUDO',    'Certificado de No Adeudo Predial',        'Certificado que acredita no tener adeudos de impuesto predial',     'Tesorería Municipal',       3),
('ALINEAMIENTO',      'Alineamiento y Número Oficial',           'Determinación de límites y número oficial del predio',              'Obras Públicas',            5);

-- ============================================================
-- REQUISITOS DOCUMENTALES POR TRÁMITE
-- ============================================================

-- Alta de Predio
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Formato de solicitud de alta de predio debidamente llenado y firmado', 1),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación oficial vigente del propietario o representante', 2),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='CURP_DOC'), TRUE, FALSE, FALSE, 'CURP del propietario', 3),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='RFC_DOC'), TRUE, FALSE, FALSE, 'Constancia de situación fiscal vigente', 4),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='ESCR_NOTARIAL'), TRUE, FALSE, FALSE, 'Escritura notarial del inmueble', 5),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='PLANO_CATASTRAL'), TRUE, FALSE, FALSE, 'Plano catastral del predio', 6),
((SELECT id FROM tipos_tramite WHERE clave='ALTA_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='CERT_LIBERTAD'), TRUE, FALSE, FALSE, 'Certificado de libertad de gravamen reciente', 7);

-- Modificación de Datos del Predio
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='MOD_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Formato de solicitud de modificación', 1),
((SELECT id FROM tipos_tramite WHERE clave='MOD_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación oficial vigente', 2),
((SELECT id FROM tipos_tramite WHERE clave='MOD_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='COMP_DOMICILIO'), TRUE, FALSE, FALSE, 'Comprobante de domicilio reciente', 3),
((SELECT id FROM tipos_tramite WHERE clave='MOD_PREDIO'), (SELECT id FROM tipos_documento WHERE clave='ESCR_NOTARIAL'), TRUE, FALSE, FALSE, 'Escritura notarial vigente', 4);

-- Licencia de Construcción
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Solicitud de licencia de construcción', 1),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación oficial del solicitante', 2),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='RFC_DOC'), TRUE, FALSE, FALSE, 'RFC vigente', 3),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='ESCR_NOTARIAL'), TRUE, FALSE, FALSE, 'Escritura del predio', 4),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='PLANO_ARQT'), TRUE, TRUE, TRUE, 'Planos arquitectónicos del proyecto a construir', 5),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='MEMORIA_DESC'), TRUE, TRUE, TRUE, 'Memoria descriptiva del proyecto', 6),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='DICTAMEN_USO'), TRUE, FALSE, FALSE, 'Dictamen de uso de suelo compatible', 7),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='PAGO_PREDIAL'), TRUE, FALSE, FALSE, 'Pago de impuesto predial al corriente', 8),
((SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'), (SELECT id FROM tipos_documento WHERE clave='PLANO_CATASTRAL'), TRUE, FALSE, FALSE, 'Plano catastral del predio', 9);

-- Certificado de Uso de Suelo
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='CERT_USO_SUELO'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Solicitud de certificado de uso de suelo', 1),
((SELECT id FROM tipos_tramite WHERE clave='CERT_USO_SUELO'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación oficial', 2),
((SELECT id FROM tipos_tramite WHERE clave='CERT_USO_SUELO'), (SELECT id FROM tipos_documento WHERE clave='ESCR_NOTARIAL'), TRUE, FALSE, FALSE, 'Escritura del predio', 3),
((SELECT id FROM tipos_tramite WHERE clave='CERT_USO_SUELO'), (SELECT id FROM tipos_documento WHERE clave='PLANO_CATASTRAL'), FALSE, FALSE, FALSE, 'Plano catastral si está disponible', 4);

-- Certificado de No Adeudo
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='CERT_NO_ADEUDO'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Solicitud de certificado de no adeudo', 1),
((SELECT id FROM tipos_tramite WHERE clave='CERT_NO_ADEUDO'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación oficial', 2);

-- Traslado de Dominio
INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='SOLICITUD_TRAM'), TRUE, TRUE, TRUE, 'Solicitud de traslado de dominio', 1),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='ID_OFICIAL'), TRUE, FALSE, FALSE, 'Identificación del nuevo propietario', 2),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='CURP_DOC'), TRUE, FALSE, FALSE, 'CURP del nuevo propietario', 3),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='RFC_DOC'), TRUE, FALSE, FALSE, 'RFC del nuevo propietario', 4),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='ESCR_NOTARIAL'), TRUE, TRUE, TRUE, 'Escritura notarial de la nueva transmisión', 5),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='CERT_LIBERTAD'), TRUE, FALSE, FALSE, 'Certificado de libertad de gravamen', 6),
((SELECT id FROM tipos_tramite WHERE clave='TRASLADO_DOMINIO'), (SELECT id FROM tipos_documento WHERE clave='CERT_NO_ADEUDO'), TRUE, FALSE, FALSE, 'Certificado de no adeudo predial', 7);

-- ============================================================
-- USUARIOS DEMO (ficticios)
--
-- No se versionan hashes de contraseñas. El hash se genera al
-- momento de cargar el seed con pgcrypto (bcrypt, cost 12),
-- compatible con password_verify() de PHP.
--
-- La contraseña se toma del parámetro de sesión remtys.demo_password:
--   PGOPTIONS="-c remtys.demo_password=TuPasswordDemo" psql ... -f database/seeds.sql
-- Si no se define, se usa la contraseña demo documentada en el README
-- ('Demo#Remtys2026'). Cámbiala en cualquier entorno compartido.
-- ============================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;

INSERT INTO usuarios (email, password_hash, nombre_completo, rol, email_verificado, email_verificado_at)
SELECT v.email,
       crypt(COALESCE(NULLIF(current_setting('remtys.demo_password', true), ''), 'Demo#Remtys2026'), gen_salt('bf', 12)),
       v.nombre, v.rol::rol_usuario, TRUE, NOW()
FROM (VALUES
    ('superadmin@ejemplo.com',  'Superadministrador Demo',     'supergod'),
    ('admin@ejemplo.com',       'Administrador Demo',          'admin'),
    ('revisor@ejemplo.com',     'Revisor Demo',                'revisor'),
    ('ciudadano@ejemplo.com',   'Juan Pérez Demo',             'contribuyente'),
    ('empresa@ejemplo.com',     'Empresa Demo S.A. de C.V.',   'contribuyente')
) AS v(email, nombre, rol);

-- ADVERTENCIA: estos usuarios son solo para desarrollo local.
-- NO ejecutar seeds.sql en producción.
-- Las contraseñas de producción deben generarse de forma independiente y segura.

-- ============================================================
-- CONTRIBUYENTES DE PRUEBA
-- ============================================================
INSERT INTO contribuyentes (usuario_id, tipo_persona, nombre_completo, rfc, calle, numero_exterior, colonia, municipio, estado, codigo_postal, telefono, email_contacto) VALUES
(
    (SELECT id FROM usuarios WHERE email='ciudadano@ejemplo.com'),
    'fisica',
    'Juan Pérez Demo',
    'XAXX010101000',
    'Av. Constitución',
    '123',
    'Centro',
    'Municipio Demo',
    'Estado de México',
    '50000',
    '5500000001',
    'ciudadano@ejemplo.com'
),
(
    (SELECT id FROM usuarios WHERE email='empresa@ejemplo.com'),
    'moral',
    'Empresa Demo S.A. de C.V.',
    'EXX010101AAA',
    'Blvd. Industrial',
    '456',
    'Parque Industrial',
    'Municipio Demo',
    'Estado de México',
    '50100',
    '5500000002',
    'empresa@ejemplo.com'
);

INSERT INTO contribuyente_fisica (contribuyente_id, curp) VALUES
(
    (SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'),
    'XAXX010101HDFXXX00'
);

INSERT INTO contribuyente_moral (contribuyente_id, representante_legal, rfc_representante) VALUES
(
    (SELECT id FROM contribuyentes WHERE rfc='EXX010101AAA'),
    'Representante Legal Demo',
    'XAXX010101000'
);

-- ============================================================
-- PREDIOS DE PRUEBA
-- ============================================================
INSERT INTO predios (clave_catastral, cuenta_predial, nombre_descripcion, uso_suelo, superficie_total, superficie_construida, valor_catastral, calle, numero_exterior, colonia, municipio, estado, codigo_postal, latitud, longitud) VALUES
('MUN-001-001-001', 'CP-2024-001234', 'Casa Habitación Centro',      'Habitacional',             180.00, 120.00, 850000.00, 'Calle 5 de Mayo', '10', 'Centro', 'Municipio Demo', 'Estado de México', '50000', 19.2850, -99.6540),
('MUN-001-002-045', 'CP-2024-002567', 'Local Comercial Boulevard',   'Comercial',                350.00, 280.00, 2100000.00, 'Blvd. Benito Juárez', '230', 'Zona Comercial', 'Municipio Demo', 'Estado de México', '50050', 19.2920, -99.6480),
('MUN-002-003-012', 'CP-2024-003891', 'Terreno Residencial',          'Habitacional Unifamiliar', 600.00, 0.00, 1200000.00, 'Privada Las Flores', '5', 'Fraccionamiento Las Rosas', 'Municipio Demo', 'Estado de México', '50200', 19.3010, -99.6320);

-- ============================================================
-- RELACIONES PREDIO-CONTRIBUYENTE
-- ============================================================
INSERT INTO predio_contribuyente (predio_id, contribuyente_id, fecha_inicio, activo, tipo_relacion) VALUES
((SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001'), (SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'), '2020-01-15', TRUE, 'propietario'),
((SELECT id FROM predios WHERE clave_catastral='MUN-001-002-045'), (SELECT id FROM contribuyentes WHERE rfc='EXX010101AAA'), '2019-06-01', TRUE, 'propietario'),
((SELECT id FROM predios WHERE clave_catastral='MUN-002-003-012'), (SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'), '2022-03-20', TRUE, 'propietario');

-- ============================================================
-- PERIODOS FISCALES
-- ============================================================
INSERT INTO periodos_fiscales (anio, descripcion, fecha_inicio, fecha_fin, activo) VALUES
(2024, 'Ejercicio Fiscal 2024', '2024-01-01', '2024-12-31', TRUE),
(2025, 'Ejercicio Fiscal 2025', '2025-01-01', '2025-12-31', TRUE),
(2026, 'Ejercicio Fiscal 2026', '2026-01-01', '2026-12-31', TRUE);

-- ============================================================
-- OBLIGACIONES FISCALES
-- ============================================================
INSERT INTO obligaciones_fiscales (contribuyente_id, predio_id, tipo, descripcion, monto_base) VALUES
((SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'), (SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001'), 'Impuesto Predial', 'Impuesto predial Casa Habitación Centro', 3200.00),
((SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'), (SELECT id FROM predios WHERE clave_catastral='MUN-002-003-012'), 'Impuesto Predial', 'Impuesto predial Terreno Residencial', 1800.00),
((SELECT id FROM contribuyentes WHERE rfc='EXX010101AAA'), (SELECT id FROM predios WHERE clave_catastral='MUN-001-002-045'), 'Impuesto Predial', 'Impuesto predial Local Comercial', 8500.00),
((SELECT id FROM contribuyentes WHERE rfc='EXX010101AAA'), (SELECT id FROM predios WHERE clave_catastral='MUN-001-002-045'), 'Derechos de Agua', 'Derechos de suministro de agua comercial', 2400.00);

-- ============================================================
-- OBLIGACIÓN-PERIODO
-- ============================================================
INSERT INTO obligacion_periodo (obligacion_id, periodo_id, monto, monto_recargos, pagado, monto_pagado) VALUES
-- Juan Pérez - Predio 1 - 2024 (pagado)
((SELECT id FROM obligaciones_fiscales WHERE tipo='Impuesto Predial' AND contribuyente_id=(SELECT id FROM contribuyentes WHERE rfc='XAXX010101000') AND predio_id=(SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001')),
 (SELECT id FROM periodos_fiscales WHERE anio=2024), 3200.00, 0.00, TRUE, 3200.00),
-- Juan Pérez - Predio 1 - 2025 (pendiente)
((SELECT id FROM obligaciones_fiscales WHERE tipo='Impuesto Predial' AND contribuyente_id=(SELECT id FROM contribuyentes WHERE rfc='XAXX010101000') AND predio_id=(SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001')),
 (SELECT id FROM periodos_fiscales WHERE anio=2025), 3200.00, 320.00, FALSE, 0.00),
-- Juan Pérez - Predio 1 - 2026 (pendiente)
((SELECT id FROM obligaciones_fiscales WHERE tipo='Impuesto Predial' AND contribuyente_id=(SELECT id FROM contribuyentes WHERE rfc='XAXX010101000') AND predio_id=(SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001')),
 (SELECT id FROM periodos_fiscales WHERE anio=2026), 3200.00, 0.00, FALSE, 0.00),
-- Juan Pérez - Predio 3 - 2025 (pendiente)
((SELECT id FROM obligaciones_fiscales WHERE tipo='Impuesto Predial' AND contribuyente_id=(SELECT id FROM contribuyentes WHERE rfc='XAXX010101000') AND predio_id=(SELECT id FROM predios WHERE clave_catastral='MUN-002-003-012')),
 (SELECT id FROM periodos_fiscales WHERE anio=2025), 1800.00, 180.00, FALSE, 0.00);

-- ============================================================
-- EXPEDIENTE Y TRÁMITE DE PRUEBA
-- ============================================================
INSERT INTO expedientes (numero_expediente, contribuyente_id, predio_id, estado, fecha_apertura, descripcion) VALUES
('EXP-2026-000001',
 (SELECT id FROM contribuyentes WHERE rfc='XAXX010101000'),
 (SELECT id FROM predios WHERE clave_catastral='MUN-001-001-001'),
 'en_proceso',
 '2026-03-01',
 'Expediente para Licencia de Construcción — ampliación de sala y recámara');

INSERT INTO tramites (numero_tramite, expediente_id, tipo_tramite_id, estado, fecha_inicio, observaciones) VALUES
('TRM-2026-000001',
 (SELECT id FROM expedientes WHERE numero_expediente='EXP-2026-000001'),
 (SELECT id FROM tipos_tramite WHERE clave='LICENCIA_CONST'),
 'documentacion_pendiente',
 '2026-03-01',
 'Trámite iniciado. Pendiente de completar documentación requerida.');






