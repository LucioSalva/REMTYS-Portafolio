-- =========================================================================
-- Catálogo de 20 trámites de Tesorería/Catastro (municipio de referencia)
-- Datos normalizados a partir de las fichas públicas del Registro Municipal de Trámites y Servicios (2025).
-- =========================================================================

INSERT INTO tramite (tramite_id, codigo, nombre, tipo, area, descripcion, documento_a_obtener,
                     vigencia_resultado, fundamento_legal, sujeto_inspeccion, se_realiza_en_linea,
                     direccion_web, tiempo_max_respuesta, costo_descripcion, costo_monto,
                     costo_es_variable, costo_es_gratuito, pago_efectivo, pago_tdc, pago_tdb, pago_portal,
                     fecha_actualizacion) VALUES

 (1, 'ASIGNACION_CLAVE_CATASTRAL', 'Asignación de clave catastral', 'TRAMITE', 'CATASTRO',
     'Asignación de claves catastrales a predios dentro de la jurisdicción municipal.',
     'Certificación de clave y valor catastral', 'Ejercicio fiscal vigente',
     'Arts. 167, 168, 171, 173, 175, 179, 182 y 183 CFEM; 22 I a y b Reglamento Título Quinto.',
     true, false, NULL, '25 días hábiles', '$282.85 (2.5 UMA)', 282.85, false, false, true, true, true, true, DATE '2024-01-31'),

 (2, 'CAMBIO_BOUCHER_RECIBO',      'Emisión de recibo oficial (cambio de boucher bancario)', 'TRAMITE', 'IMPUESTO_PREDIAL',
     'Se extiende comprobante de pagos realizados a Tesorería (línea, transferencia o banco).',
     'Recibo oficial de pago de predio', 'Sin plazo — a petición del contribuyente',
     'Arts. 20 Bis, 26 párrafos 6 y 7 CFEM.',
     false, false, NULL, 'Media hora', 'Gratuito año fiscal corriente; $24.00 copia simple años anteriores', NULL, true, false, true, true, true, false, DATE '2025-01-31'),

 (3, 'CERT_CLAVE_VALOR_CATASTRAL', 'Certificación de clave y valor catastral', 'TRAMITE', 'CATASTRO',
     'Se certifica la clave y el valor catastral con base en el sistema de gestión catastral.',
     'Certificación de clave y valor catastral', 'Ejercicio fiscal vigente',
     'Arts. 166 II, 167, 168, 171, 173, 175, 182 CFEM; 21 y 22 II d Reglamento Título Quinto.',
     true, false, NULL, '25 días hábiles', 'Art. 166 II CFEM', NULL, true, false, true, true, true, true, DATE '2025-01-31'),

 (4, 'CERT_NO_ADEUDO_PREDIAL',     'Certificación de no adeudo predial y aportación de mejoras', 'SERVICIO', 'IMPUESTO_PREDIAL',
     'Certificación para inscribir el inmueble en el Registro Público de la Propiedad o para venta.',
     'Certificado de no adeudo de impuesto predial o aportación de mejoras', 'Año en que se emite',
     'Arts. 2, 7, 9 Ley de Ingresos; Art. 8 CPEUM; 147 III CFEM.',
     false, false, NULL, '15 días', '$283.00', 283.00, false, false, true, true, true, false, DATE '2025-01-31'),

 (5, 'CERT_PLANO_MANZANERO',       'Certificación de plano manzanero catastral', 'TRAMITE', 'CATASTRO',
     'Expedir copia certificada del plano de la manzana con los datos catastrales del inmueble.',
     'Certificación de plano manzanero catastral', 'Ejercicio fiscal vigente',
     'Arts. 166 III, 167, 168, 169 IV, 171, 173, 175, 179, 182 CFEM; 21, 22 II c Reglamento T5.',
     true, false, NULL, '25 días hábiles', '$282.85 (2.5 UMA)', 282.85, false, false, true, true, true, true, DATE '2025-01-31'),

 (6, 'CERT_TRASLADO_DOMINIO',      'Certificación de traslado de dominio', 'TRAMITE', 'TRASLADO_DOMINIO',
     'Certificación cuando el usuario pierde el formato o pago del traslado de dominio.',
     'Certificación de traslado de dominio', 'Permanente',
     'Arts. 70, 71, 113 CFEM; 4 LI Municipios; 3, 13, 15, 48 VI LOAP EdoMex.',
     false, false, NULL, '30 días hábiles', '$452.00', 452.00, false, false, true, true, true, true, DATE '2025-02-06'),

 (7, 'CONSTANCIA_IDENT_CATASTRAL', 'Constancia de identificación catastral', 'TRAMITE', 'CATASTRO',
     'Hace constar los datos técnicos registrados en el padrón catastral del inmueble.',
     'Constancia de identificación catastral', 'Ejercicio fiscal vigente (desde emisión hasta fin de año)',
     'Arts. 166 IV, 167, 168, 169 IV, 171, 173, 175, 179, 182 CFEM.',
     false, false, NULL, '15 días hábiles', '$282.85 (2.5 UMA)', 282.85, false, false, true, true, true, true, DATE '2025-01-31'),

 (8, 'CONVENIO',                   'Convenio de pago en parcialidades', 'SERVICIO', 'EJECUCION_FISCAL',
     'Pago en parcialidades mensuales de contribuciones municipales.',
     'Recibo por parcialidad y carta finiquito al término', 'No aplica',
     'Arts. 17, 30, 32, 33, 34 CFEM; 47 Bando Municipal 2025.',
     false, false, NULL, 'Inmediato', 'Gratuito', NULL, false, true, true, true, true, false, DATE '2025-01-31'),

 (9, 'COPIA_CERT_TRASLADO_DOMINIO','Copia certificada de traslado de dominio', 'TRAMITE', 'TRASLADO_DOMINIO',
     'Emisión de copia certificada cuando no se cuenta con el formato y pago originales del traslado.',
     'Copia certificada de traslado de dominio', 'Permanente',
     'Arts. 70, 71, 73, 113 CFEM.',
     false, false, NULL, '30 días hábiles', '1 copia $96.00 / 2 copias $143.00', 96.00, true, false, true, true, true, true, DATE '2025-02-06'),

(10, 'COPIAS_SIMPLES_PREDIAL',    'Copias simples y certificadas del expediente predial', 'SERVICIO', 'IMPUESTO_PREDIAL',
     'Copias simples o certificadas del expediente o recibos bajo resguardo de Tesorería.',
     'Copias del expediente o recibo certificado', 'Sin plazo (a solicitud del contribuyente)',
     'Art. 147 a y b; 171 VIII CFEM; 135 CPA.',
     false, false, NULL, '15 días', '1ra foja $96.00 / 2da foja $47.00', 96.00, true, false, true, true, true, false, DATE '2025-01-31'),

(11, 'CORRECCION_DATOS_TRASLADO', 'Corrección de datos en traslado de dominio', 'TRAMITE', 'TRASLADO_DOMINIO',
     'Corrección de dato incorrecto en el formato de traslado de dominio.',
     'Corrección de datos en traslado de dominio', 'Permanente',
     'Arts. 70, 71, 113 CFEM.',
     false, false, NULL, '30 días hábiles', '$113.00', 113.00, false, false, true, true, true, true, DATE '2025-02-06'),

(12, 'CORRECCION_MEDIDAS',        'Corrección de medidas y colindancias (terreno y/o construcción)', 'SERVICIO', 'CATASTRO',
     'Rectificación de medidas o superficie mediante verificación de campo.',
     'Certificación de clave y valor catastral', 'Ejercicio fiscal vigente',
     'Arts. 167, 168, 171, 173, 175, 181, 182 CFEM; 21, 22 I b y c Reglamento T5.',
     true, false, NULL, '8 días hábiles posteriores a la visita', '$282.85 (2.5 UMA)', 282.85, false, false, true, true, true, true, DATE '2025-01-31'),

(13, 'DEGUELLO_RASTRO',           'Degüello en rastro', 'SERVICIO', 'EJECUCION_FISCAL',
     'Derecho por el sacrificio de animales destinados al consumo humano.',
     'Recibo de pago mensual', 'La que indique el recibo',
     'Arts. 48 I y III, 150, 151, 361 XIII CFEM.',
     true, false, NULL, 'Inmediato', 'Gratuito', NULL, false, true, true, true, true, false, DATE '2025-01-31'),

(14, 'ESTACIONAMIENTOS',          'Derechos por servicio de estacionamiento', 'SERVICIO', 'EJECUCION_FISCAL',
     'Derecho por prestar servicio de estacionamiento en lugares públicos y privados.',
     'Recibo de pago bimestral', 'La que indique el recibo',
     'Arts. 48 I y III, 157, 158, 361 XIII CFEM.',
     true, false, NULL, 'Inmediato', 'Gratuito', NULL, false, true, true, true, true, false, DATE '2025-01-31'),

(15, 'ISAI_TRASLATIVA_DOMINIO',   'Impuesto sobre la adquisición de inmuebles (ISAI) / Traslativa de dominio', 'TRAMITE', 'TRASLADO_DOMINIO',
     'Registro del predio a nombre del nuevo propietario tras adquisición.',
     'Formato de traslado de dominio y recibo de pago autorizados', 'Permanente',
     'Arts. 46 III, 113, 114, 115, 116, 117, 171 I CFEM; 4 LI Municipios.',
     false, false, NULL, '30 días hábiles', 'Fijo según tabla de valores (Arts. 115 y 116 CFEM)', NULL, true, false, true, true, true, true, DATE '2025-02-06'),

(16, 'JUEGOS_MAQUINITAS',         'Impuesto sobre juegos (maquinitas)', 'SERVICIO', 'EJECUCION_FISCAL',
     'Impuesto sobre ingresos por explotación de maquinitas.',
     'Recibo de pago mensual', 'La que indique el recibo',
     'Arts. 48 I y III, 122, 123, 361 XIII CFEM.',
     true, false, NULL, 'Inmediato', 'Gratuito', NULL, false, true, true, true, true, false, DATE '2025-01-31'),

(17, 'LEVANTAMIENTO_TOPOGRAFICO', 'Levantamiento topográfico catastral', 'SERVICIO', 'CATASTRO',
     'Levantamiento topográfico con base en documento de propiedad y evidencias físicas.',
     'Acta administrativa y plano de levantamiento topográfico', 'Permanente (salvo modificación)',
     'Arts. 166 V, 167, 168, 169 IV, 171, 173, 175, 179, 182 CFEM.',
     true, false, NULL, '25 días hábiles posteriores a la visita', 'Variable según superficie (Art. 166 V CFEM)', NULL, true, false, true, true, true, true, DATE '2025-01-31'),

(18, 'PAGO_IMPUESTO_PREDIAL',     'Pago del impuesto predial', 'SERVICIO', 'IMPUESTO_PREDIAL',
     'Pago anual del impuesto predial por contribuyentes inscritos en el padrón.',
     'Recibo de pago de impuesto predial', 'Periodo del recibo',
     'Art. 8 CPEUM; 2, 7, 9 LI EdoMex; 107-112 CFEM.',
     false, true, 'https://localhost/predial/', '15 minutos', 'Sin costo (el pago del propio impuesto)', NULL, true, true, true, true, true, true, DATE '2025-01-31'),

(19, 'SUBDIVISION_LOTIFICACION',  'Subdivisión, fusión, lotificación o relotificación', 'SERVICIO', 'CATASTRO',
     'Actualización del padrón catastral por subdivisión, fusión, lotificación o relotificación previa autorización SEDUO.',
     'Certificado de clave y valor catastral', 'Ejercicio fiscal vigente',
     'Arts. 167, 168, 171, 173, 175, 175 Bis, 182 CFEM.',
     true, false, NULL, 'Variable', 'Conforme a Art. 166 II CFEM', NULL, true, false, true, true, true, true, DATE '2025-01-31'),

(20, 'VERIFICACION_LINDEROS',     'Verificación de linderos', 'SERVICIO', 'CATASTRO',
     'Verificación de linderos y superficies sin notificación a colindantes.',
     'Oficio y plano de levantamiento topográfico', 'Permanente (salvo modificación)',
     'Arts. 166 VI, 167, 168, 169, 171, 173, 175, 179, 182 CFEM.',
     true, false, NULL, '25 días hábiles posteriores a la visita', 'Variable según superficie (Art. 166 VI CFEM)', NULL, true, false, true, true, true, true, DATE '2025-01-31');

-- Relaciones entre trámites (prerequisitos / relacionados mencionados en las fichas)
INSERT INTO tramite_relacion (tramite_id, tramite_relacionado_id, relacion) VALUES
 (4,  3,  'RELACIONADO'),       -- Cert no adeudo <-> Cert clave y valor
 (15, 3,  'PRERREQUISITO'),     -- ISAI <- Cert clave y valor
 (15, 4,  'PRERREQUISITO'),     -- ISAI <- Cert no adeudo predial
 (18, 4,  'RELACIONADO'),       -- Pago predial <-> Cert no adeudo predial
 (17, 3,  'RELACIONADO'),       -- Levantamiento topog. <-> Cert clave y valor
 (20, 3,  'RELACIONADO'),       -- Verificación linderos <-> Cert clave y valor
 (12, 3,  'RELACIONADO');       -- Corrección medidas emite Cert clave y valor
