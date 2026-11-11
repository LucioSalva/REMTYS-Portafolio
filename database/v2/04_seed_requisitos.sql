-- =========================================================================
-- Matriz tramite_requisito
-- Cada fila: un requisito documental para (tramite, tipo_persona).
-- Se usa grupo_id = 1 (ACREDITA_PROPIEDAD) cuando cualquier documento del
-- grupo satisface el requisito. tipo_documento_id cuando es exigido específico.
-- =========================================================================

-- -------------------------------------------------------------------------
-- T1 — Asignación de clave catastral
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 -- Personas Físicas
 (1,'FISICA',1, 13,  NULL, true, 0, true,  NULL),
 (1,'FISICA',2, 14,  NULL, true, 1, true,  'Cuando hay modificaciones de superficie'),
 (1,'FISICA',3, NULL,   1, false,1, true,  NULL),
 (1,'FISICA',4, 1,   NULL, false,1, true,  'Del propietario y/o representante legal'),
 (1,'FISICA',5, 15,  NULL, true, 0, true,  NULL),
 (1,'FISICA',6, 16,  NULL, true, 0, true,  NULL),
 -- Instituciones Públicas
 (1,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, 'Solicita exención'),
 (1,'INSTITUCION_PUBLICA',2, 14, NULL, true, 0, true, 'Si hay modificación de superficie'),
 (1,'INSTITUCION_PUBLICA',3, NULL, 1, true, 1, true, NULL),
 (1,'INSTITUCION_PUBLICA',4, 1,  NULL, false,1, true, NULL),
 (1,'INSTITUCION_PUBLICA',5, 24, NULL, true, 0, true, NULL),
 (1,'INSTITUCION_PUBLICA',6, 15, NULL, true, 0, true, NULL),
 (1,'INSTITUCION_PUBLICA',7, 16, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T2 — Cambio de boucher por recibo oficial
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (2,'FISICA',1, 18, NULL, true, 1, true, NULL),
 (2,'FISICA',2, NULL,  1, true, 1, true, NULL),
 (2,'FISICA',3, 19, NULL, true, 1, true, NULL),
 (2,'FISICA',4, 1,  NULL, true, 1, true, 'Titular, o carta poder con dos testigos'),
 (2,'FISICA',5, 2,  NULL, true, 1, false,'Si no acude el titular'),

 (2,'JURIDICA',1, NULL, 1, true, 1, true, NULL),
 (2,'JURIDICA',2, 3,  NULL, true, 1, true, NULL),
 (2,'JURIDICA',3, 4,  NULL, true, 0, true, NULL),
 (2,'JURIDICA',4, 18, NULL, true, 1, true, NULL),
 (2,'JURIDICA',5, 19, NULL, true, 1, true, NULL),

 (2,'INSTITUCION_PUBLICA',1, 1, NULL, true, 1, true, NULL),
 (2,'INSTITUCION_PUBLICA',2, 3, NULL, true, 1, true, NULL),
 (2,'INSTITUCION_PUBLICA',3, 4, NULL, true, 1, true, NULL),
 (2,'INSTITUCION_PUBLICA',4, 18,NULL, true, 1, true, NULL),
 (2,'INSTITUCION_PUBLICA',5, 19,NULL, true, 1, true, NULL);

-- -------------------------------------------------------------------------
-- T3 — Certificación de clave y valor catastral
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (3,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (3,'FISICA',2, 14, NULL, true, 0, true, 'Si hay modificaciones de superficie'),
 (3,'FISICA',3, NULL, 1, true, 1, true, NULL),
 (3,'FISICA',4, 1,  NULL, false,1, true, NULL),
 (3,'FISICA',5, 2,  NULL, true, 0, false,'Si no es el propietario/representante'),
 (3,'FISICA',6, 18, NULL, false,1, true, NULL),
 (3,'FISICA',7, 20, NULL, true, 1, true, NULL),
 (3,'FISICA',8, 15, NULL, true, 0, true, NULL),
 (3,'FISICA',9, 16, NULL, true, 0, true, NULL),

 (3,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (3,'JURIDICA',2, NULL, 1, true, 1, true, NULL),
 (3,'JURIDICA',3, 3,  NULL, true, 1, true, NULL),
 (3,'JURIDICA',4, 4,  NULL, true, 1, true, NULL),
 (3,'JURIDICA',5, 1,  NULL, false,1, true, NULL),
 (3,'JURIDICA',6, 18, NULL, false,1, true, NULL),
 (3,'JURIDICA',7, 20, NULL, true, 1, true, NULL),
 (3,'JURIDICA',8, 15, NULL, true, 0, true, NULL),
 (3,'JURIDICA',9, 16, NULL, true, 0, true, NULL),

 (3,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (3,'INSTITUCION_PUBLICA',2, 14, NULL, true, 0, true, NULL),
 (3,'INSTITUCION_PUBLICA',3, NULL, 1, true, 1, true, NULL),
 (3,'INSTITUCION_PUBLICA',4, 1,  NULL, false,1, true, NULL),
 (3,'INSTITUCION_PUBLICA',5, 2,  NULL, true, 0, false,NULL),
 (3,'INSTITUCION_PUBLICA',6, 24, NULL, true, 1, true, NULL);

-- -------------------------------------------------------------------------
-- T4 — Certificación de no adeudo predial y aportación de mejoras
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (4,'FISICA',1, NULL, 1, true, 1, true, NULL),
 (4,'FISICA',2, 1,  NULL, true, 1, true, NULL),
 (4,'FISICA',3, 18, NULL, true, 1, true, NULL),

 (4,'JURIDICA',1, 1, NULL, true, 1, true, NULL),
 (4,'JURIDICA',2, 3, NULL, true, 1, true, NULL),
 (4,'JURIDICA',3, 4, NULL, true, 1, true, NULL),

 (4,'INSTITUCION_PUBLICA',1, 1, NULL, true, 1, true, NULL),
 (4,'INSTITUCION_PUBLICA',2, 3, NULL, true, 1, true, NULL),
 (4,'INSTITUCION_PUBLICA',3, 4, NULL, true, 1, true, NULL);

-- -------------------------------------------------------------------------
-- T5 — Certificación de plano manzanero catastral
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (5,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (5,'FISICA',2, NULL, 1, true, 1, true, NULL),
 (5,'FISICA',3, 1,  NULL, false,1, true, NULL),
 (5,'FISICA',4, 18, NULL, false,1, true, NULL),
 (5,'FISICA',5, 20, NULL, true, 1, true, NULL),
 (5,'FISICA',6, 2,  NULL, true, 0, false,'Si no es el propietario'),
 (5,'FISICA',7, 15, NULL, true, 0, true, NULL),
 (5,'FISICA',8, 16, NULL, true, 0, true, NULL),

 (5,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (5,'JURIDICA',2, NULL, 1, true, 1, true, NULL),
 (5,'JURIDICA',3, 1,  NULL, false,1, true, NULL),
 (5,'JURIDICA',4, 3,  NULL, true, 1, true, NULL),
 (5,'JURIDICA',5, 18, NULL, false,1, true, NULL),
 (5,'JURIDICA',6, 20, NULL, true, 1, true, NULL),
 (5,'JURIDICA',7, 15, NULL, true, 0, true, NULL),
 (5,'JURIDICA',8, 16, NULL, true, 0, true, NULL),

 (5,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (5,'INSTITUCION_PUBLICA',2, NULL, 1, true, 1, true, NULL),
 (5,'INSTITUCION_PUBLICA',3, 1,  NULL, false,1, true, NULL),
 (5,'INSTITUCION_PUBLICA',4, 2,  NULL, true, 1, false,NULL),
 (5,'INSTITUCION_PUBLICA',5, 24, NULL, true, 1, true, NULL),
 (5,'INSTITUCION_PUBLICA',6, 15, NULL, true, 0, true, NULL),
 (5,'INSTITUCION_PUBLICA',7, 16, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T6 — Certificación de traslado de dominio
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (6,'FISICA',1, 5,  NULL, false,1, true, NULL),
 (6,'FISICA',2, 6,  NULL, false,1, false,'Si hay resolución judicial'),
 (6,'FISICA',3, 31, NULL, false,1, false,'Si existe el formato y pago original'),
 (6,'FISICA',4, NULL, 1, true, 1, true, 'Documento privado cotejado ante notario'),
 (6,'FISICA',5, 1,  NULL, true, 1, true, NULL),
 (6,'FISICA',6, 32, NULL, true, 1, true, NULL),
 (6,'FISICA',7, 18, NULL, false,1, true, NULL),

 (6,'INSTITUCION_PUBLICA',1, 5,  NULL, false,1, true, NULL),
 (6,'INSTITUCION_PUBLICA',2, 6,  NULL, false,1, false,NULL),
 (6,'INSTITUCION_PUBLICA',3, 31, NULL, false,1, false,NULL),
 (6,'INSTITUCION_PUBLICA',4, NULL, 1, true, 1, true, NULL),
 (6,'INSTITUCION_PUBLICA',5, 3,  NULL, true, 0, true, NULL),
 (6,'INSTITUCION_PUBLICA',6, 32, NULL, true, 1, true, NULL),
 (6,'INSTITUCION_PUBLICA',7, 18, NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T7 — Constancia de identificación catastral
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (7,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (7,'FISICA',2, 14, NULL, true, 0, true, 'Si hay modificaciones de superficie'),
 (7,'FISICA',3, NULL, 1, true, 1, true, NULL),
 (7,'FISICA',4, 1,  NULL, true, 1, true, NULL),
 (7,'FISICA',5, 18, NULL, false,1, true, NULL),
 (7,'FISICA',6, 20, NULL, true, 1, true, NULL),
 (7,'FISICA',7, 15, NULL, true, 0, true, NULL),
 (7,'FISICA',8, 16, NULL, true, 0, true, NULL),

 (7,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (7,'JURIDICA',2, NULL, 1, true, 1, true, NULL),
 (7,'JURIDICA',3, 3,  NULL, true, 1, true, NULL),
 (7,'JURIDICA',4, 4,  NULL, true, 1, true, NULL),
 (7,'JURIDICA',5, 1,  NULL, true, 1, true, NULL),
 (7,'JURIDICA',6, 18, NULL, false,1, true, NULL),
 (7,'JURIDICA',7, 20, NULL, true, 1, true, NULL),
 (7,'JURIDICA',8, 15, NULL, true, 0, true, NULL),
 (7,'JURIDICA',9, 16, NULL, true, 0, true, NULL),

 (7,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (7,'INSTITUCION_PUBLICA',2, NULL, 1, true, 1, true, NULL),
 (7,'INSTITUCION_PUBLICA',3, 1,  NULL, true, 1, true, NULL),
 (7,'INSTITUCION_PUBLICA',4, 24, NULL, true, 1, true, NULL),
 (7,'INSTITUCION_PUBLICA',5, 15, NULL, true, 0, true, NULL),
 (7,'INSTITUCION_PUBLICA',6, 16, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T8 — Convenio (parcialidades)
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (8,'FISICA',1, 22, NULL, true, 0, true, NULL),
 (8,'FISICA',2, 23, NULL, true, 0, true, NULL),
 (8,'FISICA',3, NULL, 1, true, 0, false,'Si convenio es por predial o traslado de dominio'),
 (8,'FISICA',4, 2,  NULL, true, 0, false,'En lugar de propiedad, si aplica'),
 (8,'FISICA',5, 1,  NULL, true, 0, true, NULL),

 (8,'JURIDICA',1, 22, NULL, true, 0, true, NULL),
 (8,'JURIDICA',2, 23, NULL, true, 0, true, NULL),
 (8,'JURIDICA',3, NULL, 1, true, 0, false,NULL),
 (8,'JURIDICA',4, 3,  NULL, true, 0, true, NULL),
 (8,'JURIDICA',5, 1,  NULL, true, 0, true, NULL),

 (8,'INSTITUCION_PUBLICA',1, 22, NULL, true, 0, true, NULL),
 (8,'INSTITUCION_PUBLICA',2, 23, NULL, true, 0, true, NULL),
 (8,'INSTITUCION_PUBLICA',3, NULL, 1, true, 0, false,NULL),
 (8,'INSTITUCION_PUBLICA',4, 3,  NULL, true, 0, true, NULL),
 (8,'INSTITUCION_PUBLICA',5, 1,  NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T9 — Copia certificada de traslado de dominio
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (9,'FISICA',1, 5,  NULL, false,1, true, NULL),
 (9,'FISICA',2, 6,  NULL, false,1, false,NULL),
 (9,'FISICA',3, 9,  NULL, false,1, false,'Si es vivienda FONHAPO'),
 (9,'FISICA',4, NULL, 1, true, 0, false,'Documento privado'),
 (9,'FISICA',5, 1,  NULL, true, 1, true, NULL),
 (9,'FISICA',6, 32, NULL, true, 1, true, 'Llenado con tinta azul'),
 (9,'FISICA',7, 18, NULL, false,1, true, NULL),

 (9,'INSTITUCION_PUBLICA',1, 5, NULL, false,1, true, NULL),
 (9,'INSTITUCION_PUBLICA',2, 6, NULL, false,1, false,NULL),
 (9,'INSTITUCION_PUBLICA',3, 9, NULL, false,1, false,NULL),
 (9,'INSTITUCION_PUBLICA',4, NULL, 1, true, 0, false,NULL),
 (9,'INSTITUCION_PUBLICA',5, 3, NULL, true, 1, true, NULL),
 (9,'INSTITUCION_PUBLICA',6, 32,NULL, true, 1, true, NULL),
 (9,'INSTITUCION_PUBLICA',7, 18,NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T10 — Copias simples/certificadas del expediente predial
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (10,'FISICA',1, NULL, 1, true, 0, true, NULL),
 (10,'FISICA',2, 1,  NULL, true, 0, true, NULL),

 (10,'JURIDICA',1, NULL, 1, true, 0, true, NULL),
 (10,'JURIDICA',2, 3,  NULL, true, 0, true, NULL),
 (10,'JURIDICA',3, 4,  NULL, true, 0, true, NULL),

 (10,'INSTITUCION_PUBLICA',1, 1, NULL, true, 0, true, NULL),
 (10,'INSTITUCION_PUBLICA',2, 3, NULL, true, 0, true, NULL),
 (10,'INSTITUCION_PUBLICA',3, 4, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T11 — Corrección de datos en traslado de dominio
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (11,'FISICA',1, 5, NULL, false,1, true, NULL),
 (11,'FISICA',2, 6, NULL, false,1, false,NULL),
 (11,'FISICA',3, NULL, 1, true, 0, false,NULL),
 (11,'FISICA',4, 31,NULL, true, 1, true, NULL),
 (11,'FISICA',5, 1, NULL, true, 1, true, NULL),
 (11,'FISICA',6, 32,NULL, false,1, true, NULL),
 (11,'FISICA',7, 18,NULL, false,1, true, NULL),
 (11,'FISICA',8, 25,NULL, true, 1, false,'Si el error proviene del nombre en documento de propiedad'),

 (11,'INSTITUCION_PUBLICA',1, 5, NULL, false,1, true, NULL),
 (11,'INSTITUCION_PUBLICA',2, 6, NULL, false,1, false,NULL),
 (11,'INSTITUCION_PUBLICA',3, 31,NULL, true, 1, true, NULL),
 (11,'INSTITUCION_PUBLICA',4, 3, NULL, true, 1, true, NULL),
 (11,'INSTITUCION_PUBLICA',5, 32,NULL, true, 1, true, NULL),
 (11,'INSTITUCION_PUBLICA',6, 18,NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T12 — Corrección de medidas y colindancias
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (12,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (12,'FISICA',2, NULL, 1, false,1, true, NULL),
 (12,'FISICA',3, 18, NULL, false,1, true, NULL),
 (12,'FISICA',4, 1,  NULL, false,1, true, NULL),
 (12,'FISICA',5, 2,  NULL, true, 0, false,'Si no es el propietario'),
 (12,'FISICA',6, 20, NULL, true, 1, true, NULL),
 (12,'FISICA',7, 15, NULL, true, 0, true, NULL),
 (12,'FISICA',8, 16, NULL, true, 0, true, NULL),

 (12,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (12,'JURIDICA',2, NULL, 1, false,1, true, NULL),
 (12,'JURIDICA',3, 18, NULL, false,1, true, NULL),
 (12,'JURIDICA',4, 3,  NULL, true, 1, true, NULL),
 (12,'JURIDICA',5, 1,  NULL, false,1, true, NULL),
 (12,'JURIDICA',6, 20, NULL, true, 1, true, NULL),
 (12,'JURIDICA',7, 15, NULL, true, 0, true, NULL),
 (12,'JURIDICA',8, 16, NULL, true, 0, true, NULL),

 (12,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (12,'INSTITUCION_PUBLICA',2, NULL, 1, false,1, true, NULL),
 (12,'INSTITUCION_PUBLICA',3, 1,  NULL, false,1, true, NULL),
 (12,'INSTITUCION_PUBLICA',4, 2,  NULL, true, 0, false,NULL),
 (12,'INSTITUCION_PUBLICA',5, 24, NULL, true, 0, true, NULL),
 (12,'INSTITUCION_PUBLICA',6, 15, NULL, true, 0, true, NULL),
 (12,'INSTITUCION_PUBLICA',7, 16, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T13 — Degüello en rastro (mensual)
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (13,'FISICA',1, 22, NULL, true, 0, true, 'Manifestando número de animales sacrificados'),
 (13,'FISICA',2, 21, NULL, false,1, true, NULL),

 (13,'JURIDICA',1, 22, NULL, true, 0, true, NULL),
 (13,'JURIDICA',2, 21, NULL, false,1, true, NULL),

 (13,'INSTITUCION_PUBLICA',1, 22, NULL, true, 0, true, NULL),
 (13,'INSTITUCION_PUBLICA',2, 21, NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T14 — Estacionamientos (bimestral)
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (14,'FISICA',1, 21, NULL, false,1, true, NULL),
 (14,'JURIDICA',1, 21, NULL, false,1, true, NULL),
 (14,'INSTITUCION_PUBLICA',1, 21, NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T15 — ISAI / Traslativa de dominio
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (15,'FISICA',1, 5,  NULL, false,1, true, NULL),
 (15,'FISICA',2, 6,  NULL, false,1, false,NULL),
 (15,'FISICA',3, NULL, 1, true, 0, false,'Documento privado cotejado'),
 (15,'FISICA',4, 27, NULL, true, 1, true, NULL),
 (15,'FISICA',5, 29, NULL, true, 1, false,NULL),
 (15,'FISICA',6, 28, NULL, true, 1, true, NULL),
 (15,'FISICA',7, 30, NULL, true, 1, true, NULL),
 (15,'FISICA',8, 1,  NULL, true, 1, true, NULL),
 (15,'FISICA',9, 32, NULL, true, 1, true, NULL),

 (15,'INSTITUCION_PUBLICA',1, 5, NULL, true, 1, true, NULL),
 (15,'INSTITUCION_PUBLICA',2, 6, NULL, true, 1, false,NULL),
 (15,'INSTITUCION_PUBLICA',3, NULL, 1, true, 1, false,NULL),
 (15,'INSTITUCION_PUBLICA',4, 27,NULL, true, 1, true, NULL),
 (15,'INSTITUCION_PUBLICA',5, 29,NULL, true, 1, false,NULL),
 (15,'INSTITUCION_PUBLICA',6, 28,NULL, true, 1, true, NULL),
 (15,'INSTITUCION_PUBLICA',7, 30,NULL, true, 1, true, NULL),
 (15,'INSTITUCION_PUBLICA',8, 3, NULL, true, 1, true, NULL),
 (15,'INSTITUCION_PUBLICA',9, 32,NULL, true, 1, true, NULL);

-- -------------------------------------------------------------------------
-- T16 — Juegos / Maquinitas (mensual)
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (16,'FISICA',1, 21, NULL, false,1, true, NULL),
 (16,'JURIDICA',1, 21, NULL, false,1, true, NULL),
 (16,'INSTITUCION_PUBLICA',1, 21, NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T17 — Levantamiento topográfico catastral
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (17,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (17,'FISICA',2, NULL, 1, false,1, true, NULL),
 (17,'FISICA',3, 18, NULL, false,1, true, NULL),
 (17,'FISICA',4, 1,  NULL, false,1, true, NULL),
 (17,'FISICA',5, 2,  NULL, true, 0, false,'Si no es el propietario'),
 (17,'FISICA',6, 17, NULL, true, 0, true, NULL),
 (17,'FISICA',7, 20, NULL, true, 1, true, NULL),
 (17,'FISICA',8, 15, NULL, true, 0, true, NULL),
 (17,'FISICA',9, 16, NULL, true, 0, true, NULL),

 (17,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (17,'JURIDICA',2, NULL, 1, false,1, true, NULL),
 (17,'JURIDICA',3, 18, NULL, false,1, true, NULL),
 (17,'JURIDICA',4, 1,  NULL, false,1, true, NULL),
 (17,'JURIDICA',5, 3,  NULL, true, 1, true, NULL),
 (17,'JURIDICA',6, 17, NULL, true, 0, true, NULL),
 (17,'JURIDICA',7, 20, NULL, true, 1, true, NULL),
 (17,'JURIDICA',8, 15, NULL, true, 0, true, NULL),
 (17,'JURIDICA',9, 16, NULL, true, 0, true, NULL),

 (17,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (17,'INSTITUCION_PUBLICA',2, NULL, 1, false,1, true, NULL),
 (17,'INSTITUCION_PUBLICA',3, 1,  NULL, true, 0, true, NULL),
 (17,'INSTITUCION_PUBLICA',4, 2,  NULL, true, 0, false,NULL),
 (17,'INSTITUCION_PUBLICA',5, 24, NULL, true, 0, true, NULL),
 (17,'INSTITUCION_PUBLICA',6, 15, NULL, true, 0, true, NULL),
 (17,'INSTITUCION_PUBLICA',7, 16, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T18 — Pago del impuesto predial
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (18,'FISICA',1, 18, NULL, false,1, true, 'Último recibo o identificación oficial del contribuyente registrado'),
 (18,'FISICA',2, 1,  NULL, false,1, true, NULL),

 (18,'JURIDICA',1, 18, NULL, false,1, true, NULL),
 (18,'JURIDICA',2, 1,  NULL, false,1, true, NULL),

 (18,'INSTITUCION_PUBLICA',1, 18, NULL, false,1, true, NULL),
 (18,'INSTITUCION_PUBLICA',2, 1,  NULL, false,1, true, NULL);

-- -------------------------------------------------------------------------
-- T19 — Subdivisión / Fusión / Lotificación / Relotificación
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (19,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (19,'FISICA',2, 14, NULL, true, 0, true, NULL),
 (19,'FISICA',3, 26, NULL, true, 1, true, NULL),
 (19,'FISICA',4, NULL, 1, false,1, true, NULL),
 (19,'FISICA',5, 1,  NULL, false,1, true, NULL),
 (19,'FISICA',6, 18, NULL, false,1, true, NULL),
 (19,'FISICA',7, 2,  NULL, true, 0, false,'Si no es el propietario'),
 (19,'FISICA',8, 20, NULL, false,1, true, NULL),
 (19,'FISICA',9, 15, NULL, true, 0, true, NULL),
 (19,'FISICA',10,16, NULL, true, 0, true, NULL),

 (19,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (19,'JURIDICA',2, 14, NULL, true, 0, true, NULL),
 (19,'JURIDICA',3, 26, NULL, true, 1, true, NULL),
 (19,'JURIDICA',4, NULL, 1, false,1, true, NULL),
 (19,'JURIDICA',5, 3,  NULL, true, 1, true, NULL),
 (19,'JURIDICA',6, 1,  NULL, false,1, true, NULL),
 (19,'JURIDICA',7, 18, NULL, false,1, true, NULL),
 (19,'JURIDICA',8, 20, NULL, false,1, true, NULL),
 (19,'JURIDICA',9, 15, NULL, true, 0, true, NULL),
 (19,'JURIDICA',10,16, NULL, true, 0, true, NULL),

 (19,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (19,'INSTITUCION_PUBLICA',2, 14, NULL, true, 0, true, NULL),
 (19,'INSTITUCION_PUBLICA',3, 26, NULL, true, 1, true, NULL),
 (19,'INSTITUCION_PUBLICA',4, NULL, 1, false,1, true, NULL),
 (19,'INSTITUCION_PUBLICA',5, 1,  NULL, false,1, true, NULL),
 (19,'INSTITUCION_PUBLICA',6, 18, NULL, false,1, true, NULL),
 (19,'INSTITUCION_PUBLICA',7, 2,  NULL, true, 0, false,NULL),
 (19,'INSTITUCION_PUBLICA',8, 24, NULL, true, 0, true, NULL);

-- -------------------------------------------------------------------------
-- T20 — Verificación de linderos
-- -------------------------------------------------------------------------
INSERT INTO tramite_requisito
 (tramite_id, aplica_a, orden, tipo_documento_id, grupo_id, original_requerido, copias_requeridas, obligatorio, condicion) VALUES
 (20,'FISICA',1, 13, NULL, true, 0, true, NULL),
 (20,'FISICA',2, NULL, 1, false,1, true, NULL),
 (20,'FISICA',3, 18, NULL, false,1, true, NULL),
 (20,'FISICA',4, 1,  NULL, false,1, true, NULL),
 (20,'FISICA',5, 2,  NULL, true, 0, false,'Si no es el propietario'),
 (20,'FISICA',6, 20, NULL, true, 1, true, NULL),
 (20,'FISICA',7, 15, NULL, true, 0, true, NULL),
 (20,'FISICA',8, 16, NULL, true, 0, true, NULL),

 (20,'JURIDICA',1, 13, NULL, true, 0, true, NULL),
 (20,'JURIDICA',2, NULL, 1, false,1, true, NULL),
 (20,'JURIDICA',3, 18, NULL, false,1, true, NULL),
 (20,'JURIDICA',4, 1,  NULL, false,1, true, NULL),
 (20,'JURIDICA',5, 3,  NULL, true, 1, true, NULL),
 (20,'JURIDICA',6, 20, NULL, true, 1, true, NULL),
 (20,'JURIDICA',7, 15, NULL, true, 0, true, NULL),
 (20,'JURIDICA',8, 16, NULL, true, 0, true, NULL),

 (20,'INSTITUCION_PUBLICA',1, 13, NULL, true, 0, true, NULL),
 (20,'INSTITUCION_PUBLICA',2, NULL, 1, false,1, true, NULL),
 (20,'INSTITUCION_PUBLICA',3, 1,  NULL, false,1, true, NULL),
 (20,'INSTITUCION_PUBLICA',4, 2,  NULL, true, 0, false,NULL),
 (20,'INSTITUCION_PUBLICA',5, 27, NULL, true, 0, true, NULL),
 (20,'INSTITUCION_PUBLICA',6, 17, NULL, true, 0, true, NULL),
 (20,'INSTITUCION_PUBLICA',7, 24, NULL, true, 0, true, NULL),
 (20,'INSTITUCION_PUBLICA',8, 15, NULL, true, 0, true, NULL),
 (20,'INSTITUCION_PUBLICA',9, 16, NULL, true, 0, true, NULL);
