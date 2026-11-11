-- =========================================================================
-- Catálogos: tipo_documento + aliases + grupos  (32 tipos canónicos)
-- =========================================================================

INSERT INTO tipo_documento (tipo_documento_id, codigo, nombre, scope, vigencia_tipo, vigencia_meses, descripcion) VALUES
-- ------ Identidad / personería ------
 (1, 'IDENTIFICACION_OFICIAL',          'Identificación oficial vigente',                         'CONTRIBUYENTE', 'VENCIMIENTO_DOC', NULL, 'Credencial para votar, cartilla militar, pasaporte o cédula profesional.'),
 (2, 'CARTA_PODER',                     'Carta poder con testigos e identificaciones',            'CONTRIBUYENTE', 'MESES',           12,   'Carta poder con dos testigos y copia de identificaciones.'),
 (3, 'PODER_NOTARIAL',                  'Poder notarial',                                          'CONTRIBUYENTE', 'PERMANENTE',      NULL, 'Instrumento notarial que acredita representación legal.'),
 (4, 'ACTA_CONSTITUTIVA',               'Acta constitutiva',                                       'CONTRIBUYENTE', 'PERMANENTE',      NULL, 'Acta constitutiva de la persona jurídica.'),

-- ------ Acreditación de propiedad (grupo "ACREDITA_PROPIEDAD") ------
 (5, 'ESCRITURA_PUBLICA',               'Escritura pública / Testimonio notarial',                 'INMUEBLE',      'PERMANENTE',      NULL, 'Escritura pública expedida por notario o testimonio notarial.'),
 (6, 'SENTENCIA_JUDICIAL',              'Sentencia judicial con ejecutoria',                       'INMUEBLE',      'PERMANENTE',      NULL, 'Resolución judicial o administrativa con ejecutoria que consigna el acto traslativo.'),
 (7, 'MANIFESTACION_TRASLADO_DOMINIO',  'Manifestación de traslado de dominio y recibo de pago',   'INMUEBLE',      'PERMANENTE',      NULL, 'Documento de traslado de dominio acompañado de su recibo.'),
 (8, 'CEDULA_IMEVIS_INSUS_CORETT',      'Cédula de contratación IMEVIS/INSUS/CORETT',              'INMUEBLE',      'PERMANENTE',      NULL, 'Cédula de vivienda social (IMEVIS, INSUS o CORETT).'),
 (9, 'ACTA_ENTREGA_VIVIENDA',           'Acta de entrega y/o liberación de vivienda',              'INMUEBLE',      'PERMANENTE',      NULL, 'Acta de entrega de vivienda de interés social (incluye FONHAPO).'),
(10, 'CONTRATO_COMPRAVENTA',            'Contrato de compra-venta',                                'INMUEBLE',      'PERMANENTE',      NULL, 'Contrato de compra-venta (cotejado ante notario si es privado).'),
(11, 'CESION_DERECHOS',                 'Cesión de derechos',                                      'INMUEBLE',      'PERMANENTE',      NULL, 'Documento de cesión de derechos.'),
(12, 'CONTRATO_DONACION',               'Contrato de donación',                                    'INMUEBLE',      'PERMANENTE',      NULL, 'Documento privado de donación.'),

-- ------ Catastral / Inmueble ------
(13, 'FORMATO_SOL_SERV_CATASTRALES',    'Formato de Solicitud de Servicios Catastrales',           'SOLICITUD',     'UNICO_USO',       NULL, 'Formato debidamente llenado, firmado y requisitado.'),
(14, 'MANIFESTACION_CATASTRAL',         'Manifestación catastral (modificación de superficie)',    'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Manifestación para actualización de inscripción en el padrón catastral.'),
(15, 'FOTOGRAFIA_FACHADA',              'Fotografía de la fachada del inmueble a color',           'INMUEBLE',      'MESES',           24,   'Fotografía reciente a color de la fachada del inmueble.'),
(16, 'CROQUIS_LOCALIZACION',            'Croquis de localización del inmueble',                    'INMUEBLE',      'PERMANENTE',      NULL, 'Croquis de localización (mientras no cambie el entorno).'),
(17, 'RELACION_COLINDANTES',            'Relación de colindantes y dos testigos',                  'SOLICITUD',     'UNICO_USO',       NULL, 'Lista de nombres de colindantes y dos testigos con domicilio en el municipio.'),

-- ------ Pagos / recibos ------
(18, 'RECIBO_PREDIAL_VIGENTE',          'Recibo de pago de impuesto predial al corriente',         'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Último recibo de predial al corriente del ejercicio fiscal.'),
(19, 'BOUCHER_BANCARIO',                'Comprobante de pago bancario (boucher)',                  'SOLICITUD',     'UNICO_USO',       NULL, 'Comprobante de línea de captura pagada en banco o en línea.'),
(20, 'RECIBO_PAGO_DERECHOS',            'Recibo de pago de derechos del trámite',                  'SOLICITUD',     'UNICO_USO',       NULL, 'Recibo oficial de pago de derechos del servicio.'),
(21, 'RECIBO_PERIODO_ANTERIOR',         'Recibo de pago del periodo inmediato anterior',           'SOLICITUD',     'UNICO_USO',       NULL, 'Recibo del periodo previo (trámites recurrentes mensuales/bimestrales).'),

-- ------ Oficios / solicitudes ------
(22, 'OFICIO_TESORERO',                 'Oficio / petición por escrito al Tesorero Municipal',     'SOLICITUD',     'UNICO_USO',       NULL, 'Escrito libre dirigido al Tesorero Municipal.'),
(23, 'LIQUIDACION_CUENTA',              'Liquidación / cuenta a pagar emitida por dependencia',    'SOLICITUD',     'UNICO_USO',       NULL, 'Liquidación o cuenta emitida por una dependencia municipal.'),
(24, 'SOL_EXENCION_CATASTRAL',          'Solicitud de exención de servicios catastrales',          'SOLICITUD',     'UNICO_USO',       NULL, 'Solicitud de exención Art. 23 CFEM (sólo Instituciones Públicas).'),
(25, 'TESTIMONIAL_NOMBRE',              'Testimonial de nombre (notario o juzgado)',               'CONTRIBUYENTE', 'PERMANENTE',      NULL, 'Testimonial de nombre para corregir datos del documento de propiedad.'),
(26, 'OFICIO_AUT_SUBDIVISION',          'Oficio de autorización, planos y gaceta (SEDUO)',         'INMUEBLE',      'PERMANENTE',      NULL, 'Autorización de subdivisión/fusión/lotificación emitida por SEDUO EdoMex.'),

-- ------ Documentos emitidos por otros trámites (insumos para ISAI y Traslado) ------
(27, 'CERT_CLAVE_VALOR_CATASTRAL',      'Certificación de clave y valor catastral',                'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Documento emitido por Catastro (resultado de otro trámite).'),
(28, 'CERT_NO_ADEUDO_PREDIAL',          'Certificación de no adeudo predial',                      'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Emitida por Tesorería (resultado de otro trámite).'),
(29, 'APORTACION_MEJORAS',              'Certificación / copia de aportación de mejoras',          'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Constancia de aportación de mejoras.'),
(30, 'CERT_NO_ADEUDO_AGUA',             'Certificación de no adeudo de agua / constancia del organismo de agua',  'INMUEBLE',      'EJERCICIO_FISCAL',NULL, 'Emitida por el organismo operador de agua o pozo independiente.'),

-- ------ Trámites sobre formato ya emitido ------
(31, 'FORMATO_TRASLADO_DOMINIO',        'Formato de traslado de dominio y pago original',          'INMUEBLE',      'PERMANENTE',      NULL, 'Formato físico de traslado de dominio (usado para correcciones y copias certificadas).'),
(32, 'FORMATO_VENTANILLA',              'Formato de ventanilla llenado (tinta azul / máquina)',    'SOLICITUD',     'UNICO_USO',       NULL, 'Formato proporcionado en ventanilla 1 o 2 del Departamento de Traslado de Dominio.');

-- -------------------------------------------------------------------------
-- Aliases (frases tal como aparecen en las fichas de los PDFs)
-- -------------------------------------------------------------------------
INSERT INTO tipo_documento_alias (tipo_documento_id, alias) VALUES
 (1, 'Credencial para votar vigente'), (1, 'INE'), (1, 'Cartilla militar'), (1, 'Pasaporte'), (1, 'Cédula profesional'),
 (2, 'Carta poder con dos testigos'),
 (3, 'Poder Notarial con respectivas identificaciones'),
 (5, 'Copia certificada de la Escritura Pública'), (5, 'Testimonio Notarial'),
 (6, 'Resolución Judicial o Administrativa que haya causado ejecutoria'),
 (7, 'Manifestación de traslado de dominio y recibo de pago'),
 (8, 'IMEVIS'), (8, 'INSUS'), (8, 'CORETT'),
 (9, 'Acta de entrega y/o liberación de inmueble de interés social'), (9, 'Acta de entrega de FONHAPO y finiquito'),
(10, 'Contrato de compraventa notariado'), (10, 'Documentos Privados (Compra-Venta)'),
(13, 'Formato de Solicitud de Servicios Catastrales debidamente llenado, firmado y requisitado'),
(14, 'Manifestación debidamente llenada para actualización de padrón catastral'),
(15, 'Fotografía fachada a color'),
(16, 'Croquis de localización'),
(18, 'Último recibo predial'), (18, 'Boleta predial'), (18, 'Recibo de pago de predio'),
(19, 'Línea de captura con boucher bancario'), (19, 'Pago bancario'),
(22, 'Petición por escrito dirigida al Tesorero Municipal'),
(31, 'Formato de Traslado de Dominio'), (31, 'Pago de Traslado de Dominio Original'),
(32, 'Formato proporcionado en ventanilla 1 o 2');

-- =========================================================================
-- Grupos (requisitos "cualquiera de estos" satisface)
-- =========================================================================
INSERT INTO grupo_documento (grupo_id, codigo, nombre, descripcion) VALUES
 (1, 'ACREDITA_PROPIEDAD',
     'Documento que acredita la propiedad',
     'Cualquiera de: escritura pública, testimonio notarial, sentencia judicial con ejecutoria, manifestación de traslado de dominio y recibo, cédula IMEVIS/INSUS/CORETT, acta de entrega de vivienda, contrato compra-venta, cesión de derechos.');

INSERT INTO grupo_documento_tipo (grupo_id, tipo_documento_id) VALUES
 (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11);
