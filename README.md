# REMTYS — Registro Municipal de Trámites y Servicios

Portal web para que los ciudadanos (contribuyentes) de un municipio se registren, den de alta sus predios, inicien trámites de Catastro y Tesorería, suban la documentación requerida y den seguimiento a su expediente. Incluye un backoffice para que el personal revise y valide los documentos, resuelva trámites y lleve el control fiscal.

Lo desarrollé en mi trabajo como Jefe del Departamento de TI del H. Ayuntamiento de Ecatepec de Morelos (Estado de México).

> **Versión de portafolio:** se removieron datos, configuraciones y recursos institucionales. El sistema original fue desarrollado para una dependencia municipal.
> Todos los usuarios, contribuyentes, RFC/CURP, predios y códigos postales incluidos son ficticios.

---

## Problema que resuelve

Los trámites municipales (certificaciones catastrales, traslado de dominio, no adeudo predial, etc.) se hacían de forma presencial y con papel. Cada trámite pide casi los mismos documentos (identificación, escritura, recibo predial…), así que el ciudadano los entregaba una y otra vez y el personal no tenía visibilidad del estado de cada expediente.

REMTYS centraliza esto:

- El ciudadano sube cada documento **una sola vez** y el sistema lo reutiliza en otros trámites mientras siga vigente.
- Para cada trámite, el sistema calcula qué requisitos ya se cumplen, cuáles faltan, cuáles vencieron y cuáles fueron rechazados.
- El personal tiene una cola de validación, el historial completo del expediente y bitácoras de auditoría.

## Funcionalidades

**Portal del ciudadano**
- Registro de persona física o moral (RFC, CURP o representante legal) con validación de formato.
- Verificación de correo electrónico con token de un solo uso y vencimiento, más reenvío con tiempo de espera.
- Recuperación de contraseña con tokens guardados como hash.
- Alta de predios con catálogo de códigos postales y colonias (SEPOMEX).
- Inicio de trámites y matriz de requisitos por trámite con estados `CUMPLE`, `REUTILIZABLE`, `VENCIDO`, `INVALIDO`, `ESPECIFICO`, `EXCLUSIVO` y `FALTANTE`.
- Carga documental con versiones, vigencia y reutilización entre expedientes.
- Consulta de obligaciones fiscales, saldo y pagos.

**Backoffice**
- Roles: `contribuyente`, `revisor`, `admin` y `supergod` (consola técnica de eventos del sistema).
- Contribuyentes, predios, expedientes y trámites, con cambios de estado y subsanación.
- Cola de validación de documentos (aprobar o rechazar con motivo).
- Inspecciones, periodos fiscales, obligaciones y pagos.
- Catálogos administrables: tipos de trámite, tipos de documento, requisitos y dependencias entre trámites.
- Folios oficiales consecutivos sin huecos ni duplicados (tabla `folio_oficial` con reserva transaccional).
- Reportes por estado y tipo.

**Operación**
- Bitácora de eventos del sistema (`system_events`) con `correlation_id` por request y funciones de retención por nivel (`scripts/purgar_logs.php`).
- Auditoría de cambios campo por campo (`audit_cambios`), con enmascarado de campos sensibles.
- Docker para desarrollo (PHP + Apache, PostgreSQL 17 y Mailpit) y para producción (Nginx con TLS delante de la app y sin exponer la BD).
- Scripts de respaldo y restauración de BD y archivos con verificación de integridad y retención.

## Seguridad

| Área | Implementación |
|------|----------------|
| Contraseñas | `password_hash()` bcrypt, costo 12 |
| Fuerza bruta | Bloqueo de cuenta tras 5 intentos fallidos (30 min) y **rate limiting por IP persistido en BD** (`rate_limits`, migración 011): resiste la rotación de cookies de sesión |
| Sesiones | `session_regenerate_id(true)` al iniciar sesión, cookies `HttpOnly` y `SameSite=Strict`, expiración por inactividad |
| CSRF | Token en todos los formularios POST |
| Cabeceras | CSP con *nonce* por request, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy` y HSTS bajo HTTPS |
| SQL | PDO con *prepared statements* en todas las consultas |
| Salida | Escape (`htmlspecialchars`) en todas las vistas |
| Archivos | Lista blanca de extensiones, MIME real con `finfo` y almacenamiento fuera del *webroot*. Se sirven por un controlador que bloquea *path traversal* (`realpath` y prefijo) |
| Autorización | Middleware por rol y verificación de propiedad (IDOR) en cada consulta del ciudadano |
| BD | Rol de aplicación `remtys_app` con mínimo privilegio: solo DML, sin `CREATE` (migración 003) |
| RLS | Políticas de *Row Level Security* por contribuyente ya definidas (migración 004, fase 1). La activación (`ENABLE ROW LEVEL SECURITY`) queda documentada para una fase 2, porque requiere fijar la GUC `remtys.contribuyente_id` por transacción y una política para el backoffice |
| Integridad | Restricción `EXCLUDE` con GiST para impedir titularidades traslapadas de un predio, más `CHECK`s de dominio (migraciones 002 y 010) |
| Secretos | Solo por variables de entorno. La app no arranca fuera de local/docker si falta `DB_PASS` |

## Arquitectura

```
Navegador ──► public/index.php  (front controller: cabeceras de seguridad, CSP nonce,
                 │                sesión segura, whitelist de rutas módulo/acción)
                 ├─► app/helpers/middleware.php  (rol, propiedad, rate limit)
                 ├─► app/controllers/*_controller.php
                 │        └─► app/services/*  (Expediente, Documento, Requisitos,
                 │                             Fiscal, Folio, Auditoría, Historial)
                 └─► app/views/*  (PHP + Bootstrap 5)
                          │
                          ▼
                   PostgreSQL 17  (PDO, rol remtys_app)
```

- PHP 8.1+ nativo, sin framework. Enrutamiento `?modulo=…&accion=…` validado contra una lista blanca.
- Lógica de negocio en servicios, separada de los controladores.
- Diagramas de flujo en Mermaid: `flujo.mmd` (general) y `docs/flujos/` (registro, alta de trámite, carga documental, revisión, subsanación y resolución).

## Modelo de datos

### `database/` — base de datos de la aplicación

- `schema.sql`: esquema base con usuarios, contribuyentes (física y moral), predios y titularidad, tipos de trámite y documento, requisitos, expedientes, trámites y dependencias, documentos y su vínculo con expedientes, validaciones, periodos y obligaciones fiscales, pagos y su aplicación, inspecciones e historial.
- `migrations/001–012`: migraciones *forward-only* aplicadas por `database/migrate.php`, que guarda un checksum SHA-256 para detectar cambios en migraciones ya aplicadas:

| # | Contenido |
|---|-----------|
| 001, 006 | Secuencias para folios |
| 002 | Endurecimiento: `CHECK`s, índices y restricciones |
| 003 | Rol de aplicación de mínimo privilegio |
| 004 | Políticas RLS (preparación) |
| 005 | Catálogo de códigos postales y colonias |
| 007, 012 | `system_events`, `correlation_id` y funciones de retención |
| 008 | Detalle de persona física y moral |
| 009 | Verificación de correo |
| 010 | Auditoría de cambios, folio oficial, catálogo de eventos de historial y `EXCLUDE` GiST |
| 011 | Rate limiting por IP persistente |

### `database/v2/` — rediseño del repositorio documental

Rediseño del modelo para un **repositorio documental único por contribuyente**: el documento pertenece al contribuyente, al inmueble o a la solicitud (`doc_scope`), tiene una regla de vigencia y satisface requisitos de varios trámites.

- `01_schema.sql`: 14 tablas con ENUMs, UUIDs (`pgcrypto`), `citext`, un trigger que valida el *scope* del documento y dos vistas: `v_solicitud_checklist` (estado de cada requisito de una solicitud) y `v_documentos_reutilizables`.
- `02–04`: catálogos normalizados a partir de las fichas públicas del registro de trámites: 32 tipos de documento con alias, 20 trámites y 342 requisitos por tipo de persona, con grupos de documentos equivalentes ("cualquiera de estos acredita la propiedad").
- `05_patch_ajustes.sql`: ajustes idempotentes. `99_validate.sql`: consultas de verificación.

## Stack

PHP 8.1+ (PDO pgsql, fileinfo, mbstring) · PostgreSQL 17 · Bootstrap 5 · Apache / Nginx · Docker Compose · Mailpit (SMTP de desarrollo) · PHPUnit 10

## Estructura

```
.
├── app/
│   ├── controllers/      auth, contribuyente, admin, supergod, home
│   ├── helpers/          auth, csrf, csp, middleware, sanitize, FileUpload, document_serve, audit, mailer…
│   ├── services/         ExpedienteService, DocumentoService, RequisitosService, FolioService, AuditService…
│   └── views/            admin/, contribuyente/, shared/, supergod/, layouts/
├── config/               database.php, mail.php (leen variables de entorno)
├── database/
│   ├── schema.sql, seeds.sql, seeds_cp_demo.sql, migrate.php
│   ├── migrations/       001–012
│   └── v2/               esquema del repositorio documental
├── docker/               apache.conf, nginx.conf (TLS)
├── docs/flujos/          diagramas Mermaid
├── public/               index.php (front controller) y assets
├── scripts/              init_db.sh, backup_*.sh, restore_*.sh, purgar_logs.php, deploy.sh
├── storage/              uploads/ y logs/ (fuera del webroot, ignorados por git)
└── tests/Unit/           pruebas PHPUnit
```

## Cómo levantarlo localmente

### Opción A: PHP y PostgreSQL locales

Requisitos: PHP ≥ 8.1 con `pdo_pgsql`, `fileinfo` y `mbstring`, PostgreSQL ≥ 14 y Composer.

```bash
cp .env.example .env                     # y reemplaza los CHANGE_ME

# Crea la BD, aplica esquema, datos demo, migraciones y catálogo CP demo
PGHOST=localhost PGUSER=postgres \
DB_PASS='contraseña-del-rol-app' DEMO_PASSWORD='Demo#Remtys2026' \
bash scripts/init_db.sh

# Servidor de desarrollo
DB_HOST=localhost DB_USER=remtys_app DB_PASS='contraseña-del-rol-app' \
DB_NAME=remtys2_db APP_ENV=local MAIL_TRANSPORT=log \
BASE_URL= APP_URL=http://localhost:8080 \
php -S localhost:8080 -t public
```

Luego abre `http://localhost:8080/?modulo=auth&accion=login`.

### Opción B: Docker Compose (desarrollo)

```bash
cp .env.example .env                     # define DB_PASS y DEMO_PASSWORD
docker compose up -d --build
# el contenedor de BD solo carga schema.sql y seeds.sql; aplica el resto con:
PGHOST=localhost PGUSER=postgres PGPASSWORD="$DB_PASS" DB_PASS="$DB_PASS" \
DEMO_PASSWORD=… bash scripts/init_db.sh
```

App: `http://localhost:8080/remtysapp/public/` · Mailpit: `http://localhost:8025`

### Usuarios demo

`database/seeds.sql` crea estos usuarios ficticios. El hash bcrypt se genera **al cargar el seed** con `pgcrypto`, así que el repositorio no contiene hashes. La contraseña es la que pases en `DEMO_PASSWORD`; si no defines ninguna, el seed usa `Demo#Remtys2026`.

| Correo | Rol |
|--------|-----|
| `superadmin@ejemplo.com` | supergod |
| `admin@ejemplo.com` | admin |
| `revisor@ejemplo.com` | revisor |
| `ciudadano@ejemplo.com` | contribuyente (persona física) |
| `empresa@ejemplo.com` | contribuyente (persona moral) |

> Solo para desarrollo local. No ejecutes `seeds.sql` en producción.

### Esquema v2

```bash
createdb remtys_db_v2
for f in database/v2/0*.sql; do psql -v ON_ERROR_STOP=1 -d remtys_db_v2 -f "$f"; done
psql -d remtys_db_v2 -f database/v2/99_validate.sql
```

## Pruebas

```bash
composer install
composer test          # vendor/bin/phpunit — 28 pruebas unitarias
composer lint          # php -l sobre todo el código
```

Las pruebas cubren el enmascarado de campos sensibles en la auditoría, el *diff* de cambios, la validación de folios y la paginación *keyset*. No necesitan base de datos.

---

## English summary

**REMTYS** (Municipal Registry of Procedures and Services) is a PHP 8 / PostgreSQL 17 web portal I built while heading the IT department of a Mexican municipal government. Citizens register (with email verification), add their properties, start cadastral and treasury procedures and upload supporting documents once, then reuse them across procedures while they remain valid. Staff review and validate documents, resolve cases and track tax obligations.

Highlights: a framework-less front controller with a route whitelist, CSRF protection, a per-request CSP nonce, hardened sessions, bcrypt passwords, IP-based rate limiting persisted in PostgreSQL, ownership checks on every citizen query, and a least-privilege database role. Row-level security policies are defined but not yet enabled. The repo also includes forward-only SQL migrations with checksum verification, an `EXCLUDE` GiST constraint, field-level audit logging and system events with correlation IDs, Docker setups for development (Mailpit) and production (Nginx with TLS), and a redesigned document-repository schema (`database/v2`).

*Portfolio version: real data, configuration and institutional assets were removed. The original system was built for a municipal agency.*
