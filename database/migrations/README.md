# REMTYS — Migraciones

Runner: `database/migrate.php`

## Convenciones

- Nombre estricto: `NNN_slug.sql` donde `NNN` es un número de 3 dígitos, incremental.
- **Forward-only**: no hay rollback. Para deshacer un cambio, crear una nueva migración que lo compense.
- Cada migración es una transacción. Si trae `BEGIN;` propio, el runner lo respeta; si no, lo envuelve.
- El runner guarda `checksum` SHA-256 del archivo: **editar una migración ya aplicada genera `CHECKSUM_DRIFT` en `verify`**.

## Comandos

```bash
# Ver estado
php database/migrate.php status

# Aplicar todas las pendientes
php database/migrate.php up

# Aplicar hasta una específica
php database/migrate.php up 012

# Verificar integridad (detecta edición de migraciones aplicadas)
php database/migrate.php verify

# Marcar como aplicadas todas las presentes SIN ejecutarlas (uso único en sistemas pre-existentes)
php database/migrate.php baseline
```

## Primera instalación en sistema existente

Si la BD ya tiene las 001–009 aplicadas manualmente antes de que existiera el runner:

```bash
# 1) crear la tabla schema_migrations
php database/migrate.php status

# 2) marcar 001..009 como ya aplicadas
php database/migrate.php baseline

# 3) desde ahora, aplicar nuevas migraciones con:
php database/migrate.php up
```

## Instalación limpia

```bash
psql ... -f database/schema.sql
psql ... -f database/seeds.sql
php database/migrate.php baseline   # marcar todas como aplicadas
```

(O bien: dejar `schema.sql` mínimo y que todo se construya con migraciones. REMTYS mantiene ambos por compatibilidad.)

## Migraciones presentes

| Versión | Descripción |
|---------|-------------|
| 001 | Secuencias iniciales de folios (legacy — reemplazado por folio_oficial en 010) |
| 002 | Endurecimiento de producción |
| 003 | Rol de aplicación dedicado |
| 004 | Preparación para Row Level Security |
| 005 | Catálogo de códigos postales / colonias |
| 006 | Secuencias de negocio adicionales |
| 007 | `system_events` (audit log) |
| 008 | Detalle de contribuyente (física / moral) |
| 009 | Verificación de email |
| 010 | P0 crítica: `PENDIENTE`, `audit_cambios`, `folio_oficial`, `validaciones_documento.vigente`, EXCLUDE predio_contribuyente, catálogo eventos, storage hardening |
| 011 | Recuperación de contraseña + `rate_limits` persistentes |
| 012 | Particionamiento `system_events` y `audit_vista_documento` |
